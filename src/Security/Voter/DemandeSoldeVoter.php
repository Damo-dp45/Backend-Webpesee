<?php

namespace App\Security\Voter;

use App\Entity\DemandeSolde;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class DemandeSoldeVoter extends Voter
{
    public const VOIR = 'VOIR';
    public const CREER = 'CREER';
    public const MODIFIER = 'MODIFIER';
    public const TRAITER = 'TRAITER';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if(in_array($attribute, [self::VOIR, self::CREER]) && $subject === 'DemandeSolde') {
            return true;
        }
        return in_array($attribute, [self::VOIR, self::TRAITER]) && $subject instanceof DemandeSolde;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if(!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');
            return false;
        }

        /* Pour les routes sans objet
         */
        if($subject === 'DemandeSolde') {
            return match($attribute) {
                self::VOIR  => true, /*
                - Le filtre géré par 'EntrepriseScopeExtension' et pour la logique on peut 'DemandeSolde_VOIR'..
            */
                self::CREER => $this->security->isGranted('ROLE_OPERATEUR'),
                default => false
            };
            return true;
        }

        /**
         * @var DemandeSolde
         */
        $demande = $subject;
        $sonSite = $demande->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $demande->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            'VOIR' => $memeEntreprise || $sonSite,
            'TRAITER' => $memeEntreprise && in_array('ROLE_AGENT', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()), // La demande est traité par l'agent ou l'admin
            default => false
        };
    }
}
