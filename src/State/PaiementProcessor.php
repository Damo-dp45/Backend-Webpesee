<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\ModePaiement;
use App\Domain\Enum\SiteStatus;
use App\Domain\Enum\StatutPaiement;
use App\Domain\Enum\Typemouvement;
use App\Entity\Input\PaiementInput;
use App\Entity\MouvementCaisse;
use App\Entity\Paiement;
use App\Entity\User;
use App\Repository\FournisseurRepository;
use App\Repository\OperationRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaiementProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private SiteRepository $siteRepository,
        private FournisseurRepository $fournisseurRepository,
        private OperationRepository $operationRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var PaiementInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $site = $this->siteRepository->findOneBy([
            'id' => $data->siteId
            // !!
        ]);

        if(!$site || $site->getOperateur()?->getId() !== $user->getId()) {
            throw new BadRequestHttpException('Site invalide ou non assigné');
        }

        if($site->getStatut() === SiteStatus::BLOQUE) {
            throw new BadRequestHttpException('Ce pont bascule est bloqué');
        }

        $fournisseur = $this->fournisseurRepository->find($data->fournisseurId);
        if(!$fournisseur || $fournisseur->getSite()?->getId() !== $site->getId()) {
            throw new BadRequestHttpException('Fournisseur invalide');
        }

        $operationEntity = null;
        if($data->operationId) { /*
            - L'opération liée 'optionnelle'
        */
            $operationEntity = $this->operationRepository->find($data->operationId);
        }

        if($data->montant <= 0) {
            throw new BadRequestHttpException('Le montant doit être positif');
        }

        if($site->getSolde() < $data->montant) {
            throw new BadRequestHttpException('Solde du pont bascule insuffisant');
        }

        $paiement = new Paiement();
        $paiement
            ->setMontant($data->montant)
            ->setModepaiement($data->modepaiement)
            ->setFournisseur($fournisseur)
            ->setSite($site)
            ->setOperation($operationEntity)
            ->setStatut(StatutPaiement::VALIDE->value) /*
                - ESPECES → validé immédiatement
                - MOBILE_MONEY → EN_ATTENTE jusqu'à confirmation callback
            */
            ->setCreatedBy($user->getId())
            ->setUpdatedBy($user->getId())
        ;

        if($data->modepaiement === ModePaiement::MOBILE_MONEY->value) {
            $paiement->setStatut(StatutPaiement::EN_ATTENTE->value);
            /*
                - TODO : déclencher l'appel API mobile money ici
                - La référence sera mise à jour via callback
            */
        }

        $site->setSolde($site->getSolde() - $data->montant); /*
            - Le débit du solde site
        */
        $site->setUpdatedBy($user->getId());

        $mouvement = new MouvementCaisse();
        $mouvement
            ->setType(Typemouvement::DEBIT->value)
            ->setMontant($data->montant)
            ->setMotif('Paiement fournisseur : ' . $fournisseur->getNom())
            ->setSite($site)
            ->setPaiement($paiement)
            ->setCreatedBy($user->getId())
            ->setUpdatedBy($user->getId())
        ;
        $this->em->persist($paiement);
        $this->em->persist($mouvement);

        return $this->processor->process($paiement, $operation, $uriVariables, $context);
    }
}
