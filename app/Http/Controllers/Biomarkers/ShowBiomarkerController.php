<?php

namespace App\Http\Controllers\Biomarkers;

use App\Http\Controllers\Controller;
use App\Models\Biomarker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ShowBiomarkerController extends Controller
{
    public function __invoke(Biomarker $biomarker): View
    {
        abort_unless($biomarker->user_id === Auth::id(), 403);

        $results = $biomarker->results()
            ->whereNotNull('confirmed_at')
            ->whereHas('bloodTest', fn ($query) => $query->where('user_id', Auth::id()))
            ->with('bloodTest')
            ->get()
            ->sortBy(fn ($result) => $result->bloodTest->test_date?->toDateString() ?? '');

        return view('biomarkers.show', [
            'biomarker' => $biomarker,
            'results' => $results,
        ]);
    }
}
