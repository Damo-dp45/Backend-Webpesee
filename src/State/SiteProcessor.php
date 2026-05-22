<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Site;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class SiteProcessor implements ProcessorInterface
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

        if($operation instanceof Post) {
            $data
                ->setEntreprise($user->getEntreprise())
                ->setSolde(0)
                ->setCreatedBy($user->getId());
        }

        if($operation instanceof Patch) {
            $data->setUpdatedBy($user->getId()); /*
                - On n'a pas besoin de '$this->em->persist($data)' vu qu'on le '->process()'
            */
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
