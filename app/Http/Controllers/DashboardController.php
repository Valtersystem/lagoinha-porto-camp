<?php

namespace App\Http\Controllers;

use App\Enums\CampPaymentStatus;
use App\Enums\VerificationPointType;
use App\Models\Camp;
use App\Models\CampPayment;
use App\Models\CampVerificationEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $user->loadMissing('role');

        $currentParticipation = $user->campPayments()
            ->with(['camp', 'room', 'team', 'lot'])
            ->join('camps', 'camp_payments.camp_id', '=', 'camps.id')
            ->orderByDesc('camps.is_active')
            ->orderByDesc('camps.starts_on')
            ->select('camp_payments.*')
            ->first();

        return Inertia::render('Dashboard', [
            'isAdministrator' => $user->isAdministrator(),
            'participant' => [
                'user' => $this->serializeUser($user),
                'participation' => $currentParticipation
                    ? $this->serializeParticipation($currentParticipation)
                    : null,
                'operationStatus' => [
                    'presence' => $this->latestVerificationLabel($currentParticipation, VerificationPointType::Presence),
                    'check_in' => $this->latestVerificationLabel($currentParticipation, VerificationPointType::CheckIn),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'photo_url' => $user->photo_path ? asset('storage/'.$user->photo_path) : null,
            'pin' => $user->pin,
            'verification_code' => $user->verification_code,
            'sex' => $user->sex?->value,
            'sex_label' => $user->sex?->label(),
            'role' => $user->role ? [
                'id' => $user->role->id,
                'key' => $user->role->key,
                'name' => $user->role->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeParticipation(CampPayment $payment): array
    {
        $amountOpenCents = $payment->status === CampPaymentStatus::Exempted
            ? 0
            : max($payment->amount_cents - $payment->amount_paid_cents, 0);

        return [
            'id' => $payment->id,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'amount_cents' => $payment->amount_cents,
            'amount' => $this->moneyFromCents($payment->amount_cents),
            'amount_paid_cents' => $payment->amount_paid_cents,
            'amount_paid' => $this->moneyFromCents($payment->amount_paid_cents),
            'amount_open_cents' => $amountOpenCents,
            'amount_open' => $this->moneyFromCents($amountOpenCents),
            'installments_count' => $payment->installments_count,
            'paid_installments' => $payment->paid_installments,
            'notes' => $payment->notes,
            'camp' => [
                'id' => $payment->camp->id,
                'name' => $payment->camp->name,
                'address' => $payment->camp->address,
                'is_active' => $payment->camp->is_active,
                'date_range_label' => $this->campDateRangeLabel($payment->camp),
            ],
            'room' => $payment->room ? [
                'id' => $payment->room->id,
                'name' => $payment->room->name,
            ] : null,
            'team' => $payment->team ? [
                'id' => $payment->team->id,
                'name' => $payment->team->name,
            ] : null,
            'lot' => $payment->lot ? [
                'id' => $payment->lot->id,
                'name' => $payment->lot->name,
            ] : null,
        ];
    }

    private function campDateRangeLabel(Camp $camp): ?string
    {
        if ($camp->starts_on === null) {
            return null;
        }

        if ($camp->ends_on === null || $camp->starts_on->isSameDay($camp->ends_on)) {
            return $camp->starts_on->format('d/m/Y');
        }

        return $camp->starts_on->format('d/m/Y').' a '.$camp->ends_on->format('d/m/Y');
    }

    private function latestVerificationLabel(?CampPayment $payment, VerificationPointType $type): string
    {
        if ($payment === null) {
            return 'Sem registo';
        }

        $entry = CampVerificationEntry::query()
            ->where('camp_payment_id', $payment->id)
            ->whereHas('point', fn ($query) => $query->where('type', $type->value))
            ->latest('verified_at')
            ->first();

        if ($entry === null) {
            return 'Sem registo';
        }

        return $entry->verified_at?->format('d/m/Y H:i') ?? 'Sem registo';
    }

    private function moneyFromCents(int $value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
}
