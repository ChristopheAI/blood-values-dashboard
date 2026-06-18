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

class UpdateContextNoteController extends Controller
{
    public function __invoke(Request $request, ContextNote $contextNote): RedirectResponse
    {
        abort_unless($contextNote->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'blood_test_id' => ['nullable', 'integer'],
            'note_date' => ['required', 'date'],
            'category' => ['required', Rule::enum(ContextNoteCategory::class)],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $contextNote->update([
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

        $bloodTest = BloodTest::query()->whereKey((int) $bloodTestId)->firstOrFail();

        abort_unless($bloodTest->user_id === Auth::id(), 403);

        return $bloodTest->id;
    }
}
