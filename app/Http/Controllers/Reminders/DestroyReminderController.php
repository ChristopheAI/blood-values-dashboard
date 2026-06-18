<?php

namespace App\Http\Controllers\Reminders;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DestroyReminderController extends Controller
{
    public function __invoke(Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->user_id === Auth::id(), 403);

        $reminder->delete();

        return redirect()->route('reminders.index');
    }
}
