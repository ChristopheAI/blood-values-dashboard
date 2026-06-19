<?php

namespace App\Http\Controllers\BloodTests;

use App\Http\Controllers\Controller;
use App\Models\BloodTestDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DestroyBloodTestDocumentController extends Controller
{
    public function __invoke(BloodTestDocument $bloodTestDocument): RedirectResponse
    {
        $bloodTest = $bloodTestDocument->bloodTest;

        abort_unless($bloodTest->user_id === Auth::id(), 403);

        $storageDisk = $bloodTestDocument->storage_disk;
        $storagePath = $bloodTestDocument->storage_path;

        DB::transaction(function () use ($bloodTest, $bloodTestDocument, $storageDisk, $storagePath): void {
            $bloodTestDocument->delete();
            $bloodTest->results()
                ->where('entry_source', 'extracted')
                ->whereNotNull('source_snippet')
                ->update(['source_snippet' => null]);

            if (! Storage::disk($storageDisk)->delete($storagePath)) {
                throw new RuntimeException('Failed to delete stored lab PDF.');
            }
        });

        return redirect()->route('blood-tests.show', $bloodTest);
    }
}
