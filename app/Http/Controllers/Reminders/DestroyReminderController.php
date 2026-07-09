<?php

namespace App\Http\Controllers\Reminders;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DestroyReminderController extends Controller
{
    public function __invoke(string $reminder): RedirectResponse
    {
        $reminder = Reminder::query()
            ->where('user_id', Auth::id())
            ->whereKey($reminder)
            ->firstOrFail();

        $reminder->delete();

        return redirect()->route('reminders.index');
    }
}
