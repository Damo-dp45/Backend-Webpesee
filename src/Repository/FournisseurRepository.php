<?php

namespace App\Repository;

use App\Entity\Fournisseur;
use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fournisseur>
 */
class FournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fournisseur::class);
    }

    public function findOneByNomAndSite(string $nom, Site $site): ?Fournisseur
    {
        return $this->createQueryBuilder('f')
            ->where('LOWER(f.nom) = LOWER(:nom)')
            ->andWhere('f.site = :site')
            ->setParameter('nom', $nom)
            ->setParameter('site', $site)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ; /*
            - Le 'LOWER' sert à rendre la comparaison insensible aux majuscules et minuscules
        */
    }

    //    /**
    //     * @return Fournisseur[] Returns an array of Fournisseur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Fournisseur
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
