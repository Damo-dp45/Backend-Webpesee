<?php

namespace App\Security\Voter;

use App\Entity\Fournisseur;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class FournisseurVoter extends Voter
{
    public const VOIR = 'VOIR';
    public const CREER = 'CREER';
    public const MODIFIER = 'MODIFIER';
    public const SUPPRIMER = 'SUPPRIMER';

    public function __construct(
        private Security $security
    )
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if(in_array($attribute, [self::VOIR, self::CREER]) && $subject === 'Fournisseur') {
            return true;
        }
        return in_array($attribute, [self::VOIR, self::MODIFIER, self::SUPPRIMER]) && $subject instanceof Fournisseur;
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

        // Collection
        if($subject === 'Fournisseur') {
            return true; // Filtré par EntrepriseScopeExtension
        }

        /** @var Fournisseur $fournisseur */
        $fournisseur = $subject;
        $sonSite = $fournisseur->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $fournisseur->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            self::VOIR => $memeEntreprise || $sonSite,
            self::MODIFIER  => $memeEntreprise && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles()) || $sonSite),
            self::SUPPRIMER => $memeEntreprise && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles())),
            default => false
        }; /*
            'MODIFIER'  => $this->security->isGranted('ROLE_AGENT') ? $memeEntreprise : $sonSite, /*
                - AGENT/ADMIN → toute l'entreprise
                - OPERATEUR   → uniquement son site
            *
            'SUPPRIMER' => $memeEntreprise && $this->security->isGranted('ROLE_AGENT')
        */
    }
}
