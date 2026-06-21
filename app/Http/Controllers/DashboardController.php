<?php

namespace App\Http\Controllers;

use App\Domain\Dashboard\BuildLatestUploadSummary;
use App\Models\PinnedBiomarker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(BuildLatestUploadSummary $buildLatestUploadSummary): View
    {
        $user = Auth::user();

        return view('dashboard', [
            'latestUploadSummary' => $buildLatestUploadSummary($user),
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
        ]);
    }
}
