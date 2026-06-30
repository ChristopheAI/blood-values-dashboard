<?php

namespace App\Http\Controllers\Biomarkers;

use App\Http\Controllers\Controller;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ShowBiomarkerController extends Controller
{
    public function __invoke(Biomarker $biomarker): View
    {
        abort_unless($biomarker->user_id === Auth::id(), 403);

        $results = BiomarkerResult::query()
            ->confirmedForUser(Auth::id())
            ->where('biomarker_id', $biomarker->id)
            ->with('bloodTest')
            ->get()
            ->sortBy(fn ($result) => $result->bloodTest->test_date?->toDateString() ?? '');

        return view('biomarkers.show', [
            'biomarker' => $biomarker,
            'pin' => $biomarker->pins()->where('user_id', Auth::id())->first(),
            'results' => $results,
        ]);
    }
}
