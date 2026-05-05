<?php

namespace App\Models;

use App\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampVerificationEntry extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'camp_verification_point_id',
        'camp_payment_id',
        'user_id',
        'verified_by',
        'method',
        'verified_at',
        'user_name',
        'user_email',
        'user_phone',
        'user_role_name',
        'team_name',
        'room_name',
        'sex_label',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => VerificationMethod::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CampVerificationPoint, $this>
     */
    public function point(): BelongsTo
    {
        return $this->belongsTo(CampVerificationPoint::class, 'camp_verification_point_id');
    }

    /**
     * @return BelongsTo<CampPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(CampPayment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
