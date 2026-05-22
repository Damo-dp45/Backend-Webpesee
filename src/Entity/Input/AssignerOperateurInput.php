<?php

namespace App\Entity\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class AssignerOperateurInput
{
    #[Assert\NotNull]
    #[Groups(['write:Assigner'])]
    public ?int $operateurId = null;
}