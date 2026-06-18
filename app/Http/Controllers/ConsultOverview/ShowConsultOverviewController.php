<?php

namespace App\Http\Controllers\ConsultOverview;

use App\Domain\Consult\BuildConsultOverview;
use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShowConsultOverviewController extends Controller
{
    public function __invoke(Request $request, BuildConsultOverview $buildConsultOverview): View
    {
        $filters = $this->filters($request);
        $this->authorizeSelectedBloodTests($filters['blood_test_ids']);

        /** @var User $user */
        $user = Auth::user();

        return view('consult-overview.index', [
            'availableBloodTests' => BloodTest::query()
                ->where('user_id', $user->id)
                ->latest('test_date')
                ->get(),
            'filters' => $filters,
            'overview' => $buildConsultOverview($user, $filters),
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
            'questions' => $request->isMethod('post') ? ($validated['questions'] ?? null) : null,
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
