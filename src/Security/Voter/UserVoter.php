<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class UserVoter extends Voter
{
    public const VOIR = 'VOIR';
    public const CREER = 'CREER';
    public const MODIFIER = 'MODIFIER';
    public const SUSPENDRE = 'SUSPENDRE';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if(in_array($attribute, [self::VOIR, self::CREER]) && $subject === 'User') {
            return true;
        }
        return in_array($attribute, [self::VOIR, self::MODIFIER, self::SUSPENDRE]) && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if(!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');
            return false;
        }

        if($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            if($attribute === 'SUSPENDRE' && $subject instanceof User) { /*
                - Pour empêcher un super admin de suspendre un autre super admin
            */
                return !in_array('ROLE_SUPER_ADMIN', $subject->getRoles());
            }
            return true;
        }

        /* Pour les routes sans objet
         */
        if($subject === 'User') {
            switch($attribute) {
                case self::VOIR:
                    return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles()); /*
                        - Ou.. 'in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles())' mais vu.. 'role_hierarchy' un admin à accès et pour le filtre 'UserEntrepriseExtension'
                    */
                case self::CREER:
                    return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles());
            }
            return false;
        }

        /**
         * @var User
         */
        $cible = $subject; /*
            $memeentreprise = $cible->getEntreprise()?->getId() === $user->getEntreprise()?->getId(); -- On n'a pas besoin il est géré par le 'UserEntrepriseExtension'
        */
        switch($attribute) {
            case self::VOIR:
                return true; // Ou.. '$memeentreprise || $cible->getId() === $user->getId()'
            case self::MODIFIER:
                return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles());
            case self::SUSPENDRE:
                return $this->security->isGranted('ROLE_ADMIN') && !in_array('ROLE_ADMIN', $cible->getRoles()); // On.. un admin de suspendre un autre admin
        }
        return false;
    }
}
