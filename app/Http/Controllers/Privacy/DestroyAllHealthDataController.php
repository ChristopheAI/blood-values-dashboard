<?php

namespace App\Http\Controllers\Privacy;

use App\Domain\Privacy\DeleteAllHealthData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DestroyAllHealthDataController extends Controller
{
    public function __invoke(Request $request, DeleteAllHealthData $deleteAllHealthData): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'string', Rule::in(['DELETE ALL'])],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $deleteAllHealthData($user);

        return redirect()
            ->route('data.edit')
            ->with('status', 'health-data-deleted');
    }
}
