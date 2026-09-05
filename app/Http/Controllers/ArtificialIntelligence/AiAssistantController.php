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
                // outputs far less likely to stutter/repeat themselves,
                // which was the root cause of the old duplicated-JSON bug.
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
                if (($action['type'] ?? null) === 'letter') {
                    $action['body'] = $this->extractLetterBody($rawReply);
                }

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
     * System prompt: instructs the small local model to respond with a
     * plain-line action block ONLY when the user is asking for a generated
     * document or a live data lookup. Otherwise it should just reply
     * normally in plain text.
     *
     * The prompt is authored as Markdown purely so it's readable/highlighted
     * in an editor (headers, fenced examples, etc). The small local model
     * doesn't need that decoration, so we strip it down to plain text
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
     * Tries to parse the model's raw reply as a document-generation or
     * data-query action. Returns null if it's a normal text reply.
     *
     * Format the model is asked to produce is plain "LABEL: value" lines,
     * e.g.:
     *   ACTION: generate_document
     *   TYPE: letter
     *   SUBJECT: Overcrowding in Cell Block 3
     *   RECIPIENT: Warden
     *   ===BODY===
     *   ...
     *   ===END BODY===
     *
     * We deliberately moved off JSON for this: a small local model asked to
     * hop between rigid JSON syntax and a free-text letter body would
     * intermittently drop the JSON header entirely and jump straight to
     * the body (or produce unescaped newlines/quotes inside a JSON string
     * that broke json_decode()). Flat label lines give the model nothing
     * to get the escaping of wrong, and a missing/malformed line just
     * fails to populate one field instead of invalidating the whole
     * action the way one bad character in JSON did.
     *
     * Parsing rules:
     *   - The reply must start with an ACTION: line (after stripping any
     *     accidental code fences). Anything before that means it's a
     *     normal chat reply, not an action.
     *   - Metadata lines are read until the first ===BODY=== marker, the
     *     first blank line, or a line that isn't "label: value" shaped.
     *   - Stray/malformed lines are skipped rather than failing the whole
     *     parse, so a small model going slightly off-script for one field
     *     doesn't take down the rest of the action.
     */
    protected function extractAction(string $rawReply): ?array
    {
        $trimmed = trim($rawReply);

        // Strip accidental markdown code fences some models add despite instructions.
        $trimmed = preg_replace('/^```[a-z]*\n?|```$/m', '', $trimmed);
        $trimmed = trim($trimmed);

        if ($trimmed === '' || !preg_match('/^ACTION\s*:/i', $trimmed)) {
            return null;
        }

        // Only scan the metadata block: everything up to the first
        // ===BODY=== marker, if the reply has one (letters only).
        $metaText = $trimmed;
        if (preg_match('/===\s*BODY\s*===/i', $trimmed, $bodyMatch, PREG_OFFSET_CAPTURE)) {
            $metaText = substr($trimmed, 0, $bodyMatch[0][1]);
        }

        $fields = [];
        foreach (preg_split('/\r\n|\r|\n/', $metaText) as $line) {
            $line = trim($line);

            if ($line === '') {
                // A blank line ends the metadata block once we've captured
                // at least something; a leading blank line is just ignored.
                if (!empty($fields)) {
                    break;
                }
                continue;
            }

            if (!preg_match('/^([A-Za-z_]+)\s*:\s*(.*)$/', $line, $lineMatch)) {
                // Not a "label: value" shaped line — skip it rather than
                // aborting the whole parse over one stray line.
                continue;
            }

            $fields[strtolower($lineMatch[1])] = trim($lineMatch[2]);
        }

        if (empty($fields['action'])) {
            Log::warning('AI action: no ACTION line found in parsed fields', ['raw_reply' => $rawReply]);
            return null;
        }

        $action = strtolower($fields['action']);

        if (!in_array($action, ['generate_document', 'query_data'], true)) {
            Log::warning('AI action: parsed but action field not recognized', ['fields' => $fields]);
            return null;
        }

        $decoded = ['action' => $action];

        foreach (['type', 'query', 'subject', 'recipient', 'report', 'key', 'name'] as $field) {
            if (isset($fields[$field]) && $fields[$field] !== '') {
                $decoded[$field] = $fields[$field];
            }
        }

        if (isset($decoded['type'])) {
            $decoded['type'] = strtolower($decoded['type']);
        }

        if (isset($fields['chart_type']) && $fields['chart_type'] !== '') {
            $decoded['chart_type'] = strtolower($fields['chart_type']);
        }

        if (isset($fields['limit']) && $fields['limit'] !== '' && is_numeric($fields['limit'])) {
            $decoded['limit'] = (int) $fields['limit'];
        }

        return $decoded;
    }

    /**
     * Pulls the letter/memo body out from between the ===BODY=== /
     * ===END BODY=== markers the system prompt asks the model to use.
     * The body is always plain free text now — there's no JSON to fall
     * back into decoding, so this only has to handle the marker itself
     * being slightly malformed or missing.
     *
     * Two layers, in order of preference:
     *   1. Text between ===BODY=== and ===END BODY=== — the documented,
     *      expected format.
     *   2. Text after ===BODY=== through the end of the reply — covers a
     *      truncated generation that's missing the closing marker.
     *
     * Returns '' (not null) if nothing usable is found, so callers can
     * render an empty-but-valid letter rather than crash.
     */
    protected function extractLetterBody(string $rawReply): string
    {
        if (preg_match('/===\s*BODY\s*===(.*?)===\s*END\s*BODY\s*===/is', $rawReply, $matches)) {
            $body = trim($matches[1]);
            if ($body !== '') {
                return $body;
            }
        }

        if (preg_match('/===\s*BODY\s*===(.*)$/is', $rawReply, $matches)) {
            $body = trim($matches[1]);
            $body = preg_replace('/===\s*END\s*BODY\s*===\s*$/i', '', $body);
            $body = trim($body);
            if ($body !== '') {
                return $body;
            }
        }

        Log::warning('AI action: letter body could not be extracted from any source', [
            'raw_reply' => $rawReply,
        ]);

        return '';
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