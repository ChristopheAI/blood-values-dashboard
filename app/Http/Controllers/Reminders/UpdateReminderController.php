<?php

namespace App\Http\Controllers\Reminders;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateReminderController extends Controller
{
    public function __invoke(Request $request, string $reminder): RedirectResponse
    {
        $reminder = Reminder::query()
            ->where('user_id', Auth::id())
            ->whereKey($reminder)
            ->firstOrFail();

        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'completed' => ['nullable', 'boolean'],
        ]);

        $reminder->update([
            'due_date' => $validated['due_date'],
            'title' => $validated['title'],
            'note' => $validated['note'] ?? null,
            'completed_at' => $request->boolean('completed') ? ($reminder->completed_at ?? now()) : null,
        ]);

        return redirect()->route('reminders.index');
    }
}
