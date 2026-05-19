<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Entreprise;
use App\Entity\Input\EntrepriseInput;
use App\Entity\User;
use App\Repository\EntrepriseRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MeEntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntrepriseRepository $entrepriseRepository,
        // private MediaObjectRepository $mediaObjectRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var EntrepriseInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        /**
         * @var Entreprise
         */
        $entreprise = $this->entrepriseRepository->find($user->getEntreprise()->getId()); /*
            - On ne vérifie pas vu que le '->find()' retourne une exception en cas d'erreur sinon throw 'RuntimeException'
        */
        $entreprise
            ->setNom($data->nom)
            ->setAdresse($data->adresse)
            ->setContact1($data->contact1)
            ->setContact2($data->contact2)
            ->setCodeentreprise($data->codeentreprise)
            ->setSolde($data->solde ?? 0)
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setUpdatedBy($user->getId())
        ;
        /*
            if($data->image) {
                /**
                 * @var MediaObject
                 *
                $media = $this->mediaObjectRepository->find($data->image);
                $entreprise->setImage($media);
            }
        */
        return $this->processor->process($entreprise, $operation, $uriVariables, $context); /*
            - Pas de '->flush()' vu qu'on a le 'process'
        */
    }
}
