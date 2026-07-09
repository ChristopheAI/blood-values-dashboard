<?php

namespace App\Http\Controllers\BloodTests;

use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class IndexBloodTestsController extends Controller
{
    public function __invoke(): View
    {
        return view('blood-tests.index', [
            'bloodTests' => BloodTest::query()
                ->where('user_id', Auth::id())
                ->recentFirst()
                ->paginate(15),
        ]);
    }
}
