<?php

namespace App\Security\Voter;

use App\Entity\DemandeSolde;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class DemandeSoldeVoter extends Voter
{
    public const EDIT = 'POST_EDIT';

    public function __construct(
        private Security $security
    )
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if(in_array($attribute, ['VOIR', 'CREER']) && $subject === 'DemandeSolde') {
            return true;
        }
        return in_array($attribute, ['VOIR', 'TRAITER']) && $subject instanceof DemandeSolde;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        if ($subject === 'DemandeSolde') {
            return match($attribute) {
                'VOIR'  => true, // Filtré par EntrepriseScopeExtension
                'CREER' => $this->security->isGranted('ROLE_OPERATEUR')
                        && !$this->security->isGranted('ROLE_AGENT'),
                default => false
            };
            return true; // Filtré par EntrepriseScopeExtension
        }

        /** @var DemandeSolde $demande */
        $demande = $subject;
        $sonSite = $demande->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $demande->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            'VOIR'    => $memeEntreprise || $sonSite,
            // Seul l'opérateur du site peut créer une demande
            // Admin ou Agent traitent les demandes
            // 'TRAITER' => $memeEntreprise && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles())),
            'TRAITER' => $memeEntreprise && $this->security->isGranted('ROLE_AGENT'),
            default   => false
        };

    }
}
