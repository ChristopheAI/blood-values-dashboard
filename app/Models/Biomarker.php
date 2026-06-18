<?php

namespace App\Models;

use Database\Factories\BiomarkerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $biomarker_category_id
 * @property string $name
 * @property string|null $short_name
 * @property string|null $default_unit
 * @property string|null $reference_min
 * @property string|null $reference_max
 * @property string|null $reference_unit
 * @property string|null $range_note
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'biomarker_category_id',
    'name',
    'short_name',
    'default_unit',
    'reference_min',
    'reference_max',
    'reference_unit',
    'range_note',
    'active',
])]
class Biomarker extends Model
{
    /** @use HasFactory<BiomarkerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BiomarkerCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BiomarkerCategory::class, 'biomarker_category_id');
    }

    /**
     * @return HasMany<BiomarkerResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(BiomarkerResult::class);
    }

    /**
     * @return HasMany<PinnedBiomarker, $this>
     */
    public function pins(): HasMany
    {
        return $this->hasMany(PinnedBiomarker::class);
    }
}
