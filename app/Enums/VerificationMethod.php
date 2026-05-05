<?php

namespace App\Enums;

enum VerificationMethod: string
{
    case Pin = 'pin';
    case Code = 'code';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Pin => 'PIN',
            self::Code => 'Codigo',
            self::Manual => 'Manual',
        };
    }
}
