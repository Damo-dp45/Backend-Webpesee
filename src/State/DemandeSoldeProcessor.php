<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\StatutDemande;
use App\Entity\DemandeSolde;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DemandeSoldeProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var DemandeSolde $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $sites = $user->getSites(); /*
            - On rattache au site de l'opérateur
        */
        if($sites->isEmpty()) {
            throw new BadRequestHttpException('Aucun site assigné à cet opérateur');
        }

        foreach($user->getSites() as $site) { /*
            - L'opérateur ne peut avoir qu'une demande 'EN_ATTENTE' à la fois
        */
            foreach($site->getDemandeSoldes() as $demande) {
                if($demande->getStatut() === StatutDemande::EN_ATTENTE->value) {
                    throw new BadRequestHttpException('Une demande est déjà en attente pour ce site');
                }
            }
        }

        $data
            ->setSite($sites->first())
            ->setCreatedBy($user->getId())
            ->setUpdatedBy($user->getId())
        ;
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
