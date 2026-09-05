<?php

namespace App\Http\Controllers\ArtificialIntelligence;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Services\AiDocumentService;
use App\Services\AiDataQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantController extends Controller
{
    protected string $ollamaBaseUrl;
    protected string $model;
    protected AiDocumentService $documents;
    protected AiDataQueryService $dataQuery;

    public function __construct(AiDocumentService $documents, AiDataQueryService $dataQuery)
    {
        $this->ollamaBaseUrl = config('services.ollama.base_url', 'http://127.0.0.1:11434');
        $this->model = AiSetting::get('active_model', config('services.ollama.model', 'llama3.2:3b'));
        $this->documents = $documents;
        $this->dataQuery = $dataQuery;
    }

    /**
     * GET /api/ai-assistant/models
     *
     * Lists models actually pulled in Ollama (via its /api/tags endpoint),
     * plus which one is currently active.
     */
    public function listModels(): JsonResponse
    {
        try {
            $response = Http::timeout(10)->get("{$this->ollamaBaseUrl}/api/tags");

            if ($response->failed()) {
                return response()->json(['models' => [], 'active' => $this->model, 'error' => 'Could not reach Ollama.'], 502);
            }

            $models = collect($response->json('models', []))
                ->pluck('name')
                ->values();

            return response()->json([
                'models' => $models,
                'active' => $this->model,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['models' => [], 'active' => $this->model, 'error' => 'Could not reach Ollama.'], 502);
        }
    }

    /**
     * POST /api/ai-assistant/models
     * Body: { "model": "gemma4:e4b" }
     *
     * Switches the active model. Validated against the actual list from
     * Ollama so you can't accidentally set a model that isn't pulled.
     */
    public function setModel(Request $request): JsonResponse
    {
        $validated = $request->validate(['model' => 'required|string']);

        $available = collect($this->listModels()->getData(true)['models'] ?? []);

        if (!$available->contains($validated['model'])) {
            return response()->json([
                'error' => "That model isn't pulled in Ollama yet. Run: ollama pull {$validated['model']}",
            ], 422);
        }

        AiSetting::set('active_model', $validated['model']);

        return response()->json(['active' => $validated['model']]);
    }

    /**
     * POST /api/ai-assistant/chat
     *
     * Body: { "message": "...", "history": [{role, content}, ...] }
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:4000',
            'history' => 'array',
            'history.*.role' => 'required_with:history|in:user,assistant,system',
            'history.*.content' => 'required_with:history|string',
        ]);

        $history = $validated['history'] ?? [];
        $messages = array_merge([$this->systemPrompt()], $history, [
            ['role' => 'user', 'content' => $validated['message']],
        ]);

        // Longer generations (letters/memos with full body text) can take
        // well past PHP's default 60s max_execution_time on a local model.
        // Raise it for this request only, and match the HTTP client timeout
        // to it so we don't hit one limit right as the other would fire.
        set_time_limit(120);

        try {
            $response = Http::timeout(120)->post("{$this->ollamaBaseUrl}/api/chat", [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                // Lower temperature + a repeat penalty make short structured
                // JSON outputs far less likely to stutter/repeat themselves,
                // which is the root cause of the duplicated-JSON bug.
                'options' => [
                    'temperature' => 0.2,
                    'repeat_penalty' => 1.3,
                ],
            ]);

            if ($response->failed()) {
                Log::error('Ollama request failed', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['reply' => "Sorry, I couldn't reach the AI model. Make sure Ollama is running."], 502);
            }

            $rawReply = $response->json('message.content', '');

            // Check whether the model responded with a special action
            // (document generation, or a live data question) instead of
            // a normal chat reply.
            $action = $this->extractAction($rawReply);

            if ($action && $action['action'] === 'query_data') {
                $result = $this->dataQuery->answer(
                    $action['key'] ?? '',
                    $action['limit'] ?? null,
                    $action['name'] ?? null,
                );

                if ($result === null) {
                    return response()->json([
                        'reply' => "I don't have a way to look that specific stat up yet.",
                    ]);
                }

                return response()->json([
                    'reply' => $result['text'],
                    'table' => $result['table'], // null, or ['title', 'columns', 'rows']
                ]);
            }

            if ($action && $action['action'] === 'generate_document') {
                $file = $this->documents->handle($action);

                if ($file) {
                    return response()->json([
                        'reply' => $this->confirmationText($action),
                        'file' => $file,
                    ]);
                }

                // Action was recognized but generation failed (e.g. inmate not found).
                return response()->json([
                    'reply' => "I couldn't find matching records to generate that document. Could you double-check the name/ID?",
                ]);
            }

            return response()->json(['reply' => $rawReply ?: "Sorry, I didn't get a valid response."]);

        } catch (\Throwable $e) {
            Log::error('Ollama connection error: ' . $e->getMessage());
            return response()->json(['reply' => 'Could not connect to the local AI server. Is Ollama running (ollama serve)?'], 502);
        }
    }

    /**
     * Path to the extracted system prompt text. Kept as its own file (rather
     * than an inline heredoc) so the prompt can be edited/reviewed/diffed
     * independently of controller logic.
     */
    protected function systemPromptPath(): string
    {
        return resource_path('prompts/ai_assistant_system_prompt.md');
    }

    /**
     * System prompt: instructs the small local model to respond with a strict
     * JSON action block ONLY when the user is asking for a generated document.
     * Otherwise it should just reply normally in plain text.
     *
     * The prompt is authored as Markdown purely so it's readable/highlighted
     * in an editor (headers, fenced JSON examples, etc). The small local
     * model doesn't need that decoration, so we strip it down to plain text
     * before it goes in the message payload.
     */
    protected function systemPrompt(): array
    {
        $path = $this->systemPromptPath();

        if (!is_readable($path)) {
            Log::error("AI assistant system prompt file missing or unreadable: {$path}");
            throw new \RuntimeException("System prompt file not found at {$path}");
        }

        $content = $this->stripMarkdown(trim(file_get_contents($path)));

        return ['role' => 'system', 'content' => $content];
    }

    /**
     * Strips lightweight Markdown decoration (##, **, `, ```lang fences)
     * used to make the prompt file readable in an editor, leaving the
     * plain-text instructions the model actually needs.
     */
    protected function stripMarkdown(string $text): string
    {
        $text = preg_replace('/^```[a-z]*\n?|```$/m', '', $text);
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);
        $text = str_replace(['**', '`'], '', $text);

        return trim($text);
    }

    /**
     * Tries to parse the model's raw reply as a document-generation action.
     * Returns null if it's a normal text reply.
     *
     * Small local models occasionally stutter/repeat themselves on short
     * structured outputs, e.g. producing:
     *   {"action":"query_data","key":"total_cells"} {"action":"query_data","key":"total_cells"}
     * strict json_decode() on the whole string fails on trailing content
     * like that, so instead we pull out just the FIRST balanced {...}
     * object in the reply and decode only that, ignoring whatever the
     * model appended after it.
     */
    protected function extractAction(string $rawReply): ?array
    {
        $trimmed = trim($rawReply);

        // Strip accidental markdown code fences some models add despite instructions.
        $trimmed = preg_replace('/^```(?:json)?|```$/m', '', $trimmed);
        $trimmed = trim($trimmed);

        if ($trimmed === '' || !str_contains($trimmed, '{')) {
            return null;
        }

        $jsonString = $this->extractFirstJsonObject($trimmed);

        if ($jsonString === null) {
            return null;
        }

        // Small local models occasionally produce technically-invalid JSON
        // in longer free-text fields (letters/memos): a stray backslash
        // that isn't a valid JSON escape, or — more commonly — real line
        // breaks typed into the "body" field instead of an escaped \n.
        // json_decode() fails silently on the *entire* object for either
        // mistake, so we repair both before decoding rather than losing
        // the whole action over one bad character.
        $jsonString = $this->repairJsonForDecode($jsonString);

        $decoded = json_decode($jsonString, true);

        if (!is_array($decoded) || !in_array($decoded['action'] ?? null, ['generate_document', 'query_data'], true)) {
            return null;
        }

        return $decoded;
    }

    /**
     * Repairs two common ways a small local model's JSON output fails
     * strict json_decode(), without touching well-formed JSON:
     *
     * 1. Invalid backslash escapes (e.g. "\[Name]") — the backslash gets
     *    escaped itself so it decodes as a literal backslash.
     * 2. Raw, unescaped control characters inside string values — most
     *    often literal line breaks in a generated letter/memo body where
     *    the model typed a real newline instead of writing "\n". These are
     *    converted to their proper escaped form.
     *
     * Both fixes only apply inside quoted string values (tracked via
     * $inString below) — whitespace and structure outside strings is left
     * untouched.
     */
    protected function repairJsonForDecode(string $json): string
    {
        $length = strlen($json);
        $out = '';
        $inString = false;
        $i = 0;

        $validEscapes = ['"', '\\', '/', 'b', 'f', 'n', 'r', 't', 'u'];

        while ($i < $length) {
            $char = $json[$i];

            if (!$inString) {
                if ($char === '"') {
                    $inString = true;
                }
                $out .= $char;
                $i++;
                continue;
            }

            // Inside a string value from here on.

            if ($char === '\\' && $i + 1 < $length) {
                $next = $json[$i + 1];
                $out .= in_array($next, $validEscapes, true)
                    ? $char . $next          // already a valid escape, leave it
                    : '\\\\' . $next;        // invalid — escape the backslash itself
                $i += 2;
                continue;
            }

            if ($char === '"') {
                $inString = false;
                $out .= $char;
                $i++;
                continue;
            }

            // A raw (unescaped) control character inside a string is what
            // breaks json_decode() when a model writes a real line break.
            if (ord($char) < 0x20) {
                switch ($char) {
                    case "\n":
                        $out .= '\\n';
                        break;
                    case "\r":
                        $out .= '\\r';
                        break;
                    case "\t":
                        $out .= '\\t';
                        break;
                    default:
                        $out .= sprintf('\\u%04x', ord($char));
                }
                $i++;
                continue;
            }

            $out .= $char;
            $i++;
        }

        return $out;
    }

    /**
     * Scans $text for the first balanced {...} substring (respecting quoted
     * strings and escaped characters, so braces inside string values like
     * a letter body don't throw off the count) and returns it, or null if
     * no complete object is found.
     */
    protected function extractFirstJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"') {
                $inString = true;
            } elseif ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        // Braces never closed - incomplete/truncated JSON, bail out.
        return null;
    }

    protected function confirmationText(array $action): string
    {
        return match ($action['type'] ?? null) {
            'inmate_profile' => "Here's the inmate profile PDF you asked for.",
            'letter' => "Here's the letter/memo, ready to download.",
            'chart' => "Here's the chart you asked for.",
            default => "Here's the file you asked for.",
        };
    }
}