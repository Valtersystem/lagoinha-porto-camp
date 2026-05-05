<?php

namespace App\Http\Controllers;

use App\Enums\UserSex;
use App\Models\Camp;
use App\Models\CampPayment;
use App\Models\CampRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampRoomController extends Controller
{
    public function index(Camp $camp): Response
    {
        Gate::authorize('manage-camps');

        $camp->load('lots');

        $rooms = CampRoom::query()
            ->with(['payments.user.role'])
            ->withCount('payments')
            ->whereBelongsTo($camp)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CampRoom $room): array => $this->serializeRoom($room))
            ->all();

        $unassignedPayments = CampPayment::query()
            ->with(['user.role'])
            ->whereBelongsTo($camp)
            ->whereNull('camp_room_id')
            ->join('users', 'camp_payments.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('camp_payments.*')
            ->get()
            ->map(fn (CampPayment $payment): array => $this->serializeRoomParticipant($payment))
            ->all();

        $totalCapacity = array_sum(array_column($rooms, 'capacity'));
        $assignedCount = $camp->payments()->whereNotNull('camp_room_id')->count();

        return Inertia::render('Camps/Rooms', [
            'camp' => $this->serializeCamp($camp),
            'rooms' => $rooms,
            'unassignedPayments' => $unassignedPayments,
            'summary' => [
                'linked_people' => $camp->payments()->count(),
                'assigned_people' => $assignedCount,
                'unassigned_people' => count($unassignedPayments),
                'rooms' => count($rooms),
                'capacity' => $totalCapacity,
            ],
        ]);
    }

    public function store(Request $request, Camp $camp): RedirectResponse
    {
        Gate::authorize('manage-camps');

        $data = $this->validateRoom($request);

        $camp->rooms()->create([
            ...$data,
            'sort_order' => ((int) $camp->rooms()->max('sort_order')) + 1,
        ]);

        return back()->with('status', 'Quarto criado com sucesso.');
    }

    public function update(Request $request, Camp $camp, CampRoom $room): RedirectResponse
    {
        Gate::authorize('manage-camps');
        abort_unless($room->camp_id === $camp->id, 404);

        $data = $this->validateRoom($request);
        $occupantsCount = $room->payments()->count();

        if ((int) $data['capacity'] < $occupantsCount) {
            throw ValidationException::withMessages([
                'capacity' => 'Este quarto ja tem '.$occupantsCount.' pessoa(s). A capacidade nao pode ser menor que isso.',
            ]);
        }

        $hasIncompatibleOccupants = $room->payments()
            ->whereHas('user', fn ($query) => $query->where('sex', '!=', $data['sex'])->orWhereNull('sex'))
            ->exists();

        if ($hasIncompatibleOccupants) {
            throw ValidationException::withMessages([
                'sex' => 'Este quarto ja tem pessoas de outro sexo. Mova essas pessoas antes de alterar o tipo do quarto.',
            ]);
        }

        $room->update($data);

        return back()->with('status', 'Quarto atualizado com sucesso.');
    }

    public function destroy(Camp $camp, CampRoom $room): RedirectResponse
    {
        Gate::authorize('manage-camps');
        abort_unless($room->camp_id === $camp->id, 404);

        $room->payments()->update(['camp_room_id' => null]);
        $room->delete();

        return back()->with('status', 'Quarto removido. As pessoas ficaram sem quarto definido.');
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
            'camp_room_id' => [
                'nullable',
                'integer',
                Rule::exists('camp_rooms', 'id')->where('camp_id', $camp->id),
            ],
        ]);

        $payment = CampPayment::query()
            ->with('user')
            ->whereBelongsTo($camp)
            ->findOrFail($data['camp_payment_id']);
        $roomId = $data['camp_room_id'] ?? null;

        if ($roomId !== null && (int) $payment->camp_room_id !== (int) $roomId) {
            $room = CampRoom::query()->whereBelongsTo($camp)->findOrFail($roomId);

            if ($room->sex === null) {
                throw ValidationException::withMessages([
                    'camp_room_id' => 'Defina se o quarto e masculino ou feminino antes de mover pessoas.',
                ]);
            }

            if ($payment->user->sex === null) {
                throw ValidationException::withMessages([
                    'camp_payment_id' => 'Defina o sexo desta pessoa no cadastro de usuarios antes de escolher o quarto.',
                ]);
            }

            if ($room->sex !== $payment->user->sex) {
                throw ValidationException::withMessages([
                    'camp_room_id' => 'Esta pessoa nao pode ser movida para um quarto de sexo diferente.',
                ]);
            }

            if ($room->payments()->count() >= $room->capacity) {
                throw ValidationException::withMessages([
                    'camp_room_id' => 'Este quarto ja atingiu a capacidade definida.',
                ]);
            }
        }

        $payment->update(['camp_room_id' => $roomId]);

        return back()->with('status', $roomId ? 'Pessoa movida para o quarto.' : 'Pessoa movida para sem quarto.');
    }

    /**
     * @return array{name: string, sex: string, capacity: int, notes?: string|null}
     */
    private function validateRoom(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sex' => ['required', Rule::enum(UserSex::class)],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
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
    private function serializeRoom(CampRoom $room): array
    {
        $occupants = $room->payments
            ->sortBy(fn (CampPayment $payment): string => $payment->user->name)
            ->map(fn (CampPayment $payment): array => $this->serializeRoomParticipant($payment))
            ->values()
            ->all();

        return [
            'id' => $room->id,
            'name' => $room->name,
            'sex' => $room->sex?->value,
            'sex_label' => $room->sex?->label(),
            'capacity' => $room->capacity,
            'notes' => $room->notes,
            'sort_order' => $room->sort_order,
            'occupants_count' => count($occupants),
            'available_places' => max($room->capacity - count($occupants), 0),
            'occupants' => $occupants,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRoomParticipant(CampPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'camp_room_id' => $payment->camp_room_id,
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
