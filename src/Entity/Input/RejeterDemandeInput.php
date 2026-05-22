<?php

namespace App\Entity\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class RejeterDemandeInput
{
    #[Groups(['write:Rejeter'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5)]
    public ?string $motif = null;
}