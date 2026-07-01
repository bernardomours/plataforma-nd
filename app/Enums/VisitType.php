<?php

namespace App\Enums;

enum VisitType: string
{
    case Supervision = 'supervision';
    case Coordination = 'coordination';

    public function getLabel(): string
    {
        return match ($this) {
            self::Supervision => 'Supervisão',
            self::Coordination => 'Coordenação',
        };
    }
}