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

            // A storage operation cannot join the database transaction. A failure on a
            // later file restores every row above, but an earlier file can already be
            // absent. Surface that failure and keep the metadata so a retry can finish
            // safely: deleting an already-removed path is idempotent.
            foreach ($documents as $document) {
                $disk = Storage::disk($document->storage_disk);

                if ($disk->exists($document->storage_path) && ! $disk->delete($document->storage_path)) {
                    throw new RuntimeException('Failed to delete stored lab PDF.');
                }
            }
        });
    }
}
