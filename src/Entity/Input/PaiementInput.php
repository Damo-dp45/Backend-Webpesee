<?php

namespace App\Entity\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class PaiementInput
{
    #[Groups(['write:PaiementInput'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $montant = null;

    #[Groups(['write:PaiementInput'])]
    #[Assert\NotBlank]
    public ?string $modepaiement = null; // ModePaiement::ESPECES / MOBILE_MONEY

    #[Groups(['write:PaiementInput'])]
    #[Assert\NotNull]
    public ?int $siteId = null;

    #[Groups(['write:PaiementInput'])]
    #[Assert\NotNull]
    public ?int $fournisseurId = null;

    #[Groups(['write:PaiementInput'])]
    public ?int $operationId = null; // Optionnel
}