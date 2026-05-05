<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camp extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'address',
        'starts_on',
        'ends_on',
        'amount_cents',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'amount_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CampLot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(CampLot::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CampPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CampPayment::class);
    }

    /**
     * @return HasMany<CampRoom, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(CampRoom::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CampTeam, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(CampTeam::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CampVerificationPoint, $this>
     */
    public function verificationPoints(): HasMany
    {
        return $this->hasMany(CampVerificationPoint::class)->orderBy('sort_order');
    }
}
