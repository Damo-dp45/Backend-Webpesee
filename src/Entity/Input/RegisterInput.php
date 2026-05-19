<?php

namespace App\Entity\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterInput
{
    #[Assert\NotBlank()]
    #[Assert\Email()]
    #[Groups('write:Register')]
    public ?string $email = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups('write:Register')]
    public ?string $nom = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups('write:Register')]
    public ?string $prenom = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères')]
    #[Groups('write:Register')]
    public ?string $password = null;

    /* Entreprise
     */
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups('write:Register')]
    public ?string $nomEntreprise = null;

    #[Assert\Length(min: 2)]
    #[Groups('write:Register')]
    public ?string $adresse = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 8)]
    #[Groups('write:Register')]
    public ?string $contact1 = null;

    #[Groups('write:Register')]
    public ?string $contact2 = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups('write:Register')]
    public ?string $codeentreprise = null;

    // #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups('write:Register')]
    public ?int $solde = null;
}