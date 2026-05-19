<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class EntrepriseScopeExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security
    )
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void
    {
        $this->addWhere($resourceClass, $queryBuilder);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void
    {
        $this->addWhere($resourceClass, $queryBuilder);
    }

    private function addWhere(string $resourceClass, QueryBuilder $queryBuilder)
    {
        /**
         * @var User
         */
        $user = $this->security->getUser();

        if(!$user || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }
        if(is_subclass_of($resourceClass, EntrepriseOwnedInterface::class)) {
            $entrepriseId = $user->getEntreprise()->getId();
            if($entrepriseId !== null) {
                $alias = $queryBuilder->getAllAliases()[0];
                $queryBuilder
                    ->andWhere("$alias.identreprise = :entrepriseId")
                    ->andWhere("$alias.deletedAt IS NULL")
                    ->setParameter('entrepriseId', $entrepriseId);
            } else {
                
            }
        }
    }

}