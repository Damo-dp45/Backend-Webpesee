<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private Security $security,
        private EntrepriseRepository $entrepriseRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var User $data */

        /**
         * @var User
         */
        $currentUser = $this->security->getUser();
        $entrepriseId = $currentUser->getEntreprise()->getId();
        $entreprise = $this->entrepriseRepository->find($entrepriseId);

        if(!empty($data->getPlainPassword())) {
            $data->setPassword(
                $this->hasher->hashPassword(
                    $data,
                    $data->getPlainPassword()
                )
            );
            $data->setPlainPassword(null); /*
                - Pour éviter de laisser des données sensibles comme le mot de passe en clair en mémoire
            */
        }

        if($operation instanceof Post) {
            $data->setEntreprise($entreprise); /*
                - On lui affecte l'entreprise de l'utilisateur qui l'a crée 
            */
        }

        if($operation instanceof Patch) {
            if(in_array('ROLE_ADMIN', $data->getRoles(), true) && !in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            ) {
                throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à modifier l\'administrateur');
            }
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
