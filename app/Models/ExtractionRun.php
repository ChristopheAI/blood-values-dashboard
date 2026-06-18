<?php

namespace App\Models;

use Database\Factories\ExtractionRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $blood_test_id
 * @property string $engine
 * @property string $status
 * @property int $candidate_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['blood_test_id', 'engine', 'status', 'candidate_count'])]
class ExtractionRun extends Model
{
    /** @use HasFactory<ExtractionRunFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<BloodTest, $this>
     */
    public function bloodTest(): BelongsTo
    {
        return $this->belongsTo(BloodTest::class);
    }
}
