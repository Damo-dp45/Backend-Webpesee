<?php

namespace App\Domain\Enum;

enum ModePaiement: string
{
    case ESPECES = 'ESPECES';
    case MOBILE_MONEY = 'MOBILE_MONEY';
}