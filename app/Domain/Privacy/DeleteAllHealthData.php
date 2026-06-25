<?php

namespace App\Domain\Privacy;

use App\Models\Biomarker;
use App\Models\BiomarkerCategory;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\ContextNote;
use App\Models\PinnedBiomarker;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteAllHealthData
{
    public function __invoke(User $user): void
    {
        $documents = BloodTestDocument::query()
            ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $user->id))
            ->get(['id', 'storage_disk', 'storage_path']);

        DB::transaction(function () use ($user, $documents): void {
            $ownedBiomarkerIds = Biomarker::query()
                ->where('user_id', $user->id)
                ->pluck('id');

            ContextNote::query()
                ->where('user_id', $user->id)
                ->delete();

            Reminder::query()
                ->where('user_id', $user->id)
                ->delete();

            PinnedBiomarker::query()
                ->where('user_id', $user->id)
                ->delete();

            BloodTest::query()
                ->where('user_id', $user->id)
                ->delete();

            BiomarkerResult::query()
                ->whereIn('biomarker_id', $ownedBiomarkerIds)
                ->whereHas('bloodTest', fn ($query) => $query->where('user_id', '!=', $user->id))
                ->update(['biomarker_id' => null]);

            PinnedBiomarker::query()
                ->whereIn('biomarker_id', $ownedBiomarkerIds)
                ->where('user_id', '!=', $user->id)
                ->update(['biomarker_id' => null]);

            Biomarker::query()
                ->where('user_id', $user->id)
                ->delete();

            BiomarkerCategory::query()
                ->where('user_id', $user->id)
                ->delete();

            // Delete the private PDFs last, inside the transaction: a file-delete
            // failure rolls back every row above, so no health record is ever lost and
            // a stored PDF is never left behind with no record pointing at it (a leak).
            // The one residue, when a blood test has several documents, is that a
            // failure on a later file leaves earlier files already deleted while their
            // rows are restored — a recoverable orphaned reference, healed by retrying
            // the deletion (Storage::delete is idempotent for already-removed files).
            foreach ($documents as $document) {
                if (! Storage::disk($document->storage_disk)->delete($document->storage_path)) {
                    throw new RuntimeException('Failed to delete stored lab PDF.');
                }
            }
        });
    }
}
