<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\ReferenceStatus;
use App\Domain\Enum\SiteStatus;
use App\Entity\Input\AssignerOperateurInput;
use App\Entity\Site;
use App\Entity\User;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssignerOperateurProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private UserRepository $userRepository,
        private SiteRepository $siteRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var AssignerOperateurInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        /**
         * @var Site
         */
        $site = $this->siteRepository->findOneBy([
            'id' => $uriVariables['id'],
            // 'statut' => SiteStatus::ACTIF
        ]);
        if($site->getEntreprise()?->getId() !== $user->getEntreprise()?->getId()) {
            throw new BadRequestHttpException('Ce site n\'appartient pas à votre entreprise.');
        }

        $operateur = $this->userRepository->findOneBy([
            'id' => $data->operateurId,
            // 'statut' => ReferenceStatus::ACTIF
        ]);
        if(!$operateur) {
            throw new BadRequestHttpException('Opérateur introuvable');
        }
        if($operateur->getEntreprise()?->getId() !== $user->getEntreprise()?->getId()) { /*
            - On vérifie que l'opérateur appartient à la même entreprise
        */
            throw new BadRequestHttpException('Cet opérateur n\'appartient pas à votre entreprise');
        }
        if(!in_array('ROLE_OPERATEUR', $operateur->getRoles())) { /*
            - On vérifie que c'est bien un opérateur
        */
            throw new BadRequestHttpException('Cet utilisateur n\'est pas un opérateur');
        }

        $site
            ->setOperateur($operateur)
            ->setUpdatedBy($user->getId())
        ;
        return $this->processor->process($site, $operation, $uriVariables, $context);
    }
}
