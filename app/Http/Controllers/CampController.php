<?php

namespace App\Http\Controllers;

use App\Enums\CampPaymentStatus;
use App\Enums\VerificationPointType;
use App\Http\Requests\StoreCampRequest;
use App\Http\Requests\UpdateCampRequest;
use App\Models\Camp;
use App\Models\CampLot;
use App\Models\CampVerificationEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CampController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage-camps');

        $camps = Camp::query()
            ->with('lots')
            ->withCount([
                'payments as payments_total',
                'payments as payments_paid' => fn ($query) => $query->where('status', CampPaymentStatus::Paid->value),
                'payments as payments_partial' => fn ($query) => $query->where('status', CampPaymentStatus::Partial->value),
                'payments as payments_pending' => fn ($query) => $query->where('status', CampPaymentStatus::Pending->value),
                'payments as payments_exempted' => fn ($query) => $query->where('status', CampPaymentStatus::Exempted->value),
                'verificationPoints as verification_points_total',
            ])
            ->orderByDesc('starts_on')
            ->get()
            ->map(fn (Camp $camp): array => $this->serializeCamp($camp))
            ->all();

        return Inertia::render('Camps/Index', [
            'camps' => $camps,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('manage-camps');

        return Inertia::render('Camps/Create');
    }

    public function show(Camp $camp): Response
    {
        Gate::authorize('manage-camps');

        $camp->load('lots')
            ->loadCount([
                'rooms as rooms_total',
                'teams as teams_total',
                'payments as payments_total',
                'payments as payments_paid' => fn ($query) => $query->where('status', CampPaymentStatus::Paid->value),
                'payments as payments_partial' => fn ($query) => $query->where('status', CampPaymentStatus::Partial->value),
                'payments as payments_pending' => fn ($query) => $query->where('status', CampPaymentStatus::Pending->value),
                'payments as payments_exempted' => fn ($query) => $query->where('status', CampPaymentStatus::Exempted->value),
                'verificationPoints as verification_points_total',
                'verificationPoints as verification_presence_points' => fn ($query) => $query->where('type', VerificationPointType::Presence->value),
                'verificationPoints as verification_check_in_points' => fn ($query) => $query->where('type', VerificationPointType::CheckIn->value),
            ]);

        $chargeablePayments = $camp->payments()
            ->where('status', '!=', CampPaymentStatus::Exempted->value);
        $totalDueCents = (int) $chargeablePayments->sum('amount_cents');
        $receivedCents = (int) $camp->payments()->sum('amount_paid_cents');
        $verificationEntriesTotal = CampVerificationEntry::query()
            ->whereHas('point', fn ($query) => $query->whereBelongsTo($camp))
            ->count();

        return Inertia::render('Camps/Show', [
            'camp' => $this->serializeCamp($camp),
            'financialSummary' => [
                'total_due' => $this->moneyFromCents($totalDueCents),
                'received' => $this->moneyFromCents($receivedCents),
                'open' => $this->moneyFromCents(max($totalDueCents - $receivedCents, 0)),
            ],
            'verificationSummary' => [
                'points_total' => $camp->verification_points_total ?? 0,
                'presence_points' => $camp->verification_presence_points ?? 0,
                'check_in_points' => $camp->verification_check_in_points ?? 0,
                'entries_total' => $verificationEntriesTotal,
            ],
        ]);
    }

    public function store(StoreCampRequest $request): RedirectResponse
    {
        $camp = DB::transaction(function () use ($request): Camp {
            $camp = Camp::query()->create([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'address' => $request->validated('address'),
                'starts_on' => $request->validated('starts_on'),
                'ends_on' => $request->validated('ends_on'),
                'amount_cents' => $this->moneyToCents($request->validated('amount')),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->syncLots($camp, $request->validated('lots'));

            return $camp;
        });

        return redirect()
            ->route('camps.show', $camp)
            ->with('status', 'Acampamento criado com sucesso.');
    }

    public function edit(Camp $camp): Response
    {
        Gate::authorize('manage-camps');

        $camp->load('lots');

        return Inertia::render('Camps/Edit', [
            'camp' => $this->serializeCamp($camp),
        ]);
    }

    public function update(UpdateCampRequest $request, Camp $camp): RedirectResponse
    {
        DB::transaction(function () use ($request, $camp): void {
            $camp->update([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'address' => $request->validated('address'),
                'starts_on' => $request->validated('starts_on'),
                'ends_on' => $request->validated('ends_on'),
                'amount_cents' => $this->moneyToCents($request->validated('amount')),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncLots($camp, $request->validated('lots'));
        });

        return redirect()
            ->route('camps.index')
            ->with('status', 'Acampamento atualizado com sucesso.');
    }

    public function destroy(Camp $camp): RedirectResponse
    {
        Gate::authorize('manage-camps');

        $camp->delete();

        return redirect()
            ->route('camps.index')
            ->with('status', 'Acampamento removido com sucesso.');
    }

    /**
     * @param array<int, array{name: string, amount: mixed}> $lots
     */
    private function syncLots(Camp $camp, array $lots): void
    {
        $camp->lots()->delete();

        foreach (array_values($lots) as $index => $lot) {
            $camp->lots()->create([
                'name' => $lot['name'],
                'amount_cents' => $this->moneyToCents($lot['amount']),
                'sort_order' => $index + 1,
            ]);
        }
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
            'lots' => $camp->lots->map(fn (CampLot $lot): array => [
                'id' => $lot->id,
                'name' => $lot->name,
                'amount_cents' => $lot->amount_cents,
                'amount' => $this->moneyFromCents($lot->amount_cents),
                'sort_order' => $lot->sort_order,
            ])->values()->all(),
            'payments_total' => $camp->payments_total ?? 0,
            'payments_paid' => $camp->payments_paid ?? 0,
            'payments_partial' => $camp->payments_partial ?? 0,
            'payments_pending' => $camp->payments_pending ?? 0,
            'payments_exempted' => $camp->payments_exempted ?? 0,
            'rooms_total' => $camp->rooms_total ?? 0,
            'teams_total' => $camp->teams_total ?? 0,
            'verification_points_total' => $camp->verification_points_total ?? 0,
            'verification_entries_total' => $camp->verification_entries_total ?? 0,
        ];
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
