<?php

namespace App\Domain\Enum;

enum StatutDemande: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case APPROUVEE = 'APPROUVEE';
    case REJETEE = 'REJETEE';
}