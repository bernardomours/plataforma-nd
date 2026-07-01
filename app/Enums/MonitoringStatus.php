<?php

namespace App\Enums;

enum MonitoringStatus: string
{
    case Pending = 'pending';
    case InProgress = 'running';
    case Completed = 'completed';
    case NoProfessional = 'no_professional';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Visita Pendente',
            self::InProgress => 'Em Andamento',
            self::Completed => 'Em dia',
            self::NoProfessional => 'Sem profissional',
        };
    }

    public function getColorClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            self::InProgress => 'bg-blue-100 text-blue-800 border-blue-200',
            self::Completed => 'bg-green-100 text-green-800 border-green-200',
            self::NoProfessional => 'bg-red-100 text-red-800 border-red-200',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => '⚠️',
            self::InProgress => '⏳',
            self::Completed => '✅',
            self::NoProfessional => '🚨',
        };
    }
}