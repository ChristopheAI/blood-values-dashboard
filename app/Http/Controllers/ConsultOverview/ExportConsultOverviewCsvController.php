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
        $rows = [['section', 'date', 'biomarker', 'value', 'unit', 'status', 'note']];

        foreach ($overview['pinnedBiomarkers'] as $pin) {
            $rows[] = ['pinned', '', $pin->biomarker->name, '', '', '', $pin->note ?? ''];
        }

        foreach ($overview['attentionResults'] as $result) {
            $rows[] = [
                'attention',
                $result->bloodTest->test_date?->toDateString() ?? '',
                $result->biomarker->name,
                (string) (float) $result->value,
                $result->unit,
                $result->status,
                $result->note ?? '',
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
                $result->note ?? '',
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
                $note->body,
            ];
        }

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to create CSV export.');
        }

        foreach ($rows as $row) {
            fputcsv($handle, $row);
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
     * @return array{
     *     from: string|null,
     *     to: string|null,
     *     blood_test_ids: list<int>,
     *     include_pinned: bool,
     *     include_attention: bool,
     *     include_trends: bool,
     *     include_context: bool,
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
            'include_trends' => ['nullable', 'boolean'],
            'include_context' => ['nullable', 'boolean'],
            'questions' => ['nullable', 'string', 'max:5000'],
        ]);

        return [
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'blood_test_ids' => array_values(array_map('intval', $validated['blood_test_ids'] ?? [])),
            'include_pinned' => $request->boolean('include_pinned'),
            'include_attention' => $request->boolean('include_attention'),
            'include_trends' => $request->boolean('include_trends'),
            'include_context' => $request->boolean('include_context'),
            'questions' => $validated['questions'] ?? null,
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
