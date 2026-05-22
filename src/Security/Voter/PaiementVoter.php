<?php

namespace App\Security\Voter;

use App\Entity\Paiement;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class PaiementVoter extends Voter
{
    public const VOIR = 'VOIR';
    public const CREER = 'CREER';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if(in_array($attribute, [self::VOIR, self::CREER]) && $subject === 'Paiement') {
            return true;
        }
        return in_array($attribute, [self::VOIR]) && $subject instanceof Paiement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if(!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');
            return false;
        }

        if($subject === 'Paiement') {
            return match($attribute) {
                self::VOIR  => true, // Filtré par EntrepriseScopeExtension
                self::CREER => $this->security->isGranted('ROLE_OPERATEUR'), /*
                    - L'opérateur paye mais l'admin et l'agent recharge
                */
                default => false
            };
        }

        /**
         * @var Paiement
         */
        $paiement = $subject;
        $sonSite = $paiement->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $paiement->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            self::VOIR => $memeEntreprise || $sonSite,
            default => false
        };
    }
}
