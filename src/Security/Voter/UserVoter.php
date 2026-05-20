<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVoter extends Voter
{
    public const VOIR = 'USER_VOIR';
    public const CREER = 'CREER';
    public const MODIFIER = 'MODIFIER';
    public const SUSPENDRE = 'SUSPENDRE';

    public function __construct(
        private Security $security
    )
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if(in_array($attribute, [self::VOIR, self::CREER]) && $subject === 'User') {
            return true;
        }
        return in_array($attribute, [self::VOIR, self::MODIFIER, self::SUSPENDRE]) && $subject instanceof User; // Ou.. 'in_array($attribute, [self::VOIR]); // && $subject instanceof User'
    }

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
            /*
                - Le super admin peut tout sur les users
                  sauf SUSPENDRE un autre super admin
            */
            if ($attribute === 'SUSPENDRE' && $subject instanceof User) {
                return !in_array('ROLE_SUPER_ADMIN', $subject->getRoles());
            }

            
            return true;
        }

        // Collection
        if($subject === 'User') {
            return match($attribute) {
                self::VOIR  => $this->security->isGranted('ROLE_ADMIN'), // Ou.. 'in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles())'
                self::CREER => $this->security->isGranted('ROLE_ADMIN'),
                default => false
            };
        }

        /**
         * @var User
         */
        $cible = $subject;
        $memeEntreprise = $cible->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            self::VOIR => $memeEntreprise || $cible->getId() === $user->getId(),
            self::MODIFIER  => ($memeEntreprise && $this->security->isGranted('ROLE_ADMIN')) || $cible->getId() === $user->getId(), // Peut modifier son propre profil, '&& (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles()))'
            self::SUSPENDRE => $memeEntreprise && $this->security->isGranted('ROLE_ADMIN') && !in_array('ROLE_ADMIN', $cible->getRoles()), // Un admin ne suspend pas un autre admin, '&& in_array('ROLE_ADMIN', $user->getRoles())' 
            default => false
        };
    }
}
