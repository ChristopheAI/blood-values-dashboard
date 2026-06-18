<?php

namespace App\Models;

use Database\Factories\BiomarkerResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $blood_test_id
 * @property int $biomarker_id
 * @property string $value
 * @property string $unit
 * @property string|null $reference_min
 * @property string|null $reference_max
 * @property string|null $reference_unit
 * @property string $status
 * @property string $entry_source
 * @property Carbon|null $confirmed_at
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'blood_test_id',
    'biomarker_id',
    'value',
    'unit',
    'reference_min',
    'reference_max',
    'reference_unit',
    'status',
    'entry_source',
    'confirmed_at',
    'note',
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
     * @return BelongsTo<Biomarker, $this>
     */
    public function biomarker(): BelongsTo
    {
        return $this->belongsTo(Biomarker::class);
    }
}
