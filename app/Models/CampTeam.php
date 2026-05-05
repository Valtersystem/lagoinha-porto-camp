<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampTeam extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'camp_id',
        'leader_camp_payment_id',
        'name',
        'notes',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'leader_camp_payment_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * @return BelongsTo<CampPayment, $this>
     */
    public function leaderPayment(): BelongsTo
    {
        return $this->belongsTo(CampPayment::class, 'leader_camp_payment_id');
    }

    /**
     * @return HasMany<CampPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CampPayment::class);
    }
}
