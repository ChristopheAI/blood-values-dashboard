<?php

namespace App\Http\Controllers\BloodTests;

use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DestroyBloodTestController extends Controller
{
    public function __invoke(BloodTest $bloodTest): RedirectResponse
    {
        abort_unless($bloodTest->user_id === Auth::id(), 403);

        $documents = $bloodTest->documents()
            ->get(['id', 'storage_disk', 'storage_path']);

        $bloodTest->delete();

        foreach ($documents as $document) {
            Storage::disk($document->storage_disk)->delete($document->storage_path);
        }

        return redirect()->route('blood-tests.index');
    }
}
