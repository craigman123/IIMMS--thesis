<?php

namespace App\Services;

use App\Models\Inmate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiDocumentService
{
    /**
     * Directory (inside storage/app/public) where generated files are saved.
     */
    protected string $outputDir = 'ai-documents';

    /**
     * Entry point. Takes the parsed "action" array from the AI's JSON response
     * and returns file info: ['url' => ..., 'filename' => ..., 'type' => 'pdf'|'image']
     * or null if the action type is unknown / generation failed.
     */
    public function handle(array $action): ?array
    {
        return match ($action['type'] ?? null) {
            'inmate_profile' => $this->generateInmateProfile($action),
            'letter' => $this->generateLetter($action),
            'chart' => $this->generateChart($action),
            default => null,
        };
    }

    /**
     * Looks up an inmate by name or ID and renders a profile PDF.
     * Expects: ['query' => 'name or id typed by the user']
     */
    protected function generateInmateProfile(array $action): ?array
    {
        $query = trim($action['query'] ?? '');
        if ($query === '') {
            return null;
        }

        $inmate = is_numeric($query)
            ? Inmate::find((int) $query)
            : Inmate::where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->first();

        if (!$inmate) {
            // No match — return null so the controller can fall back to a text reply.
            return null;
        }

        $filename = 'inmate-profile-' . Str::slug($inmate->id . '-' . $inmate->last_name) . '-' . time() . '.pdf';
        $path = "{$this->outputDir}/{$filename}";

        $pdf = Pdf::loadView('pdf.inmate-profile', ['inmate' => $inmate]);
        Storage::disk('public')->put($path, $pdf->output());

        return [
            'type' => 'pdf',
            'filename' => $filename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    /**
     * Renders AI-authored letter/memo content into a formatted PDF.
     * Expects: ['subject' => ..., 'recipient' => ..., 'body' => ...]
     */
    protected function generateLetter(array $action): array
    {
        $data = [
            'subject' => $action['subject'] ?? 'Untitled',
            'recipient' => $action['recipient'] ?? '',
            'body' => $action['body'] ?? '',
            'date' => now()->format('F j, Y'),
        ];

        $filename = 'letter-' . Str::slug($data['subject']) . '-' . time() . '.pdf';
        $path = "{$this->outputDir}/{$filename}";

        $pdf = Pdf::loadView('pdf.letter', $data);
        Storage::disk('public')->put($path, $pdf->output());

        return [
            'type' => 'pdf',
            'filename' => $filename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    /**
     * Generates a chart image for one of a few predefined report types.
     * Expects: ['report' => 'status_breakdown'|'crime_type_breakdown'|'admissions_by_month', 'chart_type' => 'bar'|'pie'|'line']
     *
     * Uses QuickChart.io (https://quickchart.io) to render the PNG — no local
     * charting library needed. For an offline-only setup, swap this out for a
     * PHP charting lib instead.
     */
    protected function generateChart(array $action): ?array
    {
        $report = $action['report'] ?? null;
        $chartType = $action['chart_type'] ?? 'bar';

        [$labels, $values, $title] = match ($report) {
            'status_breakdown' => $this->dataStatusBreakdown(),
            'crime_type_breakdown' => $this->dataCrimeTypeBreakdown(),
            'admissions_by_month' => $this->dataAdmissionsByMonth(),
            default => [null, null, null],
        };

        if ($labels === null) {
            return null;
        }

        $chartConfig = [
            'type' => $chartType,
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => $title,
                    'data' => $values,
                ]],
            ],
            'options' => [
                'plugins' => [
                    'title' => ['display' => true, 'text' => $title],
                ],
            ],
        ];

        $response = Http::timeout(30)->post('https://quickchart.io/chart', [
            'chart' => $chartConfig,
            'width' => 600,
            'height' => 400,
            'format' => 'png',
        ]);

        if ($response->failed()) {
            return null;
        }

        $filename = 'chart-' . Str::slug($report) . '-' . time() . '.png';
        $path = "{$this->outputDir}/{$filename}";
        Storage::disk('public')->put($path, $response->body());

        return [
            'type' => 'image',
            'filename' => $filename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    /** Example predefined report queries. Adjust field/table names to match your schema. */
    protected function dataStatusBreakdown(): array
    {
        $rows = Inmate::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [$rows->keys()->toArray(), $rows->values()->toArray(), 'Inmates by Status'];
    }

    protected function dataCrimeTypeBreakdown(): array
    {
        // Adjust to your actual InmateCrime model/relationship.
        $rows = \App\Models\InmateCrime::selectRaw('crime_type, count(*) as total')
            ->groupBy('crime_type')
            ->pluck('total', 'crime_type');

        return [$rows->keys()->toArray(), $rows->values()->toArray(), 'Inmates by Crime Type'];
    }

    protected function dataAdmissionsByMonth(): array
    {
        $rows = Inmate::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return [$rows->keys()->toArray(), $rows->values()->toArray(), 'Admissions by Month'];
    }
}
