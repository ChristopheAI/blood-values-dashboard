<?php

namespace App\Http\Controllers\BloodTests;

use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateBloodTestController extends Controller
{
    public function __invoke(Request $request, BloodTest $bloodTest): RedirectResponse
    {
        abort_unless($bloodTest->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'test_date' => ['nullable', 'date'],
            'lab_name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $bloodTest->update([
            'test_date' => $validated['test_date'] ?? null,
            'lab_name' => $this->nullableString($validated['lab_name'] ?? null),
            'title' => $this->nullableString($validated['title'] ?? null),
        ]);

        return redirect()->route('blood-tests.show', $bloodTest);
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
