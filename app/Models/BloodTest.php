<?php

namespace App\Models;

use Database\Factories\BloodTestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon|null $test_date
 * @property string|null $lab_name
 * @property string|null $title
 * @property string|null $notes
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'test_date', 'lab_name', 'title', 'notes', 'status'])]
class BloodTest extends Model
{
    /** @use HasFactory<BloodTestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'test_date' => 'date',
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
     * @return HasMany<BloodTestDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(BloodTestDocument::class);
    }

    /**
     * @return HasMany<BiomarkerResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(BiomarkerResult::class);
    }

    /**
     * @return HasMany<BiomarkerResult, $this>
     */
    public function confirmedResults(): HasMany
    {
        return $this->results()->whereNotNull('confirmed_at');
    }
}
