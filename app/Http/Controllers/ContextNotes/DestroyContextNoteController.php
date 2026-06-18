<?php

namespace App\Http\Controllers\ContextNotes;

use App\Http\Controllers\Controller;
use App\Models\ContextNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DestroyContextNoteController extends Controller
{
    public function __invoke(ContextNote $contextNote): RedirectResponse
    {
        abort_unless($contextNote->user_id === Auth::id(), 403);

        $contextNote->delete();

        return redirect()->route('context-notes.index');
    }
}
