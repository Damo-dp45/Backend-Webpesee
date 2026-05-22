<?php

namespace App\Entity\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class RechargerSoldeInput
{
    #[Groups(['write:Recharger'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $montant = null;
}