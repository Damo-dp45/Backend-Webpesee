<?php

namespace App\Entity\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 4)]
    #[Groups(['write:User:password'])]
    public string $currentPassword;

    #[Assert\NotBlank]
    #[Assert\Length(min: 4)]
    #[Groups(['write:User:password'])]
    public string $newPassword;
}