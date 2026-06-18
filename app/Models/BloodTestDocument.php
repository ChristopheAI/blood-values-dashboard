<?php

namespace App\Models;

use Database\Factories\BloodTestDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $blood_test_id
 * @property string $original_filename
 * @property string $storage_disk
 * @property string $storage_path
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['blood_test_id', 'original_filename', 'storage_disk', 'storage_path', 'mime_type', 'file_size'])]
class BloodTestDocument extends Model
{
    /** @use HasFactory<BloodTestDocumentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<BloodTest, $this>
     */
    public function bloodTest(): BelongsTo
    {
        return $this->belongsTo(BloodTest::class);
    }
}
