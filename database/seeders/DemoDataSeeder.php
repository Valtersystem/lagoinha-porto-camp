<?php

namespace Database\Seeders;

use App\Enums\CampPaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserSex;
use App\Enums\VerificationMethod;
use App\Enums\VerificationPointType;
use App\Models\Camp;
use App\Models\CampLot;
use App\Models\CampPayment;
use App\Models\CampRoom;
use App\Models\CampTeam;
use App\Models\CampVerificationEntry;
use App\Models\CampVerificationPoint;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private const PEOPLE_PER_SEX = 150;

    /**
     * Seed realistic demo data for local development.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $passwordHash = Hash::make('password');

        $admin = $this->ensureAdministrator($passwordHash);
        $camp = $this->ensureCamp();

        $this->resetCampData($camp);

        $lots = $this->createLots($camp);
        $rooms = $this->createRooms($camp);
        $users = $this->createUsers($passwordHash);
        $payments = $this->createPayments($camp, $users, $lots, $admin);

        $this->assignRooms($payments, $rooms);
        $this->createTeams($camp, $payments);
        $this->createVerificationPoints($camp, $payments, $admin);
    }

    private function ensureAdministrator(string $passwordHash): User
    {
        $adminRole = Role::firstOrCreateFor(UserRole::Administrator);

        return User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'phone' => '+351 900 000 001',
                'sex' => UserSex::Male->value,
                'password' => $passwordHash,
                'email_verified_at' => now(),
                'role_id' => $adminRole->id,
            ],
        );
    }

    private function ensureCamp(): Camp
    {
        return Camp::query()->updateOrCreate(
            ['name' => 'Acampamento Demo 2026'],
            [
                'description' => 'Dados ficticios para testar pessoas, financeiro, quartos e equipes antes do evento.',
                'address' => 'Estrada do Acampamento 123, Porto',
                'starts_on' => '2026-07-10',
                'ends_on' => '2026-07-12',
                'amount_cents' => 16000,
                'is_active' => true,
            ],
        );
    }

    private function resetCampData(Camp $camp): void
    {
        $camp->teams()->update(['leader_camp_payment_id' => null]);
        $camp->payments()->update([
            'camp_lot_id' => null,
            'camp_room_id' => null,
            'camp_team_id' => null,
        ]);

        $camp->verificationPoints()->delete();
        $camp->teams()->delete();
        $camp->rooms()->delete();
        $camp->payments()->delete();
        $camp->lots()->delete();
    }

    /**
     * @return array<int, CampLot>
     */
    private function createLots(Camp $camp): array
    {
        return [
            $camp->lots()->create([
                'name' => 'Primeiro lote',
                'amount_cents' => 14000,
                'sort_order' => 1,
            ]),
            $camp->lots()->create([
                'name' => 'Segundo lote',
                'amount_cents' => 16000,
                'sort_order' => 2,
            ]),
            $camp->lots()->create([
                'name' => 'Ultimo lote',
                'amount_cents' => 18000,
                'sort_order' => 3,
            ]),
        ];
    }

    /**
     * @return array<string, array<int, CampRoom>>
     */
    private function createRooms(Camp $camp): array
    {
        $rooms = [
            UserSex::Male->value => [],
            UserSex::Female->value => [],
        ];

        foreach ([UserSex::Male, UserSex::Female] as $sex) {
            $prefix = $sex === UserSex::Male ? 'Quarto M' : 'Quarto F';
            $notes = $sex === UserSex::Male ? 'Alojamento masculino' : 'Alojamento feminino';

            for ($index = 1; $index <= 25; $index++) {
                $rooms[$sex->value][] = $camp->rooms()->create([
                    'name' => $prefix.' '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'sex' => $sex->value,
                    'capacity' => 6,
                    'notes' => $notes,
                    'sort_order' => $index,
                ]);
            }
        }

        return $rooms;
    }

    /**
     * @return array<int, User>
     */
    private function createUsers(string $passwordHash): array
    {
        $teamLeaderRole = Role::firstOrCreateFor(UserRole::TeamLeader);
        $monitorRole = Role::firstOrCreateFor(UserRole::Monitor);
        $participantRole = Role::firstOrCreateFor(UserRole::Participant);

        $maleNames = [
            'Miguel', 'Joao', 'Davi', 'Gabriel', 'Lucas', 'Matheus', 'Tiago', 'Daniel', 'Rafael', 'Samuel',
            'Pedro', 'Andre', 'Felipe', 'Gustavo', 'Henrique', 'Leandro', 'Nuno', 'Bruno', 'Ricardo', 'Tomas',
        ];
        $femaleNames = [
            'Maria', 'Ana', 'Sofia', 'Beatriz', 'Leonor', 'Carolina', 'Matilde', 'Ines', 'Rita', 'Margarida',
            'Joana', 'Clara', 'Laura', 'Mariana', 'Sara', 'Teresa', 'Catarina', 'Francisca', 'Isabel', 'Eva',
        ];
        $surnames = [
            'Silva', 'Santos', 'Ferreira', 'Pereira', 'Oliveira', 'Costa', 'Rodrigues', 'Martins', 'Jesus', 'Sousa',
            'Fernandes', 'Goncalves', 'Gomes', 'Lopes', 'Marques', 'Alves', 'Almeida', 'Ribeiro', 'Pinto', 'Carvalho',
        ];

        $users = [];
        $counter = 1;

        foreach ([UserSex::Male, UserSex::Female] as $sex) {
            $firstNames = $sex === UserSex::Male ? $maleNames : $femaleNames;

            for ($index = 0; $index < self::PEOPLE_PER_SEX; $index++) {
                $role = match (true) {
                    $index < 6 => $teamLeaderRole,
                    $index < 18 => $monitorRole,
                    default => $participantRole,
                };

                $firstName = $firstNames[$index % count($firstNames)];
                $surname = $surnames[($index + intdiv($index, count($firstNames))) % count($surnames)];
                $name = $firstName.' '.$surname;
                $email = 'demo.'.str_pad((string) $counter, 3, '0', STR_PAD_LEFT).'@portocamp.test';
                $phone = '+351 91'.str_pad((string) $counter, 7, '0', STR_PAD_LEFT);

                $users[] = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'phone' => $phone,
                        'sex' => $sex->value,
                        'password' => $passwordHash,
                        'email_verified_at' => now(),
                        'role_id' => $role->id,
                    ],
                );

                $counter++;
            }
        }

        return $users;
    }

    /**
     * @param array<int, User> $users
     * @param array<int, CampLot> $lots
     * @return array<int, CampPayment>
     */
    private function createPayments(Camp $camp, array $users, array $lots, User $admin): array
    {
        $payments = [];

        foreach (array_values($users) as $index => $user) {
            $lot = $lots[$index % count($lots)];
            $installments = [1, 2, 3, 4][$index % 4];
            $status = $this->paymentStatusFor($index);

            if ($status === CampPaymentStatus::Partial && $installments === 1) {
                $installments = 3;
            }

            $paidInstallments = 0;
            $amountPaidCents = 0;
            $paidAt = null;
            $exemptedAt = null;
            $exemptedBy = null;

            if ($status === CampPaymentStatus::Partial) {
                $paidInstallments = max(1, min($installments - 1, ($index % max($installments - 1, 1)) + 1));
                $amountPaidCents = intdiv($lot->amount_cents * $paidInstallments, $installments);
            }

            if ($status === CampPaymentStatus::Paid) {
                $paidInstallments = $installments;
                $amountPaidCents = $lot->amount_cents;
                $paidAt = now()->subDays(($index % 21) + 1);
            }

            if ($status === CampPaymentStatus::Exempted) {
                $exemptedAt = now()->subDays(($index % 10) + 1);
                $exemptedBy = $admin->id;
            }

            $payments[] = CampPayment::query()->create([
                'camp_id' => $camp->id,
                'user_id' => $user->id,
                'camp_lot_id' => $lot->id,
                'amount_cents' => $lot->amount_cents,
                'installments_count' => $installments,
                'paid_installments' => $paidInstallments,
                'amount_paid_cents' => $amountPaidCents,
                'status' => $status,
                'paid_at' => $paidAt,
                'exempted_at' => $exemptedAt,
                'exempted_by' => $exemptedBy,
                'notes' => $this->paymentNoteFor($status),
            ]);
        }

        return $payments;
    }

    private function paymentStatusFor(int $index): CampPaymentStatus
    {
        return match (true) {
            $index % 20 === 0 => CampPaymentStatus::Exempted,
            $index % 5 === 0 => CampPaymentStatus::Partial,
            $index % 3 === 0 => CampPaymentStatus::Paid,
            default => CampPaymentStatus::Pending,
        };
    }

    private function paymentNoteFor(CampPaymentStatus $status): ?string
    {
        return match ($status) {
            CampPaymentStatus::Pending => null,
            CampPaymentStatus::Partial => 'Pagamento parcial registrado para teste.',
            CampPaymentStatus::Paid => 'Pagamento ficticio confirmado.',
            CampPaymentStatus::Exempted => 'Liberado pelo administrador para teste.',
        };
    }

    /**
     * @param array<int, CampPayment> $payments
     * @param array<string, array<int, CampRoom>> $rooms
     */
    private function assignRooms(array $payments, array $rooms): void
    {
        $roomIndexes = [
            UserSex::Male->value => 0,
            UserSex::Female->value => 0,
        ];

        foreach ($payments as $payment) {
            $payment->loadMissing('user');

            $sex = $payment->user->sex?->value;

            if ($sex === null || ! isset($rooms[$sex])) {
                continue;
            }

            $room = $rooms[$sex][intdiv($roomIndexes[$sex], 6)] ?? null;

            if ($room === null) {
                continue;
            }

            $payment->update(['camp_room_id' => $room->id]);
            $roomIndexes[$sex]++;
        }
    }

    /**
     * @param array<int, CampPayment> $payments
     */
    private function createTeams(Camp $camp, array $payments): void
    {
        $teamData = [
            ['Equipe Azul', 'Acolhimento e recepcao'],
            ['Equipe Verde', 'Apoio aos quartos'],
            ['Equipe Amarela', 'Logistica e materiais'],
            ['Equipe Vermelha', 'Monitoria dos adolescentes'],
            ['Equipe Branca', 'Intercessao e cuidado pastoral'],
            ['Equipe Laranja', 'Refeitorio e cozinha'],
            ['Equipe Roxa', 'Louvor e programacao'],
            ['Equipe Cinza', 'Limpeza e apoio geral'],
            ['Equipe Preta', 'Seguranca e circulacao'],
            ['Equipe Turquesa', 'Check-in e secretaria'],
            ['Equipe Vinho', 'Midia e comunicacao'],
            ['Equipe Dourada', 'Atividades e jogos'],
        ];

        $teams = [];

        foreach ($teamData as $index => [$name, $notes]) {
            $teams[] = $camp->teams()->create([
                'name' => $name,
                'notes' => $notes,
                'sort_order' => $index + 1,
            ]);
        }

        foreach (array_values($payments) as $index => $payment) {
            $team = $teams[$index % count($teams)];
            $payment->update(['camp_team_id' => $team->id]);
        }

        $leaderPayments = CampPayment::query()
            ->with('user.role')
            ->whereBelongsTo($camp)
            ->whereHas('user.role', fn ($query) => $query->where('key', UserRole::TeamLeader->value))
            ->orderBy('id')
            ->get()
            ->values();

        foreach ($teams as $index => $team) {
            $leaderPayment = $leaderPayments[$index] ?? null;

            if ($leaderPayment === null) {
                continue;
            }

            $leaderPayment->update(['camp_team_id' => $team->id]);
            $team->update(['leader_camp_payment_id' => $leaderPayment->id]);
        }
    }

    /**
     * @param array<int, CampPayment> $payments
     */
    private function createVerificationPoints(Camp $camp, array $payments, User $admin): void
    {
        $pointData = [
            ['Check-in geral', VerificationPointType::CheckIn, 'Rececao principal na chegada', 4, now()->subDays(3)],
            ['Almoco de sabado', VerificationPointType::Presence, 'Contagem antes da refeicao', 5, now()->subDays(2)],
            ['Culto da noite', VerificationPointType::Presence, 'Verificacao antes do inicio do culto', 6, now()->subDay()],
            ['Dormir - sabado', VerificationPointType::Presence, 'Fecho do dia nos quartos', 7, now()->subHours(12)],
        ];

        foreach ($pointData as $index => [$name, $type, $notes, $modulus, $baseTime]) {
            $point = CampVerificationPoint::query()->create([
                'camp_id' => $camp->id,
                'name' => $name,
                'type' => $type->value,
                'notes' => $notes,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            foreach (array_values($payments) as $paymentIndex => $payment) {
                if ($paymentIndex % $modulus === 0) {
                    continue;
                }

                $payment->loadMissing(['user.role', 'room', 'team']);

                CampVerificationEntry::query()->create([
                    'camp_verification_point_id' => $point->id,
                    'camp_payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'verified_by' => $admin->id,
                    'method' => match ($paymentIndex % 3) {
                        0 => VerificationMethod::Code->value,
                        1 => VerificationMethod::Pin->value,
                        default => VerificationMethod::Manual->value,
                    },
                    'verified_at' => $baseTime->copy()->addMinutes($paymentIndex * 3),
                    'user_name' => $payment->user->name,
                    'user_email' => $payment->user->email,
                    'user_phone' => $payment->user->phone,
                    'user_role_name' => $payment->user->role?->name,
                    'team_name' => $payment->team?->name,
                    'room_name' => $payment->room?->name,
                    'sex_label' => $payment->user->sex?->label(),
                ]);
            }
        }
    }
}
