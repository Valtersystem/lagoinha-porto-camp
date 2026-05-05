<?php

namespace Tests\Feature;

use App\Enums\CampPaymentStatus;
use App\Enums\UserSex;
use App\Models\Camp;
use App\Models\CampPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_team_management(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();

        $response = $this->actingAs($admin)->get(route('camps.teams.index', $camp));

        $response->assertOk();
    }

    public function test_administrator_can_create_team_with_leader(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $leaderPayment = $this->linkUserToCamp(
            $camp,
            User::factory()->teamLeader()->state(['sex' => UserSex::Female->value])->create(),
        );

        $response = $this->actingAs($admin)->post(route('camps.teams.store', $camp), [
            'name' => 'Equipe Azul',
            'leader_camp_payment_id' => $leaderPayment->id,
            'notes' => 'Apoio geral',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_teams', [
            'camp_id' => $camp->id,
            'name' => 'Equipe Azul',
            'leader_camp_payment_id' => $leaderPayment->id,
        ]);

        $this->assertDatabaseHas('camp_payments', [
            'id' => $leaderPayment->id,
            'camp_team_id' => $camp->teams()->first()->id,
        ]);
    }

    public function test_administrator_can_assign_person_to_team(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $payment = $this->linkUserToCamp(
            $camp,
            User::factory()->participant()->state(['sex' => UserSex::Male->value])->create(),
        );
        $team = $camp->teams()->create([
            'name' => 'Equipe Azul',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->patch(route('camps.teams.assignments.update', $camp), [
            'camp_payment_id' => $payment->id,
            'camp_team_id' => $team->id,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_payments', [
            'id' => $payment->id,
            'camp_team_id' => $team->id,
        ]);
    }

    public function test_moving_leader_out_of_team_clears_leader(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $leaderPayment = $this->linkUserToCamp(
            $camp,
            User::factory()->teamLeader()->state(['sex' => UserSex::Female->value])->create(),
        );
        $team = $camp->teams()->create([
            'name' => 'Equipe Azul',
            'leader_camp_payment_id' => $leaderPayment->id,
            'sort_order' => 1,
        ]);
        $leaderPayment->update(['camp_team_id' => $team->id]);

        $response = $this->actingAs($admin)->patch(route('camps.teams.assignments.update', $camp), [
            'camp_payment_id' => $leaderPayment->id,
            'camp_team_id' => null,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull($team->refresh()->leader_camp_payment_id);
        $this->assertNull($leaderPayment->refresh()->camp_team_id);
    }

    public function test_deleting_team_unassigns_members(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $payment = $this->linkUserToCamp(
            $camp,
            User::factory()->participant()->state(['sex' => UserSex::Male->value])->create(),
        );
        $team = $camp->teams()->create([
            'name' => 'Equipe Azul',
            'sort_order' => 1,
        ]);
        $payment->update(['camp_team_id' => $team->id]);

        $response = $this->actingAs($admin)->delete(route('camps.teams.destroy', [$camp, $team]));

        $response->assertSessionHasNoErrors();
        $this->assertNull($payment->refresh()->camp_team_id);
        $this->assertDatabaseMissing('camp_teams', ['id' => $team->id]);
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
