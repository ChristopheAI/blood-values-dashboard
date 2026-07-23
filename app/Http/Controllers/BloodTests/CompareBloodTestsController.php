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
        if (! $request->filled(['first', 'second'])) {
            return redirect()
                ->route('blood-tests.index')
                ->with('compare_error', 'Kies twee bloedtesten om te vergelijken.');
        }

        if ((string) $request->input('first') === (string) $request->input('second')) {
            return redirect()
                ->route('blood-tests.index')
                ->with('compare_error', 'Kies twee verschillende bloedtesten om te vergelijken.');
        }

        $validated = $request->validate([
            'first' => ['required', 'integer'],
            'second' => ['required', 'integer'],
        ]);

        $first = BloodTest::query()
            ->where('user_id', Auth::id())
            ->whereKey((int) $validated['first'])
            ->firstOrFail();
        $second = BloodTest::query()
            ->where('user_id', Auth::id())
            ->whereKey((int) $validated['second'])
            ->firstOrFail();

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
