<?php

namespace App\Http\Controllers;

use App\Enums\VerificationMethod;
use App\Enums\VerificationPointType;
use App\Models\Camp;
use App\Models\CampPayment;
use App\Models\CampVerificationEntry;
use App\Models\CampVerificationPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampVerificationPointController extends Controller
{
    public function index(Camp $camp): Response
    {
        Gate::authorize('manage-operations');

        $camp->load('lots');

        $linkedPeople = $camp->payments()->count();

        $points = CampVerificationPoint::query()
            ->whereBelongsTo($camp)
            ->withCount('entries')
            ->withMax('entries as last_verified_at', 'verified_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CampVerificationPoint $point): array => $this->serializePoint($point, $linkedPeople))
            ->all();

        $pointsCollection = collect($points);
        $totalVerifications = CampVerificationEntry::query()
            ->whereHas('point', fn ($query) => $query->whereBelongsTo($camp))
            ->count();

        return Inertia::render('Camps/VerificationPoints/Index', [
            'camp' => $this->serializeCamp($camp),
            'points' => $points,
            'summary' => [
                'linked_people' => $linkedPeople,
                'points_total' => $pointsCollection->count(),
                'presence_points' => $pointsCollection->where('type', VerificationPointType::Presence->value)->count(),
                'check_in_points' => $pointsCollection->where('type', VerificationPointType::CheckIn->value)->count(),
                'verified_entries' => $totalVerifications,
            ],
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(Request $request, Camp $camp): RedirectResponse
    {
        Gate::authorize('manage-operations');

        $data = $this->validatePoint($request);

        $camp->verificationPoints()->create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => ((int) $camp->verificationPoints()->max('sort_order')) + 1,
        ]);

        return back()->with('status', 'Ponto de verificacao criado com sucesso.');
    }

    public function show(Camp $camp, CampVerificationPoint $verificationPoint): Response
    {
        Gate::authorize('manage-operations');
        $this->ensurePointBelongsToCamp($camp, $verificationPoint);

        $camp->load('lots');

        $entries = $verificationPoint->entries()
            ->with(['payment.user.role', 'payment.room', 'payment.team', 'verifiedBy'])
            ->get();

        $entriesByPaymentId = $entries->keyBy('camp_payment_id');

        $participants = CampPayment::query()
            ->with(['user.role', 'room', 'team'])
            ->whereBelongsTo($camp)
            ->join('users', 'camp_payments.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('camp_payments.*')
            ->get()
            ->map(fn (CampPayment $payment): array => $this->serializeParticipant(
                $payment,
                $entriesByPaymentId->get($payment->id),
            ))
            ->values()
            ->all();

        $totalParticipants = count($participants);
        $verifiedCount = $entries->count();

        return Inertia::render('Camps/VerificationPoints/Show', [
            'camp' => $this->serializeCamp($camp),
            'point' => $this->serializePoint($verificationPoint, $totalParticipants),
            'participants' => $participants,
            'recentEntries' => $entries
                ->take(12)
                ->map(fn (CampVerificationEntry $entry): array => $this->serializeEntry($entry))
                ->values()
                ->all(),
            'summary' => [
                'linked_people' => $totalParticipants,
                'verified_people' => $verifiedCount,
                'pending_people' => max($totalParticipants - $verifiedCount, 0),
            ],
        ]);
    }

    public function update(Request $request, Camp $camp, CampVerificationPoint $verificationPoint): RedirectResponse
    {
        Gate::authorize('manage-operations');
        $this->ensurePointBelongsToCamp($camp, $verificationPoint);

        $data = $this->validatePoint($request);

        $verificationPoint->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Ponto de verificacao atualizado com sucesso.');
    }

    public function destroy(Camp $camp, CampVerificationPoint $verificationPoint): RedirectResponse
    {
        Gate::authorize('manage-operations');
        $this->ensurePointBelongsToCamp($camp, $verificationPoint);

        $verificationPoint->delete();

        return redirect()
            ->route('camps.verification-points.index', $camp)
            ->with('status', 'Ponto de verificacao removido com sucesso.');
    }

    public function verify(Request $request, Camp $camp, CampVerificationPoint $verificationPoint): RedirectResponse
    {
        Gate::authorize('manage-operations');
        $this->ensurePointBelongsToCamp($camp, $verificationPoint);

        if (! $verificationPoint->is_active) {
            throw ValidationException::withMessages([
                'point' => 'Ative este ponto antes de registrar verificacoes.',
            ]);
        }

        $data = $request->validate(
            [
                'method' => ['required', Rule::enum(VerificationMethod::class)],
                'pin' => ['nullable', 'digits:4'],
                'verification_code' => ['nullable', 'string', 'max:64'],
                'camp_payment_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('camp_payments', 'id')->where('camp_id', $camp->id),
                ],
            ],
            [
                'pin.digits' => 'Informe um PIN com 4 digitos.',
            ],
        );

        $method = VerificationMethod::from($data['method']);
        $payment = $this->resolvePaymentForVerification($camp, $method, $data);

        $entry = $this->recordVerification(
            $verificationPoint,
            $payment,
            $method,
            $request->user(),
        );

        return back()->with(
            'status',
            $entry->wasRecentlyCreated
                ? 'Pessoa verificada com sucesso.'
                : 'Verificacao atualizada com sucesso.',
        );
    }

    /**
     * @return array{name: string, type: string, notes?: string|null}
     */
    private function validatePoint(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(VerificationPointType::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolvePaymentForVerification(Camp $camp, VerificationMethod $method, array $data): CampPayment
    {
        return match ($method) {
            VerificationMethod::Pin => $this->findPaymentByPin($camp, (string) ($data['pin'] ?? '')),
            VerificationMethod::Code => $this->findPaymentByCode($camp, (string) ($data['verification_code'] ?? '')),
            VerificationMethod::Manual => $this->findPaymentById($camp, $data['camp_payment_id'] ?? null),
        };
    }

    private function findPaymentByPin(Camp $camp, string $pin): CampPayment
    {
        $normalizedPin = preg_replace('/\D+/', '', $pin) ?? '';

        if (strlen($normalizedPin) !== 4) {
            throw ValidationException::withMessages([
                'pin' => 'Informe o PIN com 4 digitos.',
            ]);
        }

        $payment = CampPayment::query()
            ->with(['user.role', 'room', 'team'])
            ->whereBelongsTo($camp)
            ->whereHas('user', fn ($query) => $query->where('pin', $normalizedPin))
            ->first();

        if ($payment === null) {
            throw ValidationException::withMessages([
                'pin' => 'Nenhuma pessoa deste acampamento foi encontrada com esse PIN.',
            ]);
        }

        return $payment;
    }

    private function findPaymentByCode(Camp $camp, string $verificationCode): CampPayment
    {
        $normalizedCode = strtoupper(trim(preg_replace('/\s+/', '', $verificationCode) ?? ''));

        if ($normalizedCode === '') {
            throw ValidationException::withMessages([
                'verification_code' => 'Informe ou leia o codigo da pessoa.',
            ]);
        }

        $payment = CampPayment::query()
            ->with(['user.role', 'room', 'team'])
            ->whereBelongsTo($camp)
            ->whereHas('user', fn ($query) => $query->where('verification_code', $normalizedCode))
            ->first();

        if ($payment === null) {
            throw ValidationException::withMessages([
                'verification_code' => 'Nenhuma pessoa deste acampamento foi encontrada com esse codigo.',
            ]);
        }

        return $payment;
    }

    private function findPaymentById(Camp $camp, mixed $paymentId): CampPayment
    {
        if ($paymentId === null || $paymentId === '') {
            throw ValidationException::withMessages([
                'camp_payment_id' => 'Escolha uma pessoa para registar manualmente.',
            ]);
        }

        return CampPayment::query()
            ->with(['user.role', 'room', 'team'])
            ->whereBelongsTo($camp)
            ->findOrFail($paymentId);
    }

    private function recordVerification(
        CampVerificationPoint $point,
        CampPayment $payment,
        VerificationMethod $method,
        ?User $verifiedBy,
    ): CampVerificationEntry {
        $payment->loadMissing(['user.role', 'room', 'team']);

        return CampVerificationEntry::query()->updateOrCreate(
            [
                'camp_verification_point_id' => $point->id,
                'camp_payment_id' => $payment->id,
            ],
            [
                'user_id' => $payment->user_id,
                'verified_by' => $verifiedBy?->id,
                'method' => $method,
                'verified_at' => now(),
                'user_name' => $payment->user->name,
                'user_email' => $payment->user->email,
                'user_phone' => $payment->user->phone,
                'user_role_name' => $payment->user->role?->name,
                'team_name' => $payment->team?->name,
                'room_name' => $payment->room?->name,
                'sex_label' => $payment->user->sex?->label(),
            ],
        );
    }

    private function ensurePointBelongsToCamp(Camp $camp, CampVerificationPoint $verificationPoint): void
    {
        abort_unless($verificationPoint->camp_id === $camp->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCamp(Camp $camp): array
    {
        return [
            'id' => $camp->id,
            'name' => $camp->name,
            'description' => $camp->description,
            'address' => $camp->address,
            'starts_on' => $camp->starts_on?->format('Y-m-d'),
            'starts_on_label' => $camp->starts_on?->format('d/m/Y'),
            'ends_on' => $camp->ends_on?->format('Y-m-d'),
            'ends_on_label' => $camp->ends_on?->format('d/m/Y'),
            'date_range_label' => $this->dateRangeLabel($camp),
            'amount_cents' => $camp->amount_cents,
            'amount' => $this->moneyFromCents($camp->amount_cents),
            'is_active' => $camp->is_active,
            'lots' => $camp->lots->map(fn ($lot): array => [
                'id' => $lot->id,
                'name' => $lot->name,
                'amount_cents' => $lot->amount_cents,
                'amount' => $this->moneyFromCents($lot->amount_cents),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePoint(CampVerificationPoint $point, int $linkedPeople): array
    {
        $lastVerifiedAt = $point->getAttribute('last_verified_at');
        $verifiedCount = (int) ($point->entries_count
            ?? ($point->relationLoaded('entries') ? $point->entries->count() : 0));

        if ($lastVerifiedAt === null && $point->relationLoaded('entries')) {
            $lastVerifiedAt = $point->entries->first()?->verified_at;
        }

        $lastVerifiedAtCarbon = match (true) {
            $lastVerifiedAt instanceof Carbon => $lastVerifiedAt,
            $lastVerifiedAt !== null => Carbon::parse((string) $lastVerifiedAt),
            default => null,
        };

        return [
            'id' => $point->id,
            'name' => $point->name,
            'type' => $point->type->value,
            'type_label' => $point->type->label(),
            'notes' => $point->notes,
            'point_code' => $point->point_code,
            'is_active' => $point->is_active,
            'sort_order' => $point->sort_order,
            'verified_count' => $verifiedCount,
            'pending_count' => max($linkedPeople - $verifiedCount, 0),
            'last_verified_at' => $lastVerifiedAtCarbon?->toISOString(),
            'last_verified_at_label' => $lastVerifiedAtCarbon?->format('d/m/Y H:i'),
            'operation_url' => route('camps.verification-points.show', [$point->camp_id, $point]),
        ];
    }

    private function serializeEntry(CampVerificationEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'method' => $entry->method->value,
            'method_label' => $entry->method->label(),
            'verified_at' => $entry->verified_at?->toISOString(),
            'verified_at_label' => $entry->verified_at?->format('d/m/Y H:i'),
            'user' => [
                'id' => $entry->user_id,
                'name' => $entry->user_name,
                'email' => $entry->user_email,
                'phone' => $entry->user_phone,
                'role_name' => $entry->user_role_name,
                'team_name' => $entry->team_name,
                'room_name' => $entry->room_name,
                'sex_label' => $entry->sex_label,
            ],
            'verified_by_name' => $entry->verifiedBy?->name,
        ];
    }

    private function serializeParticipant(CampPayment $payment, ?CampVerificationEntry $entry): array
    {
        return [
            'id' => $payment->id,
            'status_label' => $payment->status->label(),
            'user' => [
                'id' => $payment->user->id,
                'name' => $payment->user->name,
                'email' => $payment->user->email,
                'phone' => $payment->user->phone,
                'pin' => $payment->user->pin,
                'verification_code' => $payment->user->verification_code,
                'sex' => $payment->user->sex?->value,
                'sex_label' => $payment->user->sex?->label(),
                'role' => $payment->user->role ? [
                    'id' => $payment->user->role->id,
                    'key' => $payment->user->role->key,
                    'name' => $payment->user->role->name,
                ] : null,
            ],
            'team' => $payment->team ? [
                'id' => $payment->team->id,
                'name' => $payment->team->name,
            ] : null,
            'room' => $payment->room ? [
                'id' => $payment->room->id,
                'name' => $payment->room->name,
            ] : null,
            'verification' => $entry ? [
                'id' => $entry->id,
                'method' => $entry->method->value,
                'method_label' => $entry->method->label(),
                'verified_at' => $entry->verified_at?->toISOString(),
                'verified_at_label' => $entry->verified_at?->format('d/m/Y H:i'),
            ] : null,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return array_map(fn (VerificationPointType $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ], VerificationPointType::cases());
    }

    private function moneyFromCents(int $value): string
    {
        return number_format($value / 100, 2, '.', '');
    }

    private function dateRangeLabel(Camp $camp): ?string
    {
        if ($camp->starts_on === null) {
            return null;
        }

        if ($camp->ends_on === null || $camp->starts_on->isSameDay($camp->ends_on)) {
            return $camp->starts_on->format('d/m/Y');
        }

        return $camp->starts_on->format('d/m/Y').' a '.$camp->ends_on->format('d/m/Y');
    }
}
