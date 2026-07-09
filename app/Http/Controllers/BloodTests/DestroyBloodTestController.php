<?php

namespace App\Http\Controllers\BloodTests;

use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class DestroyBloodTestController extends Controller
{
    public function __invoke(Request $request, string $bloodTest): RedirectResponse
    {
        $bloodTest = BloodTest::query()
            ->where('user_id', Auth::id())
            ->whereKey($bloodTest)
            ->firstOrFail();

        $request->validate([
            'confirmation' => ['required', 'string', Rule::in(['DELETE TEST'])],
        ]);

        $documents = $bloodTest->documents()
            ->get(['id', 'storage_disk', 'storage_path']);

        // Delete the private PDFs inside the transaction, after the database delete: a
        // file-delete failure rolls the row deletes back, so a record is never left
        // without its file and a file is never orphaned without its record. With
        // several documents a later-file failure leaves earlier files deleted while
        // the rows are restored — a recoverable orphaned reference, healed by retry.
        DB::transaction(function () use ($bloodTest, $documents): void {
            $bloodTest->delete();

            foreach ($documents as $document) {
                if (! Storage::disk($document->storage_disk)->delete($document->storage_path)) {
                    throw new RuntimeException('Failed to delete stored lab PDF.');
                }
            }
        });

        return redirect()->route('blood-tests.index');
    }
}
