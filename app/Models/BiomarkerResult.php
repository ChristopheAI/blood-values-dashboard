<?php

namespace App\Models;

use Database\Factories\BiomarkerResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $blood_test_id
 * @property int|null $blood_test_document_id
 * @property int|null $biomarker_id
 * @property string|null $extracted_name
 * @property string $value
 * @property string|null $value_comparator
 * @property string $unit
 * @property string|null $reference_min
 * @property string|null $reference_max
 * @property string|null $reference_unit
 * @property string $status
 * @property string $entry_source
 * @property Carbon|null $confirmed_at
 * @property string|null $note
 * @property string|null $extraction_confidence
 * @property string|null $source_snippet
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'blood_test_id',
    'blood_test_document_id',
    'biomarker_id',
    'extracted_name',
    'value',
    'value_comparator',
    'unit',
    'reference_min',
    'reference_max',
    'reference_unit',
    'status',
    'entry_source',
    'confirmed_at',
    'note',
    'extraction_confidence',
    'source_snippet',
])]
class BiomarkerResult extends Model
{
    /** @use HasFactory<BiomarkerResultFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BloodTest, $this>
     */
    public function bloodTest(): BelongsTo
    {
        return $this->belongsTo(BloodTest::class);
    }

    /**
     * @return BelongsTo<BloodTestDocument, $this>
     */
    public function bloodTestDocument(): BelongsTo
    {
        return $this->belongsTo(BloodTestDocument::class);
    }

    /**
     * @return BelongsTo<Biomarker, $this>
     */
    public function biomarker(): BelongsTo
    {
        return $this->belongsTo(Biomarker::class);
    }

    /**
     * @param  Builder<BiomarkerResult>  $query
     * @return Builder<BiomarkerResult>
     */
    public function scopeConfirmedForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->whereNotNull('biomarker_results.confirmed_at')
            ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $userId))
            ->whereHas('biomarker', fn ($query) => $query->where('user_id', $userId));
    }
}
