<?php

namespace App\Enums;

enum ProfessionalFormacao: string
{
    case Estudante = 'estudante';
    case Graduado = 'graduado';
    case PosGraduado = 'pos_graduado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Estudante => 'Estudante',
            self::Graduado => 'Graduado',
            self::PosGraduado => 'Pós-graduado',
        };
    }
}
