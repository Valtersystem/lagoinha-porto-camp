<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserSex;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

#[Fillable(['name', 'email', 'phone', 'photo_path', 'pin', 'verification_code', 'sex', 'password', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'sex' => UserSex::class,
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if (blank($user->pin)) {
                $user->pin = self::generateUniquePin();
            }

            if (blank($user->verification_code)) {
                $user->verification_code = self::generateUniqueVerificationCode();
            }
        });

        static::deleting(function (self $user): void {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
        });
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasMany<CampPayment, $this>
     */
    public function campPayments(): HasMany
    {
        return $this->hasMany(CampPayment::class);
    }

    /**
     * @return HasMany<CampVerificationEntry, $this>
     */
    public function verificationEntries(): HasMany
    {
        return $this->hasMany(CampVerificationEntry::class);
    }

    public function hasRole(UserRole|string $role): bool
    {
        $roleKey = $role instanceof UserRole ? $role->value : $role;

        return $this->role?->key === $roleKey;
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole(UserRole::Administrator);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public static function generateUniquePin(): string
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! self::query()->where('pin', $pin)->exists()) {
                return $pin;
            }
        }

        throw new RuntimeException('Nao foi possivel gerar um PIN unico para o usuario.');
    }

    public static function generateUniqueVerificationCode(): string
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $code = 'USR-'.Str::upper(Str::random(10));

            if (! self::query()->where('verification_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Nao foi possivel gerar um codigo unico para o usuario.');
    }
}
