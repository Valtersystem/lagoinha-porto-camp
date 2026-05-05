<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Monitor = 'monitor';
    case TeamLeader = 'team_leader';
    case Participant = 'participant';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrador',
            self::Monitor => 'Monitor',
            self::TeamLeader => 'Lider de equipe',
            self::Participant => 'Participante',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Administrator => 'Lider geral com acesso total ao sistema.',
            self::Monitor => 'Participa do acampamento e monitora grupos.',
            self::TeamLeader => 'Responsavel por uma equipe ou grupo especifico.',
            self::Participant => 'Pessoa do acampamento com acesso limitado.',
        };
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Administrator => ['*'],
            self::Monitor => [
                'dashboard.view',
                'participants.view',
                'groups.monitor',
                'operations.manage',
            ],
            self::TeamLeader => [
                'dashboard.view',
                'participants.view_team',
                'teams.manage_own',
            ],
            self::Participant => [
                'dashboard.view',
                'profile.manage',
            ],
        };
    }

    public static function default(): self
    {
        return self::Participant;
    }
}
