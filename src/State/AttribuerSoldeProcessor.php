<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Input\AttribuerSoldeInput;
use App\Entity\Site;
use App\Entity\User;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AttribuerSoldeProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private SiteRepository $siteRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var AttribuerSoldeInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entreprise = $user->getEntreprise();
        /**
         * @var Site
         */
        $site = $this->siteRepository->findOneBy([
            'id' => $uriVariables['id'],
            // 'statut' => SiteStatus::ACTIF
        ]);

        if($site->getEntreprise()?->getId() !== $entreprise->getId()) {
            throw new BadRequestHttpException('Ce site n\'appartient pas à votre entreprise');
        }

        if($entreprise->getSolde() < $data->montant) {
            throw new BadRequestHttpException('Solde entreprise insuffisant');
        }

        $entreprise->setSolde($entreprise->getSolde() - $data->montant); /*
            - La logique de débit entreprise et crédit site
        */
        $site->setSolde($site->getSolde() + $data->montant);
        $entreprise->setUpdatedBy($user->getId());
        $site->setUpdatedBy($user->getId());

        return $this->processor->process($site, $operation, $uriVariables, $context);
    }
}
