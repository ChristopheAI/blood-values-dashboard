<?php

namespace App\Http\Controllers\Biomarkers;

use App\Http\Controllers\Controller;
use App\Models\Biomarker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class UnpinBiomarkerController extends Controller
{
    public function __invoke(Biomarker $biomarker): RedirectResponse
    {
        abort_unless($biomarker->user_id === Auth::id(), 403);

        $biomarker->pins()
            ->where('user_id', Auth::id())
            ->delete();

        return redirect()->route('biomarkers.show', $biomarker);
    }
}
