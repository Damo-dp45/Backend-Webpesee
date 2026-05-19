<?php

namespace App\Domain\Enum;

enum SiteStatus: string
{
    case ACTIF = 'ACTIF';
    case BLOQUE = 'BLOQUE';
}