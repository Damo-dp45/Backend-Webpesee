<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Entity\Interface\SiteOwnedInterface;
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
         * @var User|null
         */
        $user = $this->security->getUser();
        if(!$user || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
            /*
                - Super admin : pas de filtre entreprise
                - Mais on applique quand même le soft delete
                if (is_subclass_of($resourceClass, EntityBase::class)) {
                    $alias = $queryBuilder->getAllAliases()[0];
                    $queryBuilder->andWhere("$alias.deletedAt IS NULL");
                }
            */
            return;
        }

        $alias = $queryBuilder->getAllAliases()[0];

        if(is_subclass_of($resourceClass, EntrepriseOwnedInterface::class)) {
            $entreprise = $user->getEntreprise();
            if($entreprise !== null) {
                $queryBuilder
                    ->andWhere("$alias.entreprise = :entreprise")
                    ->andWhere("$alias.deletedAt IS NULL")
                    ->setParameter('entreprise', $entreprise);
            } else {
            }
        }

        if(is_subclass_of($resourceClass, SiteOwnedInterface::class)) {
            $queryBuilder->andWhere("$alias.deletedAt IS NULL");
            if($this->security->isGranted('ROLE_OPERATEUR')) { /*
                - L'opérateur ne voit que ses sites assignés
            */
                $queryBuilder
                    ->join("$alias.site", 'scope_site')
                    ->andWhere('scope_site.operateur = :operateur')
                    ->setParameter('operateur', $user)
                ;
                return;
            }

            $queryBuilder
                ->join("$alias.site", 'scope_site')
                ->join('scope_site.entreprise', 'scope_entreprise')
                ->andWhere('scope_entreprise = :entreprise')
                ->setParameter('entreprise', $user->getEntreprise())
            ; /*
                - L'admin et l'agent peuvent voir tous les sites de l'entreprise
            */
        }
    }
}