<?php

namespace App\Http\Controllers\ContextNotes;

use App\Http\Controllers\Controller;
use App\Models\ContextNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DestroyContextNoteController extends Controller
{
    public function __invoke(string $contextNote): RedirectResponse
    {
        $contextNote = ContextNote::query()
            ->where('user_id', Auth::id())
            ->whereKey($contextNote)
            ->firstOrFail();

        $contextNote->delete();

        return redirect()->route('context-notes.index');
    }
}
