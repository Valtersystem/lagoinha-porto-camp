<?php

namespace App\Http\Controllers;

use App\Actions\SyncUserPhoto;
use App\Models\Camp;
use App\Models\CampPayment;
use App\Models\CampVerificationEntry;
use App\Enums\UserRole;
use App\Enums\VerificationPointType;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly SyncUserPhoto $syncUserPhoto)
    {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('manage-users');

        $filters = [
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString(),
        ];

        $users = User::query()
            ->with('role')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->when($filters['role'] !== '', function ($query) use ($filters): void {
                $query->whereHas('role', function ($query) use ($filters): void {
                    $query->where('key', $filters['role']);
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (User $user): array => $this->serializeUser($user, $request->user()));

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $this->rolesForForm(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('manage-users');

        return Inertia::render('Users/Create', [
            'roles' => $this->rolesForForm(),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorizeProfileAccess($request->user(), $user);

        $user->loadMissing('role');

        $participations = $user->campPayments()
            ->with(['camp', 'room', 'team', 'lot'])
            ->join('camps', 'camp_payments.camp_id', '=', 'camps.id')
            ->orderByDesc('camps.is_active')
            ->orderByDesc('camps.starts_on')
            ->select('camp_payments.*')
            ->get();

        $currentParticipation = $participations->first();

        return Inertia::render('Users/Show', [
            'managedUser' => $this->serializeUser($user, $request->user()),
            'currentParticipation' => $currentParticipation
                ? $this->serializeParticipation($currentParticipation)
                : null,
            'participations' => $participations
                ->map(fn (CampPayment $payment): array => $this->serializeParticipation($payment))
                ->all(),
            'operationStatus' => [
                'presence' => $this->latestVerificationLabel($currentParticipation, VerificationPointType::Presence),
                'check_in' => $this->latestVerificationLabel($currentParticipation, VerificationPointType::CheckIn),
                'photo' => $user->photo_path ? 'Com foto' : 'Sem foto',
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'sex' => $data['sex'],
            'role_id' => $data['role_id'],
            'password' => Hash::make($data['password']),
        ]);

        $this->syncUserPhoto->handle($user, $request->file('photo'));

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario criado com sucesso.');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('manage-users');

        $user->loadMissing('role');

        return Inertia::render('Users/Edit', [
            'managedUser' => $this->serializeUser($user, request()->user()),
            'roles' => $this->rolesForForm(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $this->guardLastAdministrator($user, (int) $data['role_id']);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'sex' => $data['sex'],
            'role_id' => $data['role_id'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $this->syncUserPhoto->handle(
            $user,
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario atualizado com sucesso.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($request->user()?->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Voce nao pode remover o proprio usuario administrativo.',
            ]);
        }

        $this->guardLastAdministrator($user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario removido com sucesso.');
    }

    /**
     * @return array<int, array{id: int, key: string, name: string, description: string|null}>
     */
    private function rolesForForm(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'description'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => $role->name,
                'description' => $role->description,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user, ?User $currentUser): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'photo_url' => $user->photo_path ? Storage::disk('public')->url($user->photo_path) : null,
            'pin' => $user->pin,
            'verification_code' => $user->verification_code,
            'sex' => $user->sex?->value,
            'sex_label' => $user->sex?->label(),
            'role_id' => $user->role_id,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'key' => $user->role->key,
                'name' => $user->role->name,
                'description' => $user->role->description,
            ] : null,
            'is_current_user' => $currentUser?->is($user) ?? false,
            'created_at' => $user->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeParticipation(CampPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'installments_count' => $payment->installments_count,
            'paid_installments' => $payment->paid_installments,
            'notes' => $payment->notes,
            'camp' => [
                'id' => $payment->camp->id,
                'name' => $payment->camp->name,
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

    private function guardLastAdministrator(User $user, ?int $newRoleId = null): void
    {
        $user->loadMissing('role');

        if (! $user->hasRole(UserRole::Administrator)) {
            return;
        }

        $newRole = $newRoleId ? Role::query()->find($newRoleId) : null;
        $keepsAdministratorRole = $newRole?->key === UserRole::Administrator->value;

        if ($keepsAdministratorRole) {
            return;
        }

        $administratorCount = User::query()
            ->whereHas('role', fn ($query) => $query->where('key', UserRole::Administrator->value))
            ->count();

        if ($administratorCount <= 1) {
            throw ValidationException::withMessages([
                'role_id' => 'Mantenha pelo menos um administrador ativo no sistema.',
            ]);
        }
    }

    private function authorizeProfileAccess(?User $currentUser, User $targetUser): void
    {
        abort_unless($currentUser !== null, 403);

        if (
            $currentUser->is($targetUser) ||
            $currentUser->can('manage-users') ||
            $currentUser->can('manage-camps') ||
            $currentUser->can('manage-payments') ||
            $currentUser->can('manage-operations')
        ) {
            return;
        }

        abort(403);
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
}
