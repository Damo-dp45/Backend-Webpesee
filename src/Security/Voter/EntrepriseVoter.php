<?php

namespace App\Security\Voter;

use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class EntrepriseVoter extends Voter
{
    public const VOIR = 'VOIR';
    public const MODIFIER = 'MODIFIER';

    public function __construct(
        private Security $security
    )
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VOIR, self::MODIFIER]) && $subject instanceof Entreprise;
    }

    /**
     * 
     * @param string $attribute
     * @param Entreprise $subject
     * @param TokenInterface $token
     * @param mixed $vote
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /**
         * @var User
         */
        $user = $token->getUser();

        if(!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');
            return false;
        }

        if($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true; # !! 'utile'
        }

        $meentreprise = $user->getEntreprise()?->getId() === $subject->getId();

        switch ($attribute) {
            case self::VOIR:
                return $meentreprise;
                break;

            case self::MODIFIER:
                return $this->security->isGranted('ROLE_ADMIN') && $meentreprise; /*
                    - On.. pas 'in_array('ROLE_ADMIN', $user->getRoles())' vu qu'on utilise le 'role_hierarchy'
                */
                break;
        }

        return false;
    }
}
