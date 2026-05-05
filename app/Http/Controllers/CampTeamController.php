<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\CampPayment;
use App\Models\CampTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampTeamController extends Controller
{
    public function index(Camp $camp): Response
    {
        Gate::authorize('manage-camps');

        $camp->load('lots');

        $teams = CampTeam::query()
            ->with(['leaderPayment.user.role', 'payments.user.role'])
            ->whereBelongsTo($camp)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CampTeam $team): array => $this->serializeTeam($team))
            ->all();

        $unassignedPayments = CampPayment::query()
            ->with(['user.role'])
            ->whereBelongsTo($camp)
            ->whereNull('camp_team_id')
            ->join('users', 'camp_payments.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('camp_payments.*')
            ->get()
            ->map(fn (CampPayment $payment): array => $this->serializeTeamParticipant($payment))
            ->all();

        $leaderOptions = CampPayment::query()
            ->with(['user.role'])
            ->whereBelongsTo($camp)
            ->join('users', 'camp_payments.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('camp_payments.*')
            ->get()
            ->map(fn (CampPayment $payment): array => $this->serializeTeamParticipant($payment))
            ->all();

        $assignedCount = $camp->payments()->whereNotNull('camp_team_id')->count();
        $leadersCount = CampTeam::query()
            ->whereBelongsTo($camp)
            ->whereNotNull('leader_camp_payment_id')
            ->count();

        return Inertia::render('Camps/Teams', [
            'camp' => $this->serializeCamp($camp),
            'teams' => $teams,
            'unassignedPayments' => $unassignedPayments,
            'leaderOptions' => $leaderOptions,
            'summary' => [
                'linked_people' => $camp->payments()->count(),
                'assigned_people' => $assignedCount,
                'unassigned_people' => count($unassignedPayments),
                'teams' => count($teams),
                'leaders' => $leadersCount,
            ],
        ]);
    }

    public function store(Request $request, Camp $camp): RedirectResponse
    {
        Gate::authorize('manage-camps');

        $data = $this->validateTeam($request, $camp);

        $team = $camp->teams()->create([
            ...$data,
            'sort_order' => ((int) $camp->teams()->max('sort_order')) + 1,
        ]);

        $this->syncLeaderMembership($team);

        return back()->with('status', 'Equipe criada com sucesso.');
    }

    public function update(Request $request, Camp $camp, CampTeam $team): RedirectResponse
    {
        Gate::authorize('manage-camps');
        abort_unless($team->camp_id === $camp->id, 404);

        $data = $this->validateTeam($request, $camp);

        $team->update($data);
        $this->syncLeaderMembership($team);

        return back()->with('status', 'Equipe atualizada com sucesso.');
    }

    public function destroy(Camp $camp, CampTeam $team): RedirectResponse
    {
        Gate::authorize('manage-camps');
        abort_unless($team->camp_id === $camp->id, 404);

        $team->payments()->update(['camp_team_id' => null]);
        $team->delete();

        return back()->with('status', 'Equipe removida. As pessoas ficaram sem equipe definida.');
    }

    public function assign(Request $request, Camp $camp): RedirectResponse
    {
        Gate::authorize('manage-camps');

        $data = $request->validate([
            'camp_payment_id' => [
                'required',
                'integer',
                Rule::exists('camp_payments', 'id')->where('camp_id', $camp->id),
            ],
            'camp_team_id' => [
                'nullable',
                'integer',
                Rule::exists('camp_teams', 'id')->where('camp_id', $camp->id),
            ],
        ]);

        $payment = CampPayment::query()
            ->whereBelongsTo($camp)
            ->findOrFail($data['camp_payment_id']);
        $teamId = $data['camp_team_id'] ?? null;

        if ($teamId !== null) {
            CampTeam::query()->whereBelongsTo($camp)->findOrFail($teamId);
        }

        CampTeam::query()
            ->whereBelongsTo($camp)
            ->where('leader_camp_payment_id', $payment->id)
            ->when($teamId !== null, fn ($query) => $query->where('id', '!=', $teamId))
            ->update(['leader_camp_payment_id' => null]);

        $payment->update(['camp_team_id' => $teamId]);

        return back()->with('status', $teamId ? 'Pessoa movida para a equipe.' : 'Pessoa movida para sem equipe.');
    }

    /**
     * @return array{name: string, leader_camp_payment_id?: int|null, notes?: string|null}
     */
    private function validateTeam(Request $request, Camp $camp): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'leader_camp_payment_id' => [
                'nullable',
                'integer',
                Rule::exists('camp_payments', 'id')->where('camp_id', $camp->id),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function syncLeaderMembership(CampTeam $team): void
    {
        if ($team->leader_camp_payment_id === null) {
            return;
        }

        $leaderPayment = CampPayment::query()
            ->whereBelongsTo($team->camp)
            ->find($team->leader_camp_payment_id);

        if ($leaderPayment === null) {
            throw ValidationException::withMessages([
                'leader_camp_payment_id' => 'Escolha uma pessoa vinculada a este acampamento.',
            ]);
        }

        CampTeam::query()
            ->whereBelongsTo($team->camp)
            ->where('id', '!=', $team->id)
            ->where('leader_camp_payment_id', $leaderPayment->id)
            ->update(['leader_camp_payment_id' => null]);

        $leaderPayment->update(['camp_team_id' => $team->id]);
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
                'sort_order' => $lot->sort_order,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTeam(CampTeam $team): array
    {
        $members = $team->payments
            ->sortBy(fn (CampPayment $payment): string => $payment->user->name)
            ->map(fn (CampPayment $payment): array => $this->serializeTeamParticipant($payment))
            ->values()
            ->all();

        return [
            'id' => $team->id,
            'name' => $team->name,
            'leader_camp_payment_id' => $team->leader_camp_payment_id,
            'leader' => $team->leaderPayment ? $this->serializeTeamParticipant($team->leaderPayment) : null,
            'notes' => $team->notes,
            'sort_order' => $team->sort_order,
            'members_count' => count($members),
            'members' => $members,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTeamParticipant(CampPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'camp_team_id' => $payment->camp_team_id,
            'status_label' => $payment->status->label(),
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
        ];
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
