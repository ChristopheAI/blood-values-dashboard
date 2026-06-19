<?php

namespace App\Http\Controllers\BloodTests;

use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DestroyBloodTestController extends Controller
{
    public function __invoke(BloodTest $bloodTest): RedirectResponse
    {
        abort_unless($bloodTest->user_id === Auth::id(), 403);

        $documents = $bloodTest->documents()
            ->get(['id', 'storage_disk', 'storage_path']);

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
