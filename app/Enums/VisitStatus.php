<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Canceled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Completed => 'Realizada',
            self::Canceled => 'Cancelada',
        };
    }
}