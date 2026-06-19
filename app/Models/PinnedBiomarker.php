<?php

namespace App\Models;

use Database\Factories\PinnedBiomarkerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $biomarker_id
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'biomarker_id', 'note'])]
class PinnedBiomarker extends Model
{
    /** @use HasFactory<PinnedBiomarkerFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Biomarker, $this>
     */
    public function biomarker(): BelongsTo
    {
        return $this->belongsTo(Biomarker::class);
    }

    /**
     * @param  Builder<PinnedBiomarker>  $query
     * @return Builder<PinnedBiomarker>
     */
    public function scopeForUserWithOwnedBiomarker(Builder $query, int $userId): Builder
    {
        return $query
            ->where('pinned_biomarkers.user_id', $userId)
            ->whereHas('biomarker', fn ($query) => $query->where('user_id', $userId));
    }
}
