<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\StatutDemande;
use App\Domain\Enum\Typemouvement;
use App\Entity\DemandeSolde;
use App\Entity\MouvementCaisse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApprouverDemandeProcessor implements ProcessorInterface
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
        /** @var DemandeSolde $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();

        if($data->getStatut() !== StatutDemande::EN_ATTENTE->value) {
            throw new BadRequestHttpException('Cette demande a déjà été traitée');
        }
        $site = $data->getSite();
        $entreprise = $site->getEntreprise();

        if($entreprise->getSolde() < $data->getMontantdemande()) { /*
            - On vérifie le solde entreprise
        */
            throw new BadRequestHttpException('Solde entreprise insuffisant pour approuver cette demande');
        }

        $data
            ->setStatut(StatutDemande::APPROUVEE->value)
            ->setTraitePar($user)
            ->setTraiteAt(new \DateTimeImmutable())
            ->setUpdatedBy($user->getId())
        ;
        $entreprise->setSolde($entreprise->getSolde() - $data->getMontantdemande()); /*
            - Le débit entreprise et crédit site
        */
        $entreprise->setUpdatedBy($user->getId());
        $site->setSolde($site->getSolde() + $data->getMontantdemande());
        $site->setUpdatedBy($user->getId());

        $mouvement = new MouvementCaisse();
        $mouvement
            ->setType(Typemouvement::CREDIT->value)
            ->setMontant($data->getMontantdemande())
            ->setMotif('Recharge approuvée par : ' . $user->getEmail())
            ->setSite($site)
            ->setDemandeSolde($data)
            ->setCreatedBy($user->getId())
            ->setUpdatedBy($user->getId())
        ;
        $this->em->persist($mouvement);

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
