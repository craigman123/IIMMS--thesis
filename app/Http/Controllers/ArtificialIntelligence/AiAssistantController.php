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

        try {
            $response = Http::timeout(60)->post("{$this->ollamaBaseUrl}/api/chat", [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
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
                $answer = $this->dataQuery->answer($action['key'] ?? '');

                return response()->json([
                    'reply' => $answer ?? "I don't have a way to look that specific stat up yet.",
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
     * System prompt: instructs the small local model to respond with a strict
     * JSON action block ONLY when the user is asking for a generated document.
     * Otherwise it should just reply normally in plain text.
     */
    protected function systemPrompt(): array
    {
        $content = <<<PROMPT
You are a helpful AI assistant embedded inside an Inmate Information Management System.
Answer normally in plain text for regular questions.

SPECIAL CASE — Document generation:
If, and only if, the user is clearly asking you to generate/create/export a document
(a PDF profile, a letter/memo, or a chart), respond with ONLY a raw JSON object and
nothing else — no explanation, no markdown fences, no extra text. Use one of these
exact shapes:

1) Inmate profile PDF:
{"action": "generate_document", "type": "inmate_profile", "query": "<name or ID mentioned by the user>"}

2) Letter or memo (write the actual content yourself):
{"action": "generate_document", "type": "letter", "subject": "<short subject>", "recipient": "<recipient name or role>", "body": "<the full letter text you write>"}

3) Chart of existing data (pick the closest matching report):
{"action": "generate_document", "type": "chart", "report": "status_breakdown" | "crime_type_breakdown" | "admissions_by_month", "chart_type": "bar" | "pie" | "line"}

SPECIAL CASE — Live data questions:
If the user asks a factual question about current counts/stats in the system
(how many inmates, staff, cells, incidents, etc.) — NEVER guess or make up a
number yourself. Instead respond with ONLY this JSON (no other text), picking
whichever "key" is the closest match to what they asked:

{"action": "query_data", "key": "total_inmates" | "inmates_by_status" | "inmates_by_cell" | "total_staff" | "staff_by_role" | "total_cells" | "cell_occupancy" | "incidents_total" | "incidents_unresolved" | "incidents_recent"}

If none of those keys are a reasonable match for what they're asking, do NOT
guess a number — just answer normally in plain text saying you don't have
that specific data available yet.

Examples:
User: "Can you make a PDF profile for inmate Juan Dela Cruz?"
You: {"action": "generate_document", "type": "inmate_profile", "query": "Juan Dela Cruz"}

User: "Write a memo to the warden about overcrowding in Cell Block 3"
You: {"action": "generate_document", "type": "letter", "subject": "Overcrowding in Cell Block 3", "recipient": "Warden", "body": "..."}

User: "Show me a chart of inmates by crime type"
You: {"action": "generate_document", "type": "chart", "report": "crime_type_breakdown", "chart_type": "bar"}

User: "How many inmates do we have?"
You: {"action": "query_data", "key": "total_inmates"}

User: "Any unresolved incidents right now?"
You: {"action": "query_data", "key": "incidents_unresolved"}

For anything else — questions, explanations, casual conversation — just answer normally in plain text.
PROMPT;

        return ['role' => 'system', 'content' => $content];
    }

    /**
     * Tries to parse the model's raw reply as a document-generation action.
     * Returns null if it's a normal text reply.
     */
    protected function extractAction(string $rawReply): ?array
    {
        $trimmed = trim($rawReply);

        // Strip accidental markdown code fences some models add despite instructions.
        $trimmed = preg_replace('/^```(?:json)?|```$/m', '', $trimmed);
        $trimmed = trim($trimmed);

        if ($trimmed === '' || $trimmed[0] !== '{') {
            return null;
        }

        $decoded = json_decode($trimmed, true);

        if (!is_array($decoded) || !in_array($decoded['action'] ?? null, ['generate_document', 'query_data'], true)) {
            return null;
        }

        return $decoded;
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