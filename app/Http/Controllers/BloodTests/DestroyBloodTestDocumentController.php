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

        // Clear snippets before the row delete so FK nullOnDelete does not skip the
        // update, then delete the PDF inside the transaction so a file failure rolls
        // the database delete back and the record and file stay consistent.
        DB::transaction(function () use ($bloodTest, $bloodTestDocument, $storageDisk, $storagePath): void {
            $bloodTest->newQuery()->whereKey($bloodTest->id)->lockForUpdate()->first();

            $clearsLegacyNullSnippets = $bloodTest->documents()
                ->whereKeyNot($bloodTestDocument->id)
                ->doesntExist();

            $bloodTest->results()
                ->where('entry_source', 'extracted')
                ->whereNotNull('source_snippet')
                ->where(function ($query) use ($bloodTestDocument, $clearsLegacyNullSnippets): void {
                    $query->where('blood_test_document_id', $bloodTestDocument->id);

                    if ($clearsLegacyNullSnippets) {
                        $query->orWhereNull('blood_test_document_id');
                    }
                })
                ->update(['source_snippet' => null]);

            $bloodTestDocument->delete();

            if (! Storage::disk($storageDisk)->delete($storagePath)) {
                throw new RuntimeException('Failed to delete stored lab PDF.');
            }
        });

        return redirect()->route('blood-tests.show', $bloodTest);
    }
}
