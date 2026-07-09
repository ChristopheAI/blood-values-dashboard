<?php

namespace App\Http\Controllers\ContextNotes;

use App\Enums\ContextNoteCategory;
use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use App\Models\ContextNote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class IndexContextNotesController extends Controller
{
    public function __invoke(): View
    {
        return view('context-notes.index', [
            'categories' => ContextNoteCategory::cases(),
            'bloodTests' => BloodTest::query()
                ->where('user_id', Auth::id())
                ->latest('test_date')
                ->get(),
            'contextNotes' => ContextNote::query()
                ->where('user_id', Auth::id())
                ->with(['bloodTest' => fn ($query) => $query->where('user_id', Auth::id())])
                ->latest('note_date')
                ->paginate(15),
        ]);
    }
}
