<?php

namespace Tests\Feature;

use App\Enums\CampPaymentStatus;
use App\Enums\UserSex;
use App\Models\Camp;
use App\Models\CampPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampRoomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_room_management(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();

        $response = $this->actingAs($admin)->get(route('camps.rooms.index', $camp));

        $response->assertOk();
    }

    public function test_administrator_can_create_room(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();

        $response = $this->actingAs($admin)->post(route('camps.rooms.store', $camp), [
            'name' => 'Quarto 01',
            'sex' => UserSex::Male->value,
            'capacity' => 6,
            'notes' => 'Beliches',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_rooms', [
            'camp_id' => $camp->id,
            'name' => 'Quarto 01',
            'sex' => UserSex::Male->value,
            'capacity' => 6,
        ]);
    }

    public function test_administrator_can_assign_person_to_room(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $user = User::factory()->participant()->state(['sex' => UserSex::Male->value])->create();
        $payment = $this->linkUserToCamp($camp, $user);
        $room = $camp->rooms()->create([
            'name' => 'Quarto 01',
            'sex' => UserSex::Male->value,
            'capacity' => 4,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->patch(route('camps.rooms.assignments.update', $camp), [
            'camp_payment_id' => $payment->id,
            'camp_room_id' => $room->id,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_payments', [
            'id' => $payment->id,
            'camp_room_id' => $room->id,
        ]);
    }

    public function test_room_capacity_is_enforced(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $firstPayment = $this->linkUserToCamp($camp, User::factory()->participant()->state(['sex' => UserSex::Male->value])->create());
        $secondPayment = $this->linkUserToCamp($camp, User::factory()->participant()->state(['sex' => UserSex::Male->value])->create());
        $room = $camp->rooms()->create([
            'name' => 'Quarto 01',
            'sex' => UserSex::Male->value,
            'capacity' => 1,
            'sort_order' => 1,
        ]);

        $firstPayment->update(['camp_room_id' => $room->id]);

        $response = $this->actingAs($admin)->patch(route('camps.rooms.assignments.update', $camp), [
            'camp_payment_id' => $secondPayment->id,
            'camp_room_id' => $room->id,
        ]);

        $response->assertSessionHasErrors('camp_room_id');
        $this->assertNull($secondPayment->refresh()->camp_room_id);
    }

    public function test_deleting_room_unassigns_people(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $payment = $this->linkUserToCamp($camp, User::factory()->participant()->state(['sex' => UserSex::Female->value])->create());
        $room = $camp->rooms()->create([
            'name' => 'Quarto 01',
            'sex' => UserSex::Female->value,
            'capacity' => 4,
            'sort_order' => 1,
        ]);

        $payment->update(['camp_room_id' => $room->id]);

        $response = $this->actingAs($admin)->delete(route('camps.rooms.destroy', [$camp, $room]));

        $response->assertSessionHasNoErrors();
        $this->assertNull($payment->refresh()->camp_room_id);
        $this->assertDatabaseMissing('camp_rooms', ['id' => $room->id]);
    }

    public function test_people_cannot_be_assigned_to_room_for_different_sex(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $payment = $this->linkUserToCamp(
            $camp,
            User::factory()->participant()->state(['sex' => UserSex::Female->value])->create(),
        );
        $room = $camp->rooms()->create([
            'name' => 'Quarto Masculino',
            'sex' => UserSex::Male->value,
            'capacity' => 4,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->patch(route('camps.rooms.assignments.update', $camp), [
            'camp_payment_id' => $payment->id,
            'camp_room_id' => $room->id,
        ]);

        $response->assertSessionHasErrors('camp_room_id');
        $this->assertNull($payment->refresh()->camp_room_id);
    }

    private function createCamp(): Camp
    {
        return Camp::query()->create([
            'name' => 'Acampamento 2026',
            'starts_on' => '2026-07-10',
            'amount_cents' => 15000,
        ]);
    }

    private function linkUserToCamp(Camp $camp, User $user): CampPayment
    {
        return $camp->payments()->create([
            'user_id' => $user->id,
            'amount_cents' => 15000,
            'installments_count' => 1,
            'status' => CampPaymentStatus::Pending,
        ]);
    }
}
