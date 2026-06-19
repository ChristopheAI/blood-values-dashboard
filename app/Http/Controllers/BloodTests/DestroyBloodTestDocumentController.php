<?php

namespace App\Http\Controllers\BloodTests;

use App\Http\Controllers\Controller;
use App\Models\BloodTestDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DestroyBloodTestDocumentController extends Controller
{
    public function __invoke(BloodTestDocument $bloodTestDocument): RedirectResponse
    {
        $bloodTest = $bloodTestDocument->bloodTest;

        abort_unless($bloodTest->user_id === Auth::id(), 403);

        $storageDisk = $bloodTestDocument->storage_disk;
        $storagePath = $bloodTestDocument->storage_path;

        $bloodTestDocument->delete();

        Storage::disk($storageDisk)->delete($storagePath);

        return redirect()->route('blood-tests.show', $bloodTest);
    }
}
