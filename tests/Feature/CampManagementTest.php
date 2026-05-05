<?php

namespace Tests\Feature;

use App\Enums\CampPaymentStatus;
use App\Models\Camp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_camp_with_lots(): void
    {
        $admin = User::factory()->administrator()->create();

        $response = $this->actingAs($admin)->post(route('camps.store'), [
            'name' => 'Acampamento 2026',
            'description' => 'Fim de semana de comunhao e organizacao das equipes.',
            'address' => 'Rua do Acampamento 123, Porto',
            'starts_on' => '2026-07-10',
            'ends_on' => '2026-07-12',
            'amount' => '150.00',
            'is_active' => true,
            'lots' => [
                ['name' => 'Primeiro lote', 'amount' => '150.00'],
                ['name' => 'Segundo lote', 'amount' => '180.00'],
            ],
        ]);

        $camp = Camp::query()->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('camps.show', $camp));

        $this->assertDatabaseHas('camps', [
            'name' => 'Acampamento 2026',
            'description' => 'Fim de semana de comunhao e organizacao das equipes.',
            'address' => 'Rua do Acampamento 123, Porto',
            'starts_on' => '2026-07-10 00:00:00',
            'ends_on' => '2026-07-12 00:00:00',
            'amount_cents' => 15000,
        ]);

        $this->assertDatabaseHas('camp_lots', [
            'camp_id' => $camp->id,
            'name' => 'Segundo lote',
            'amount_cents' => 18000,
        ]);
    }

    public function test_camp_end_date_cannot_be_before_start_date(): void
    {
        $admin = User::factory()->administrator()->create();

        $response = $this->actingAs($admin)->post(route('camps.store'), [
            'name' => 'Acampamento 2026',
            'description' => null,
            'address' => 'Rua do Acampamento 123, Porto',
            'starts_on' => '2026-07-10',
            'ends_on' => '2026-07-09',
            'amount' => '150.00',
            'is_active' => true,
            'lots' => [
                ['name' => 'Primeiro lote', 'amount' => '150.00'],
            ],
        ]);

        $response->assertSessionHasErrors('ends_on');
        $this->assertDatabaseCount('camps', 0);
    }

    public function test_participant_cannot_manage_camps(): void
    {
        $participant = User::factory()->participant()->create();

        $response = $this->actingAs($participant)->get(route('camps.index'));

        $response->assertForbidden();
    }

    public function test_administrator_can_view_camp_admin_central(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = Camp::query()->create([
            'name' => 'Acampamento 2026',
            'starts_on' => '2026-07-10',
            'amount_cents' => 15000,
        ]);

        $response = $this->actingAs($admin)->get(route('camps.show', $camp));

        $response->assertOk();
    }

    public function test_payment_page_does_not_auto_link_all_users(): void
    {
        $admin = User::factory()->administrator()->create();
        User::factory()->participant()->count(2)->create();
        $camp = Camp::query()->create([
            'name' => 'Acampamento 2026',
            'starts_on' => '2026-07-10',
            'amount_cents' => 15000,
        ]);

        $camp->lots()->create([
            'name' => 'Primeiro lote',
            'amount_cents' => 15000,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('camps.payments.index', $camp));

        $response->assertOk();
        $this->assertDatabaseCount('camp_payments', 0);
    }

    public function test_administrator_can_link_user_to_camp_with_installments(): void
    {
        $admin = User::factory()->administrator()->create();
        $user = User::factory()->participant()->create();
        $camp = Camp::query()->create([
            'name' => 'Acampamento 2026',
            'starts_on' => '2026-07-10',
            'amount_cents' => 15000,
        ]);
        $lot = $camp->lots()->create([
            'name' => 'Primeiro lote',
            'amount_cents' => 15000,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('camps.payments.store', $camp), [
            'user_id' => $user->id,
            'camp_lot_id' => $lot->id,
            'installments_count' => 3,
            'notes' => 'Vai pagar em tres vezes',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_payments', [
            'camp_id' => $camp->id,
            'user_id' => $user->id,
            'camp_lot_id' => $lot->id,
            'installments_count' => 3,
            'status' => CampPaymentStatus::Pending->value,
        ]);
    }

    public function test_administrator_can_mark_payment_as_paid(): void
    {
        $admin = User::factory()->administrator()->create();
        $user = User::factory()->participant()->create();
        $camp = Camp::query()->create([
            'name' => 'Acampamento 2026',
            'starts_on' => '2026-07-10',
            'amount_cents' => 15000,
        ]);
        $lot = $camp->lots()->create([
            'name' => 'Segundo lote',
            'amount_cents' => 18000,
            'sort_order' => 1,
        ]);
        $payment = $camp->payments()->create([
            'user_id' => $user->id,
            'amount_cents' => 15000,
            'installments_count' => 3,
            'status' => CampPaymentStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->patch(route('camps.payments.update', [$camp, $payment]), [
            'status' => CampPaymentStatus::Paid->value,
            'camp_lot_id' => $lot->id,
            'installments_count' => 3,
            'paid_installments' => 1,
            'amount_paid' => '50.00',
            'notes' => 'Pago em dinheiro',
        ]);

        $response->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame(CampPaymentStatus::Paid, $payment->status);
        $this->assertSame(18000, $payment->amount_cents);
        $this->assertSame(3, $payment->paid_installments);
        $this->assertSame(18000, $payment->amount_paid_cents);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_administrator_can_mark_payment_as_partial(): void
    {
        $admin = User::factory()->administrator()->create();
        $user = User::factory()->participant()->create();
        $camp = Camp::query()->create([
            'name' => 'Acampamento 2026',
            'starts_on' => '2026-07-10',
            'amount_cents' => 15000,
        ]);
        $payment = $camp->payments()->create([
            'user_id' => $user->id,
            'amount_cents' => 15000,
            'installments_count' => 3,
            'status' => CampPaymentStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->patch(route('camps.payments.update', [$camp, $payment]), [
            'status' => CampPaymentStatus::Partial->value,
            'camp_lot_id' => null,
            'installments_count' => 3,
            'paid_installments' => 1,
            'amount_paid' => '50.00',
            'notes' => 'Primeira parcela recebida',
        ]);

        $response->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame(CampPaymentStatus::Partial, $payment->status);
        $this->assertSame(1, $payment->paid_installments);
        $this->assertSame(5000, $payment->amount_paid_cents);
        $this->assertNull($payment->paid_at);
    }

    public function test_received_value_changes_pending_payment_to_partial(): void
    {
        $admin = User::factory()->administrator()->create();
        $user = User::factory()->participant()->create();
        $camp = Camp::query()->create([
            'name' => 'Acampamento 2026',
            'starts_on' => '2026-07-10',
            'amount_cents' => 15000,
        ]);
        $payment = $camp->payments()->create([
            'user_id' => $user->id,
            'amount_cents' => 15000,
            'installments_count' => 3,
            'status' => CampPaymentStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->patch(route('camps.payments.update', [$camp, $payment]), [
            'status' => CampPaymentStatus::Pending->value,
            'camp_lot_id' => null,
            'installments_count' => 3,
            'paid_installments' => 1,
            'amount_paid' => '50.00',
            'notes' => 'Primeira parcela recebida',
        ]);

        $response->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame(CampPaymentStatus::Partial, $payment->status);
        $this->assertSame(1, $payment->paid_installments);
        $this->assertSame(5000, $payment->amount_paid_cents);
    }

    public function test_administrator_can_exempt_user_from_payment(): void
    {
        $admin = User::factory()->administrator()->create();
        $user = User::factory()->participant()->create();
        $camp = Camp::query()->create([
            'name' => 'Acampamento 2026',
            'starts_on' => '2026-07-10',
            'amount_cents' => 15000,
        ]);
        $payment = $camp->payments()->create([
            'user_id' => $user->id,
            'amount_cents' => 15000,
            'installments_count' => 2,
            'status' => CampPaymentStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->patch(route('camps.payments.update', [$camp, $payment]), [
            'status' => CampPaymentStatus::Exempted->value,
            'camp_lot_id' => null,
            'installments_count' => 2,
            'paid_installments' => 0,
            'amount_paid' => '0',
            'notes' => 'Liberado pelo administrador',
        ]);

        $response->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame(CampPaymentStatus::Exempted, $payment->status);
        $this->assertSame($admin->id, $payment->exempted_by);
        $this->assertNotNull($payment->exempted_at);
    }
}
