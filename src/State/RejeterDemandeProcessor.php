<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\StatutDemande;
use App\Entity\DemandeSolde;
use App\Entity\Input\RejeterDemandeInput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RejeterDemandeProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var RejeterDemandeInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();

        /**
         * @var DemandeSolde
         */
        $demande = $context['previous_data']; /*
            - Ou.. $site = $this->siteRepository->findOneBy([
                    'id' => $uriVariables['id'],
                    // 'statut' => SiteStatus::ACTIF
                ]);
        */
        if($demande->getStatut() !== StatutDemande::EN_ATTENTE->value) {
            throw new BadRequestHttpException('Cette demande a déjà été traitée');
        }
        $demande
            ->setStatut(StatutDemande::REJETEE->value)
            ->setTraitePar($user)
            ->setTraiteAt(new \DateTimeImmutable())
            ->setMotif($data->motif)
            ->setUpdatedBy($user->getId())
        ;
        return $this->processor->process($demande, $operation, $uriVariables, $context);
    }
}
