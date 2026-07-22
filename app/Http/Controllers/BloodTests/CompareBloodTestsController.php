<?php

namespace App\Http\Controllers\BloodTests;

use App\Domain\BloodTests\CompareBloodTests;
use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompareBloodTestsController extends Controller
{
    public function __invoke(Request $request, CompareBloodTests $compareBloodTests): View|RedirectResponse
    {
        $validated = $request->validate([
            'first' => ['required', 'integer'],
            'second' => ['required', 'integer'],
        ]);

        $first = BloodTest::query()->whereKey((int) $validated['first'])->firstOrFail();
        $second = BloodTest::query()->whereKey((int) $validated['second'])->firstOrFail();

        abort_unless($first->user_id === Auth::id() && $second->user_id === Auth::id(), 403);

        if ($first->is($second)) {
            return redirect()
                ->route('blood-tests.index')
                ->withInput()
                ->withErrors(['comparison' => __('Kies twee verschillende bloedtesten.')]);
        }

        return view('blood-tests.compare', [
            'first' => $first,
            'second' => $second,
            'rows' => $compareBloodTests($first, $second),
        ]);
    }
}
