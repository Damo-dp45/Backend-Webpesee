<?php

namespace App\Security\Voter;

use App\Entity\Site;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class SiteVoter extends Voter
{
    public const VOIR = 'VOIR';
    public const CREER = 'CREER';
    public const MODIFIER = 'MODIFIER';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if(in_array($attribute, [self::VOIR, self::CREER]) && $subject === 'Site') {
            return true;
        }
        return in_array($attribute, [self::VOIR, self::MODIFIER]) && $subject instanceof Site;
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
            return true;
        }

        /* Pour les routes sans objet
         */
        if($subject === 'Site') {
            return match($attribute) {
                self::VOIR  => true, /*
                    - Le filtre géré dans 'EntrepriseScopeExtension' ou pour la logique on peut 'Site_VOIR'..
                */
                self::CREER => $this->security->isGranted('ROLE_ADMIN'),
                default => false
            };
        }

        /**
         * @var Site
         */
        $site = $subject;
        $memeEntreprise = $site->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        switch($attribute) {
            case self::VOIR:
                return $memeEntreprise || $site->getOperateur()?->getId() === $user->getId();
            case self::MODIFIER:
                return $memeEntreprise && (in_array('ROLE_AGENT', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())); /*
                    - On.. '(in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles()))' vu qu'on utilise le 'role_hierarchy' donc l'admin aussi à accès car il hérite 'ROLE_AGENT'
                */
        }
        return false;
    }
}
