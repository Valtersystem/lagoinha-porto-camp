<?php

namespace Tests\Feature;

use App\Enums\CampPaymentStatus;
use App\Enums\UserSex;
use App\Models\Camp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_dashboard_shows_badge_and_financial_data(): void
    {
        $participant = User::factory()
            ->participant()
            ->state([
                'name' => 'Ana Martins',
                'phone' => '+351 910 000 111',
                'sex' => UserSex::Female->value,
            ])
            ->create();

        $camp = Camp::query()->create([
            'name' => 'Porto Camp 2026',
            'description' => 'Fim de semana de acampamento.',
            'address' => 'Rua do Acampamento 123, Porto',
            'starts_on' => '2026-07-10',
            'ends_on' => '2026-07-12',
            'amount_cents' => 16000,
            'is_active' => true,
        ]);

        $lot = $camp->lots()->create([
            'name' => 'Segundo lote',
            'amount_cents' => 18000,
            'sort_order' => 1,
        ]);

        $room = $camp->rooms()->create([
            'name' => 'Quarto Magnolia',
            'sex' => UserSex::Female->value,
            'capacity' => 6,
            'sort_order' => 1,
        ]);

        $team = $camp->teams()->create([
            'name' => 'Equipe Azul',
            'sort_order' => 1,
        ]);

        $camp->payments()->create([
            'user_id' => $participant->id,
            'camp_lot_id' => $lot->id,
            'camp_room_id' => $room->id,
            'camp_team_id' => $team->id,
            'amount_cents' => 18000,
            'amount_paid_cents' => 6000,
            'installments_count' => 4,
            'paid_installments' => 1,
            'status' => CampPaymentStatus::Partial,
            'notes' => 'Primeira parcela recebida',
        ]);

        $response = $this->actingAs($participant)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('isAdministrator', false)
                ->where('participant.user.name', 'Ana Martins')
                ->where('participant.user.phone', '+351 910 000 111')
                ->where('participant.participation.camp.name', 'Porto Camp 2026')
                ->where('participant.participation.room.name', 'Quarto Magnolia')
                ->where('participant.participation.team.name', 'Equipe Azul')
                ->where('participant.participation.lot.name', 'Segundo lote')
                ->where('participant.participation.status', CampPaymentStatus::Partial->value)
                ->where('participant.participation.amount', '180.00')
                ->where('participant.participation.amount_paid', '60.00')
                ->where('participant.participation.amount_open', '120.00')
                ->where('participant.participation.paid_installments', 1)
                ->where('participant.participation.installments_count', 4)
            );
    }

    public function test_administrator_dashboard_keeps_admin_flag(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = $this->actingAs($administrator)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('isAdministrator', true)
                ->where('participant.user.id', $administrator->id)
                ->where('participant.participation', null)
            );
    }
}
