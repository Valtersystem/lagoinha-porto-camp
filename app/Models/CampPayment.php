<?php

namespace App\Models;

use App\Enums\CampPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampPayment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'camp_id',
        'user_id',
        'camp_lot_id',
        'camp_room_id',
        'camp_team_id',
        'amount_cents',
        'installments_count',
        'paid_installments',
        'amount_paid_cents',
        'status',
        'paid_at',
        'exempted_at',
        'exempted_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'camp_room_id' => 'integer',
            'camp_team_id' => 'integer',
            'installments_count' => 'integer',
            'paid_installments' => 'integer',
            'amount_paid_cents' => 'integer',
            'status' => CampPaymentStatus::class,
            'paid_at' => 'datetime',
            'exempted_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CampLot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(CampLot::class, 'camp_lot_id');
    }

    /**
     * @return BelongsTo<CampRoom, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(CampRoom::class, 'camp_room_id');
    }

    /**
     * @return BelongsTo<CampTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(CampTeam::class, 'camp_team_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function exemptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exempted_by');
    }

    /**
     * @return HasMany<CampVerificationEntry, $this>
     */
    public function verificationEntries(): HasMany
    {
        return $this->hasMany(CampVerificationEntry::class);
    }
}
