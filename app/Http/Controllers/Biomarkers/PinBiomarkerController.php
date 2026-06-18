<?php

namespace App\Http\Controllers\Biomarkers;

use App\Http\Controllers\Controller;
use App\Models\Biomarker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PinBiomarkerController extends Controller
{
    public function __invoke(Request $request, Biomarker $biomarker): RedirectResponse
    {
        abort_unless($biomarker->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $biomarker->pins()->updateOrCreate(
            ['user_id' => Auth::id()],
            ['note' => $validated['note'] ?? null],
        );

        return redirect()->route('biomarkers.show', $biomarker);
    }
}
