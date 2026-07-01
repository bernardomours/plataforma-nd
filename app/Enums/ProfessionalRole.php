<?php

namespace App\Enums;

enum ProfessionalRole: string
{
    case Supervisor = 'supervisor';
    case Coordinator = 'coordinator';
    case Therapist = 'therapist';
    case Uncategorized = 'uncategorized';

    public function getLabel(): string // Apenas o método, sem implementar contrato
    {
        return match ($this) {
            self::Supervisor => 'Supervisor',
            self::Coordinator => 'Coordenador',
            self::Therapist => 'AT/Profissional',
            self::Uncategorized => 'Não Registrado',
        };
    }
}