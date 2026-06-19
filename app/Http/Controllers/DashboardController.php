<?php

namespace App\Http\Controllers;

use App\Models\BiomarkerResult;
use App\Models\PinnedBiomarker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        return view('dashboard', [
            'recentBloodTests' => $user->bloodTests()
                ->latest('test_date')
                ->limit(5)
                ->get(),
            'pinnedBiomarkers' => PinnedBiomarker::query()
                ->forUserWithOwnedBiomarker($user->id)
                ->with('biomarker')
                ->latest()
                ->get(),
            'nextReminder' => $user->reminders()
                ->open()
                ->orderBy('due_date')
                ->orderBy('id')
                ->first(),
            'attentionResults' => BiomarkerResult::query()
                ->confirmedForUser($user->id)
                ->whereIn('status', ['low', 'high', 'unknown'])
                ->with(['biomarker', 'bloodTest'])
                ->latest('confirmed_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
