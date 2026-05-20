<?php

namespace App\Security\Voter;

use App\Entity\Operation;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class OperationVoter extends Voter
{
    public const EDIT = 'POST_EDIT';
    public const VIEW = 'POST_VIEW';

    public function __construct(
        private Security $security
    )
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if($attribute === 'VOIR' && $subject === 'Operation') {
            return true;
        }
        return $attribute === 'VOIR' && $subject instanceof Operation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if(!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        if ($subject === 'Operation') {
            return true; // Filtré par EntrepriseScopeExtension
        }

        /**
         * @var Operation
         */
        $operation = $subject;
        $memeEntreprise = $operation->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();
        $sonSite = $operation->getSite()?->getOperateur()?->getId() === $user->getId();

        return $memeEntreprise || $sonSite;
    }
}
