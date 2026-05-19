<?php

namespace App\Domain\Enum;

enum Typemouvement: string
{
    case CREDIT = 'CREDIT';
    case DEBIT = 'DEBIT';
}