<?php

namespace App\Models;

use App\Enums\VerificationPointType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use RuntimeException;

class CampVerificationPoint extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'camp_id',
        'name',
        'type',
        'point_code',
        'notes',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VerificationPointType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $point): void {
            if (blank($point->point_code)) {
                $point->point_code = self::generateUniquePointCode();
            }
        });
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * @return HasMany<CampVerificationEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CampVerificationEntry::class)->orderByDesc('verified_at');
    }

    public static function generateUniquePointCode(): string
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $code = 'PNT-'.Str::upper(Str::random(8));

            if (! self::query()->where('point_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Nao foi possivel gerar um codigo unico para o ponto de verificacao.');
    }
}
