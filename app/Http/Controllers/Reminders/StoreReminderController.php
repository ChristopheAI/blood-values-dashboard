<?php

namespace App\Http\Controllers\Reminders;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreReminderController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        Reminder::create([
            'user_id' => Auth::id(),
            'due_date' => $validated['due_date'],
            'title' => $validated['title'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('reminders.index');
    }
}
