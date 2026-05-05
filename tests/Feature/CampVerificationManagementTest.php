<?php

namespace Tests\Feature;

use App\Enums\CampPaymentStatus;
use App\Enums\VerificationMethod;
use App\Enums\VerificationPointType;
use App\Models\Camp;
use App\Models\CampPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampVerificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_verification_point_management(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();

        $response = $this->actingAs($admin)->get(route('camps.verification-points.index', $camp));

        $response->assertOk();
    }

    public function test_monitor_can_view_verification_point_management(): void
    {
        $monitor = User::factory()->monitor()->create();
        $camp = $this->createCamp();

        $response = $this->actingAs($monitor)->get(route('camps.verification-points.index', $camp));

        $response->assertOk();
    }

    public function test_participant_cannot_view_verification_point_management(): void
    {
        $participant = User::factory()->participant()->create();
        $camp = $this->createCamp();

        $response = $this->actingAs($participant)->get(route('camps.verification-points.index', $camp));

        $response->assertForbidden();
    }

    public function test_participant_can_open_scan_page_for_their_point(): void
    {
        $participant = User::factory()->participant()->create();
        $camp = $this->createCamp();
        $this->linkUserToCamp($camp, $participant);
        $point = $camp->verificationPoints()->create([
            'name' => 'Almoco de sabado',
            'type' => VerificationPointType::Presence->value,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($participant)->get(route('camps.verification-points.scan', [$camp, $point]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Camps/VerificationPoints/Scan')
                ->where('point.id', $point->id)
                ->where('point.participant_url', route('camps.verification-points.scan', [$camp, $point]))
                ->where('participant.user.id', $participant->id)
                ->where('canSelfVerify', true)
            );
    }

    public function test_participant_can_confirm_own_verification_from_scan_page(): void
    {
        $participant = User::factory()->participant()->create();
        $camp = $this->createCamp();
        $payment = $this->linkUserToCamp($camp, $participant);
        $point = $camp->verificationPoints()->create([
            'name' => 'Chegada principal',
            'type' => VerificationPointType::CheckIn->value,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($participant)->post(route('camps.verification-points.scan.store', [$camp, $point]));

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_verification_entries', [
            'camp_verification_point_id' => $point->id,
            'camp_payment_id' => $payment->id,
            'user_id' => $participant->id,
            'method' => VerificationMethod::Code->value,
            'verified_by' => null,
        ]);
    }

    public function test_administrator_can_create_verification_point(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();

        $response = $this->actingAs($admin)->post(route('camps.verification-points.store', $camp), [
            'name' => 'Almoco de sabado',
            'type' => VerificationPointType::Presence->value,
            'notes' => 'Contagem antes de abrir a fila',
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_verification_points', [
            'camp_id' => $camp->id,
            'name' => 'Almoco de sabado',
            'type' => VerificationPointType::Presence->value,
        ]);
    }

    public function test_administrator_can_verify_person_by_pin(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $user = User::factory()->participant()->create();
        $payment = $this->linkUserToCamp($camp, $user);
        $point = $camp->verificationPoints()->create([
            'name' => 'Check-in geral',
            'type' => VerificationPointType::CheckIn->value,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('camps.verification-points.verifications.store', [$camp, $point]), [
            'method' => VerificationMethod::Pin->value,
            'pin' => $user->pin,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_verification_entries', [
            'camp_verification_point_id' => $point->id,
            'camp_payment_id' => $payment->id,
            'user_id' => $user->id,
            'method' => VerificationMethod::Pin->value,
            'user_name' => $user->name,
        ]);
    }

    public function test_administrator_can_verify_person_by_code(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $user = User::factory()->participant()->create();
        $payment = $this->linkUserToCamp($camp, $user);
        $point = $camp->verificationPoints()->create([
            'name' => 'Dormir - sabado',
            'type' => VerificationPointType::Presence->value,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('camps.verification-points.verifications.store', [$camp, $point]), [
            'method' => VerificationMethod::Code->value,
            'verification_code' => $user->verification_code,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camp_verification_entries', [
            'camp_verification_point_id' => $point->id,
            'camp_payment_id' => $payment->id,
            'user_id' => $user->id,
            'method' => VerificationMethod::Code->value,
            'user_email' => $user->email,
        ]);
    }

    public function test_same_person_is_not_duplicated_in_the_same_point(): void
    {
        $admin = User::factory()->administrator()->create();
        $camp = $this->createCamp();
        $user = User::factory()->participant()->create();
        $this->linkUserToCamp($camp, $user);
        $point = $camp->verificationPoints()->create([
            'name' => 'Culto da noite',
            'type' => VerificationPointType::Presence->value,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)->post(route('camps.verification-points.verifications.store', [$camp, $point]), [
            'method' => VerificationMethod::Code->value,
            'verification_code' => $user->verification_code,
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('camps.verification-points.verifications.store', [$camp, $point]), [
            'method' => VerificationMethod::Pin->value,
            'pin' => $user->pin,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('camp_verification_entries', 1);
        $this->assertDatabaseHas('camp_verification_entries', [
            'camp_verification_point_id' => $point->id,
            'user_id' => $user->id,
            'method' => VerificationMethod::Pin->value,
        ]);
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
