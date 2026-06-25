<?php

namespace App\Http\Controllers\ConsultOverview;

use App\Domain\Consult\BuildConsultOverview;
use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ExportConsultOverviewCsvController extends Controller
{
    public function __invoke(Request $request, BuildConsultOverview $buildConsultOverview): Response
    {
        $filters = $this->filters($request);
        $this->authorizeSelectedBloodTests($filters['blood_test_ids']);

        /** @var User $user */
        $user = Auth::user();
        $overview = $buildConsultOverview($user, $filters);
        $rows = [['section', 'date', 'biomarker', 'value', 'unit', 'status', 'source', 'confirmed_at', 'note']];

        foreach ($overview['pinnedBiomarkers'] as $pin) {
            $rows[] = ['pinned', '', $pin->biomarker->name, '', '', '', '', '', $pin->note ?? ''];
        }

        foreach ($overview['attentionResults'] as $result) {
            $rows[] = [
                'attention',
                $result->bloodTest->test_date?->toDateString() ?? '',
                $result->biomarker->name,
                (string) (float) $result->value,
                $result->unit,
                $result->status,
                $this->sourceLabel($result->bloodTest),
                $result->confirmed_at?->toDateString() ?? '',
                $result->note ?? '',
            ];
        }

        foreach ($overview['normalResults'] as $result) {
            $rows[] = [
                'normal',
                $result->bloodTest->test_date?->toDateString() ?? '',
                $result->biomarker->name,
                (string) (float) $result->value,
                $result->unit,
                $result->status,
                $this->sourceLabel($result->bloodTest),
                $result->confirmed_at?->toDateString() ?? '',
                $result->note ?? '',
            ];
        }

        foreach ($overview['trendChanges'] as $change) {
            $result = $change['result'];
            $previousResult = $change['previousResult'];

            $rows[] = [
                'change',
                $result->bloodTest->test_date?->toDateString() ?? '',
                $result->biomarker->name,
                (string) (float) $result->value,
                $result->unit,
                $result->status,
                $this->sourceLabel($result->bloodTest),
                $result->confirmed_at?->toDateString() ?? '',
                'previous '.(string) (float) $previousResult->value.' '.$previousResult->unit.'; change '.$change['changeLabel'],
            ];
        }

        foreach ($overview['trendResults'] as $result) {
            $rows[] = [
                'trend',
                $result->bloodTest->test_date?->toDateString() ?? '',
                $result->biomarker->name,
                (string) (float) $result->value,
                $result->unit,
                $result->status,
                $this->sourceLabel($result->bloodTest),
                $result->confirmed_at?->toDateString() ?? '',
                $result->note ?? '',
            ];
        }

        foreach ($overview['sourceDocuments'] as $document) {
            $rows[] = [
                'source_document',
                $document->bloodTest->test_date?->toDateString() ?? '',
                $document->original_filename,
                '',
                '',
                '',
                $this->sourceLabel($document->bloodTest),
                '',
                '',
            ];
        }

        foreach ($overview['contextNotes'] as $note) {
            $rows[] = [
                'context',
                $note->note_date->toDateString(),
                ucfirst($note->category->value),
                '',
                '',
                '',
                '',
                '',
                $note->body,
            ];
        }

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to create CSV export.');
        }

        foreach ($rows as $row) {
            fputcsv($handle, $this->escapeSpreadsheetFormulas($row), ',', '"', '\\');
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="consult-overview.csv"',
        ]);
    }

    /**
     * @param  list<string>  $row
     * @return list<string>
     */
    private function escapeSpreadsheetFormulas(array $row): array
    {
        return array_map(function (string $cell): string {
            return preg_match('/^\s*[=+\-@\t\r]/', $cell) === 1 ? "'".$cell : $cell;
        }, $row);
    }

    private function sourceLabel(BloodTest $bloodTest): string
    {
        return $bloodTest->title ?: __('Bloedtest zonder titel');
    }

    /**
     * @return array{
     *     from: string|null,
     *     to: string|null,
     *     blood_test_ids: list<int>,
     *     include_pinned: bool,
     *     include_attention: bool,
     *     include_normal: bool,
     *     include_trends: bool,
     *     include_context: bool,
     *     include_source_documents: bool,
     *     questions: string|null
     * }
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'blood_test_ids' => ['nullable', 'array'],
            'blood_test_ids.*' => ['integer'],
            'include_pinned' => ['nullable', 'boolean'],
            'include_attention' => ['nullable', 'boolean'],
            'include_normal' => ['nullable', 'boolean'],
            'include_trends' => ['nullable', 'boolean'],
            'include_context' => ['nullable', 'boolean'],
            'include_source_documents' => ['nullable', 'boolean'],
        ]);

        return [
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'blood_test_ids' => array_values(array_map('intval', $validated['blood_test_ids'] ?? [])),
            'include_pinned' => $request->boolean('include_pinned'),
            'include_attention' => $request->boolean('include_attention'),
            'include_normal' => $request->boolean('include_normal'),
            'include_trends' => $request->boolean('include_trends'),
            'include_context' => $request->boolean('include_context'),
            'include_source_documents' => $request->boolean('include_source_documents'),
            'questions' => null,
        ];
    }

    /**
     * @param  list<int>  $bloodTestIds
     */
    private function authorizeSelectedBloodTests(array $bloodTestIds): void
    {
        if ($bloodTestIds === []) {
            return;
        }

        $ownedCount = BloodTest::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $bloodTestIds)
            ->count();

        abort_unless($ownedCount === count(array_unique($bloodTestIds)), 403);
    }
}
