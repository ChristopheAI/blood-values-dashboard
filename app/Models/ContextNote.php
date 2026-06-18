<?php

namespace App\Models;

use App\Enums\ContextNoteCategory;
use Database\Factories\ContextNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $blood_test_id
 * @property Carbon $note_date
 * @property ContextNoteCategory $category
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'blood_test_id', 'note_date', 'category', 'body'])]
class ContextNote extends Model
{
    /** @use HasFactory<ContextNoteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'note_date' => 'date',
            'category' => ContextNoteCategory::class,
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
     * @return BelongsTo<BloodTest, $this>
     */
    public function bloodTest(): BelongsTo
    {
        return $this->belongsTo(BloodTest::class);
    }
}
