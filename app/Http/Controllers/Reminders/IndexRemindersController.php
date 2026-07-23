<?php

namespace App\Http\Controllers\Reminders;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class IndexRemindersController extends Controller
{
    public function __invoke(): View
    {
        return view('reminders.index', [
            'openReminders' => Reminder::query()
                ->where('user_id', Auth::id())
                ->open()
                ->orderBy('due_date')
                ->orderBy('id')
                ->paginate(15, ['*'], 'open_page'),
            'completedReminders' => Reminder::query()
                ->where('user_id', Auth::id())
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->paginate(15, ['*'], 'completed_page'),
        ]);
    }
}
