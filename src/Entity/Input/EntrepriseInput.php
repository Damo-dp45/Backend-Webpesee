<?php

namespace App\Entity\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class EntrepriseInput
{
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups(['write:EntrepriseInput'])]
    public ?string $nom = null;

    #[Assert\Length(min: 2)]
    #[Groups(['write:EntrepriseInput'])]
    public ?string $adresse = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 3)]
    #[Groups(['write:EntrepriseInput'])]
    public ?string $contact1 = null;

    #[Groups(['write:EntrepriseInput'])]
    public ?string $contact2 = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups(['write:EntrepriseInput'])]
    public ?string $codeentreprise = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups('write:Register')]
    public ?int $solde = null;
    /*
        #[Groups(['write:EntrepriseInput'])]
        public ?int $image = null;    
    */
}