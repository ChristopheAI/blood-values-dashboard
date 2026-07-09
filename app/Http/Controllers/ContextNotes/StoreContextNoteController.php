<?php

namespace App\Http\Controllers\ContextNotes;

use App\Enums\ContextNoteCategory;
use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use App\Models\ContextNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreContextNoteController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'blood_test_id' => ['nullable', 'integer'],
            'note_date' => ['required', 'date'],
            'category' => ['required', Rule::enum(ContextNoteCategory::class)],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ContextNote::create([
            'user_id' => Auth::id(),
            'blood_test_id' => $this->ownedBloodTestId($validated['blood_test_id'] ?? null),
            'note_date' => $validated['note_date'],
            'category' => $validated['category'],
            'body' => $validated['body'],
        ]);

        return redirect()->route('context-notes.index');
    }

    private function ownedBloodTestId(mixed $bloodTestId): ?int
    {
        if ($bloodTestId === null || $bloodTestId === '') {
            return null;
        }

        $bloodTest = BloodTest::query()
            ->where('user_id', Auth::id())
            ->whereKey((int) $bloodTestId)
            ->firstOrFail();

        return $bloodTest->id;
    }
}
