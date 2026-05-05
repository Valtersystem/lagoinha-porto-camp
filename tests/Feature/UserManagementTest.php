<?php

namespace Tests\Feature;

use App\Enums\CampPaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserSex;
use App\Models\Camp;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_user_management(): void
    {
        $admin = User::factory()->administrator()->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
    }

    public function test_participant_cannot_view_user_management(): void
    {
        $participant = User::factory()->participant()->create();

        $response = $this->actingAs($participant)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_administrator_can_create_user_with_role(): void
    {
        Storage::fake('public');

        $admin = User::factory()->administrator()->create();
        $monitorRole = Role::firstOrCreateFor(UserRole::Monitor);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Monitor Teste',
            'email' => 'monitor@example.com',
            'phone' => '+351 910 000 111',
            'photo' => UploadedFile::fake()->image('monitor.jpg'),
            'sex' => UserSex::Male->value,
            'role_id' => $monitorRole->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $createdUser = User::query()->where('email', 'monitor@example.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => 'monitor@example.com',
            'phone' => '+351 910 000 111',
            'sex' => UserSex::Male->value,
            'role_id' => $monitorRole->id,
        ]);
        $this->assertNotNull($createdUser->pin);
        $this->assertSame(4, strlen((string) $createdUser->pin));
        $this->assertNotNull($createdUser->verification_code);
        $this->assertNotNull($createdUser->photo_path);
        Storage::disk('public')->assertExists($createdUser->photo_path);
    }

    public function test_administrator_can_view_user_profile_page(): void
    {
        $admin = User::factory()->administrator()->create();
        $participant = User::factory()->participant()->create();
        $camp = Camp::query()->create([
            'name' => 'Acampamento 2026',
            'description' => 'Perfil operacional',
            'address' => 'Rua do Acampamento 123, Porto',
            'starts_on' => '2026-07-10',
            'ends_on' => '2026-07-12',
            'amount_cents' => 15000,
        ]);
        $room = $camp->rooms()->create([
            'name' => 'Quarto 01',
            'sex' => $participant->sex?->value,
            'capacity' => 4,
            'sort_order' => 1,
        ]);

        $camp->payments()->create([
            'user_id' => $participant->id,
            'camp_room_id' => $room->id,
            'amount_cents' => 15000,
            'installments_count' => 2,
            'status' => CampPaymentStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $participant));

        $response->assertOk();
    }

    public function test_user_can_view_own_profile_page(): void
    {
        $participant = User::factory()->participant()->create();

        $response = $this->actingAs($participant)->get(route('users.show', $participant));

        $response->assertOk();
    }

    public function test_participant_cannot_view_another_user_profile_page(): void
    {
        $participant = User::factory()->participant()->create();
        $otherUser = User::factory()->participant()->create();

        $response = $this->actingAs($participant)->get(route('users.show', $otherUser));

        $response->assertForbidden();
    }

    public function test_last_administrator_cannot_be_demoted(): void
    {
        $admin = User::factory()->administrator()->create();
        $participantRole = Role::firstOrCreateFor(UserRole::Participant);

        $response = $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'sex' => $admin->sex->value,
            'role_id' => $participantRole->id,
            'password' => null,
            'password_confirmation' => null,
        ]);

        $response->assertSessionHasErrors('role_id');

        $this->assertTrue($admin->refresh()->isAdministrator());
    }

    public function test_administrator_cannot_delete_self(): void
    {
        $admin = User::factory()->administrator()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertSessionHasErrors('user');
        $this->assertNotNull($admin->fresh());
    }
}
