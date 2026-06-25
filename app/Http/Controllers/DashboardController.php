<?php

namespace App\Http\Controllers;

use App\Domain\Dashboard\BuildDashboardOverview;
use App\Domain\Dashboard\BuildLatestUploadSummary;
use App\Models\PinnedBiomarker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(
        BuildDashboardOverview $buildDashboardOverview,
        BuildLatestUploadSummary $buildLatestUploadSummary,
    ): View {
        $user = Auth::user();

        return view('dashboard', [
            'dashboardOverview' => $buildDashboardOverview($user),
            'latestUploadSummary' => $buildLatestUploadSummary($user),
            'recentBloodTests' => $user->bloodTests()
                ->recentFirst()
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
        ]);
    }
}
