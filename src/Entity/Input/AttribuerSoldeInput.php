<?php

namespace App\Entity\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class AttribuerSoldeInput
{
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['write:Attribuer'])]
    public ?int $montant = null;
}