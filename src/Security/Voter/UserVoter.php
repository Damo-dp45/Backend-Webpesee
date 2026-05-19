<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVoter extends Voter
{
    public const VOIR = 'USER_VOIR';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VOIR]); // && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /**
         * @var User
         */
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if(!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::VOIR:
                return true;
                // logic to determine if the user can VIEW
                // return true or false
                break;
        }

        return false;
    }
}
