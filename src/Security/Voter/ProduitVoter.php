<?php

namespace App\Security\Voter;

use App\Entity\Produit;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class ProduitVoter extends Voter
{
    public const VOIR = 'VOIR';
    public const CREER = 'CREER';
    public const MODIFIER = 'MODIFIER';
    public const SUPPRIMER = 'SUPPRIMER';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if(in_array($attribute, [self::VOIR, self::CREER]) && $subject === 'Produit') {
            return true;
        }
        return in_array($attribute, [self::VOIR, self::MODIFIER, self::SUPPRIMER]) && $subject instanceof Produit;
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

        if($subject === 'Produit') {
            return true; /*
                - Le filtre géré par 'EntrepriseScopeExtension' et pour la logique on peut 'Produit_VOIR'..
            */
        }

        /**
         * @var Produit
         */
        $produit = $subject;
        $sonSite = $produit->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $produit->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            self::VOIR => $memeEntreprise || $sonSite,
            self::MODIFIER  => (in_array('ROLE_AGENT', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) ? $memeEntreprise : $sonSite,
            self::SUPPRIMER => $memeEntreprise && (in_array('ROLE_AGENT', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())),
            default => false
        };
    }
}
