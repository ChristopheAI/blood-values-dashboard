<?php

namespace App\Http\Controllers;

use App\Models\BiomarkerResult;
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
            'pinnedBiomarkers' => $user->pinnedBiomarkers()
                ->with('biomarker')
                ->latest()
                ->get(),
            'attentionResults' => BiomarkerResult::query()
                ->whereNotNull('confirmed_at')
                ->whereIn('status', ['low', 'high', 'unknown'])
                ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $user->id))
                ->with(['biomarker', 'bloodTest'])
                ->latest('confirmed_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
