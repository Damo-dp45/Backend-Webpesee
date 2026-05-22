<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\SiteStatus;
use App\Entity\Site;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class SuspendreSiteProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Site $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $data
            ->setStatut($data->getStatut() === SiteStatus::ACTIF->value ? SiteStatus::BLOQUE->value : SiteStatus::ACTIF->value)
            ->setUpdatedBy($user->getId())
        ;
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
