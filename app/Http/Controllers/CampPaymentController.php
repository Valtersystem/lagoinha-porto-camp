<?php

namespace App\Http\Controllers;

use App\Enums\CampPaymentStatus;
use App\Models\Camp;
use App\Models\CampLot;
use App\Models\CampPayment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampPaymentController extends Controller
{
    public function index(Request $request, Camp $camp): Response
    {
        Gate::authorize('manage-payments');

        $camp->load('lots');

        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $payments = CampPayment::query()
            ->with(['user.role', 'lot'])
            ->whereBelongsTo($camp)
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = '%'.$filters['search'].'%';

                $query->whereHas('user', function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->when($filters['status'] !== '', function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->join('users', 'camp_payments.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('camp_payments.*')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CampPayment $payment): array => $this->serializePayment($payment));

        return Inertia::render('Camps/Payments', [
            'camp' => $this->serializeCamp($camp),
            'payments' => $payments,
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'summary' => $this->summary($camp),
            'availableUsers' => $this->availableUsers($camp),
        ]);
    }

    public function store(Request $request, Camp $camp): RedirectResponse
    {
        Gate::authorize('manage-payments');

        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('camp_payments', 'user_id')->where('camp_id', $camp->id),
            ],
            'camp_lot_id' => [
                'nullable',
                'integer',
                Rule::exists('camp_lots', 'id')->where('camp_id', $camp->id),
            ],
            'installments_count' => ['required', 'integer', 'min:1', 'max:24'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $lot = isset($data['camp_lot_id'])
            ? CampLot::query()->whereBelongsTo($camp)->find($data['camp_lot_id'])
            : null;

        CampPayment::query()->create([
            'camp_id' => $camp->id,
            'user_id' => $data['user_id'],
            'camp_lot_id' => $lot?->id,
            'amount_cents' => $lot?->amount_cents ?? $camp->amount_cents,
            'installments_count' => $data['installments_count'],
            'paid_installments' => 0,
            'amount_paid_cents' => 0,
            'status' => CampPaymentStatus::Pending,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'Usuario vinculado ao acampamento com sucesso.');
    }

    public function update(Request $request, Camp $camp, CampPayment $payment): RedirectResponse
    {
        Gate::authorize('manage-payments');

        abort_unless($payment->camp_id === $camp->id, 404);

        $data = $request->validate(
            [
                'status' => ['required', Rule::in(array_map(fn (CampPaymentStatus $status): string => $status->value, CampPaymentStatus::cases()))],
                'camp_lot_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('camp_lots', 'id')->where('camp_id', $camp->id),
                ],
                'installments_count' => ['required', 'integer', 'min:1', 'max:24'],
                'paid_installments' => ['required', 'integer', 'min:0', 'max:24'],
                'amount_paid' => ['required', 'string', 'max:20', 'regex:/^\d+([,.]\d{1,2})?$/'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'amount_paid.regex' => 'Informe o valor recebido em euros, como 0,00 ou 50.00.',
            ],
        );

        $lot = isset($data['camp_lot_id'])
            ? CampLot::query()->whereBelongsTo($camp)->find($data['camp_lot_id'])
            : null;
        $amountCents = $lot?->amount_cents ?? $camp->amount_cents;
        $requestedPaidInstallments = min((int) $data['paid_installments'], (int) $data['installments_count']);
        $requestedAmountPaidCents = min($this->moneyToCents($data['amount_paid']), $amountCents);
        $status = $this->normalizeStatus(
            CampPaymentStatus::from($data['status']),
            $requestedPaidInstallments,
            $requestedAmountPaidCents,
        );

        if (
            $status === CampPaymentStatus::Partial
            && $requestedPaidInstallments === 0
            && $requestedAmountPaidCents === 0
        ) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Para pagamento parcial, informe algum valor recebido ou parcelas pagas.',
            ]);
        }

        $payment->fill([
            'camp_lot_id' => $lot?->id,
            'amount_cents' => $amountCents,
            'installments_count' => $data['installments_count'],
            'paid_installments' => $requestedPaidInstallments,
            'amount_paid_cents' => $requestedAmountPaidCents,
            'status' => $status,
            'notes' => $data['notes'] ?? null,
        ]);

        match ($status) {
            CampPaymentStatus::Partial => $payment->forceFill([
                'paid_at' => null,
                'exempted_at' => null,
                'exempted_by' => null,
            ]),
            CampPaymentStatus::Paid => $payment->forceFill([
                'paid_installments' => $payment->installments_count,
                'amount_paid_cents' => $payment->amount_cents,
                'paid_at' => now(),
                'exempted_at' => null,
                'exempted_by' => null,
            ]),
            CampPaymentStatus::Exempted => $payment->forceFill([
                'paid_installments' => 0,
                'amount_paid_cents' => 0,
                'paid_at' => null,
                'exempted_at' => now(),
                'exempted_by' => $request->user()?->id,
            ]),
            CampPaymentStatus::Pending => $payment->forceFill([
                'paid_installments' => 0,
                'amount_paid_cents' => 0,
                'paid_at' => null,
                'exempted_at' => null,
                'exempted_by' => null,
            ]),
        };

        $payment->save();

        return back()->with('status', 'Pagamento atualizado com sucesso.');
    }

    public function destroy(Camp $camp, CampPayment $payment): RedirectResponse
    {
        Gate::authorize('manage-payments');

        abort_unless($payment->camp_id === $camp->id, 404);

        $payment->delete();

        return back()->with('status', 'Usuario removido deste acampamento.');
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
            'lots' => $camp->lots->map(fn (CampLot $lot): array => [
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
    private function serializePayment(CampPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'camp_lot_id' => $payment->camp_lot_id,
            'amount_cents' => $payment->amount_cents,
            'amount' => $this->moneyFromCents($payment->amount_cents),
            'installments_count' => $payment->installments_count,
            'paid_installments' => $payment->paid_installments,
            'amount_paid_cents' => $payment->amount_paid_cents,
            'amount_paid' => $this->moneyFromCents($payment->amount_paid_cents),
            'notes' => $payment->notes,
            'paid_at' => $payment->paid_at?->toISOString(),
            'exempted_at' => $payment->exempted_at?->toISOString(),
            'user' => [
                'id' => $payment->user->id,
                'name' => $payment->user->name,
                'email' => $payment->user->email,
                'phone' => $payment->user->phone,
                'sex' => $payment->user->sex?->value,
                'sex_label' => $payment->user->sex?->label(),
                'role' => $payment->user->role ? [
                    'id' => $payment->user->role->id,
                    'key' => $payment->user->role->key,
                    'name' => $payment->user->role->name,
                ] : null,
            ],
            'lot' => $payment->lot ? [
                'id' => $payment->lot->id,
                'name' => $payment->lot->name,
                'amount' => $this->moneyFromCents($payment->lot->amount_cents),
            ] : null,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(fn (CampPaymentStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], CampPaymentStatus::cases());
    }

    /**
     * @return array<string, int>
     */
    private function summary(Camp $camp): array
    {
        return [
            'total' => $camp->payments()->count(),
            'pending' => $camp->payments()->where('status', CampPaymentStatus::Pending->value)->count(),
            'partial' => $camp->payments()->where('status', CampPaymentStatus::Partial->value)->count(),
            'paid' => $camp->payments()->where('status', CampPaymentStatus::Paid->value)->count(),
            'exempted' => $camp->payments()->where('status', CampPaymentStatus::Exempted->value)->count(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, email: string, phone: string|null, sex: string|null, sex_label: string|null}>
     */
    private function availableUsers(Camp $camp): array
    {
        return User::query()
            ->whereDoesntHave('campPayments', fn ($query) => $query->where('camp_id', $camp->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'sex'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'sex' => $user->sex?->value,
                'sex_label' => $user->sex?->label(),
            ])
            ->all();
    }

    private function moneyToCents(mixed $value): int
    {
        $normalized = str_replace(',', '.', (string) $value);

        return (int) round(((float) $normalized) * 100);
    }

    private function moneyFromCents(int $value): string
    {
        return number_format($value / 100, 2, '.', '');
    }

    private function normalizeStatus(
        CampPaymentStatus $status,
        int $paidInstallments,
        int $amountPaidCents,
    ): CampPaymentStatus {
        if (
            $status === CampPaymentStatus::Pending
            && ($paidInstallments > 0 || $amountPaidCents > 0)
        ) {
            return CampPaymentStatus::Partial;
        }

        return $status;
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
