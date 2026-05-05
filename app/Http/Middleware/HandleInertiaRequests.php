<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing('role');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'photo_url' => $user->photo_path ? Storage::disk('public')->url($user->photo_path) : null,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'key' => $user->role->key,
                        'name' => $user->role->name,
                        'description' => $user->role->description,
                    ] : null,
                ] : null,
                'can' => [
                    'manageUsers' => $user?->can('manage-users') ?? false,
                    'manageCamps' => $user?->can('manage-camps') ?? false,
                    'managePayments' => $user?->can('manage-payments') ?? false,
                    'manageOperations' => $user?->can('manage-operations') ?? false,
                ],
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
        ];
    }
}
