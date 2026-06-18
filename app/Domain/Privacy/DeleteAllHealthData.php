<?php

namespace App\Domain\Privacy;

use App\Models\Biomarker;
use App\Models\BiomarkerCategory;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\ContextNote;
use App\Models\PinnedBiomarker;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAllHealthData
{
    public function __invoke(User $user): void
    {
        $documents = BloodTestDocument::query()
            ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $user->id))
            ->get(['id', 'storage_disk', 'storage_path']);

        foreach ($documents as $document) {
            Storage::disk($document->storage_disk)->delete($document->storage_path);
        }

        DB::transaction(function () use ($user): void {
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

            Biomarker::query()
                ->where('user_id', $user->id)
                ->delete();

            BiomarkerCategory::query()
                ->where('user_id', $user->id)
                ->delete();
        });
    }
}
