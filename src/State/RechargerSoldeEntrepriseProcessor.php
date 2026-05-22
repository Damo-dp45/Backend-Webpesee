<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Input\RechargerSoldeInput;
use App\Entity\User;
use App\Repository\EntrepriseRepository;
use Symfony\Bundle\SecurityBundle\Security;

class RechargerSoldeEntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private readonly Security $security,
        private EntrepriseRepository $entrepriseRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var RechargerSoldeInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entreprise = $this->entrepriseRepository->find($user->getEntreprise()->getId());
        $entreprise
            ->setSolde($entreprise->getSolde() + $data->montant)
            ->setUpdatedBy($user->getId())
        ;
        return $this->processor->process($entreprise, $operation, $uriVariables, $context);
    }
}
