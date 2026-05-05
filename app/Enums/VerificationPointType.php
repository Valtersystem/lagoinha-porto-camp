<?php

namespace App\Enums;

enum VerificationPointType: string
{
    case Presence = 'presence';
    case CheckIn = 'check_in';

    public function label(): string
    {
        return match ($this) {
            self::Presence => 'Presenca',
            self::CheckIn => 'Check-in',
        };
    }
}
