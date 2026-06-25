<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * @return HasMany<BloodTest, $this>
     */
    public function bloodTests(): HasMany
    {
        return $this->hasMany(BloodTest::class);
    }

    /**
     * @return Collection<int, BloodTest>
     */
    public function bloodTestsUpToAndIncluding(BloodTest $bloodTest): Collection
    {
        if ((int) $bloodTest->user_id !== $this->id) {
            return collect();
        }

        $query = $this->bloodTests();

        if ($bloodTest->test_date === null) {
            return $query
                ->where('id', '<=', $bloodTest->id)
                ->get();
        }

        return $query
            ->where(function ($query) use ($bloodTest): void {
                $query->where(function ($query) use ($bloodTest): void {
                    $query
                        ->whereNotNull('test_date')
                        ->whereDate('test_date', '<', $bloodTest->test_date);
                })->orWhere(function ($query) use ($bloodTest): void {
                    $query
                        ->whereDate('test_date', $bloodTest->test_date)
                        ->where('id', '<=', $bloodTest->id);
                });
            })
            ->get();
    }

    /**
     * @return HasMany<Biomarker, $this>
     */
    public function biomarkers(): HasMany
    {
        return $this->hasMany(Biomarker::class);
    }

    /**
     * @return HasMany<PinnedBiomarker, $this>
     */
    public function pinnedBiomarkers(): HasMany
    {
        return $this->hasMany(PinnedBiomarker::class);
    }

    /**
     * @return HasMany<ContextNote, $this>
     */
    public function contextNotes(): HasMany
    {
        return $this->hasMany(ContextNote::class);
    }

    /**
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }
}
