<?php

namespace App\Enums;

enum CampPaymentStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Exempted = 'exempted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Partial => 'Parcial',
            self::Paid => 'Pago',
            self::Exempted => 'Liberado sem pagamento',
        };
    }
}
