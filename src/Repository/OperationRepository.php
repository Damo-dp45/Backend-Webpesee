<?php

namespace App\Repository;

use App\Entity\Entreprise;
use App\Entity\Operation;
use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 */
class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    public function findOneByCodesecret(string $codesecret): ?Operation
    {
        return $this->findOneBy([
            'codesecret' => $codesecret
        ]);
    }

    public function getListeMouvement(string $code): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT o.mouvement')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getListeClient(string $code): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT o.client')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getListeFournisseur(string $code): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT o.fournisseur')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getListeDestination(string $code): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT o.destination')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getListeProvenance(string $code): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT o.provenance')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getListeTransporteur(string $code): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT o.transporteur')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getListeVehicule(string $code): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT o.immatriculation')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getListeProduit(string $code): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT o.produit')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getAllBy($criteres = [], $dateDebut = NULL, $dateFin = NULL, $limit = NULL)
    {
        try {
            $qb = $this->createQueryBuilder('pes');
            // $qb->join('App\Entity\Operation', 'ope', Join::WITH, 'pes.operations = ope.id');
            // $qb->join('App\Entity\Destination', 'des', Join::WITH, 'pes.destinations = des.id');
            // $qb->join('App\Entity\Provenance', 'prov', Join::WITH, 'pes.provenances = prov.id');
            // $qb->join('App\Entity\Produit', 'prod', Join::WITH, 'pes.produits = prod.id');
            // $qb->join('App\Entity\Transporteur', 'tran', Join::WITH, 'pes.transporteurs = tran.id');
            // $qb->join('App\Entity\Fournisseur', 'four', Join::WITH, 'pes.fournisseur = four.id');
            // $qb->join('App\Entity\Client', 'clt', Join::WITH, 'pes.client = clt.id');
            // $qb->where("fig = 1")
            $qb->Where("pes.date2 IS NOT NULL");
            $qb->addOrderBy('pes.date2', 'DESC');

            //Tri en fonction des dates debut et fin
            if ($dateDebut && $dateFin) {
                $qb->andWhere($qb->expr()->between("pes.date2", ":dateDebut", ":dateFin"));
                $qb->setParameter("dateDebut", $dateDebut);
                $qb->setParameter("dateFin", $dateFin);
            }
            if(array_key_exists('code', $criteres)){
                $qb->andWhere('pes.code =:code');
                $qb->setParameter('code', $criteres['code']);
                unset($criteres['code']);
            }
            //si l'etat de operation est dans les critères de recherche
            if(array_key_exists('mouvement', $criteres)){
                $qb->andWhere("pes.mouvement = :mouvement");
                $qb->setParameter('mouvement', $criteres['mouvement']);
                unset($criteres['mouvement']);
            }
            //si l'etat de destination est dans les critères de recherche
            if(array_key_exists('destination', $criteres)){
                $qb->andWhere('pese.destination =:destination');
                $qb->setParameter('destination', $criteres['destination']);
                unset($criteres['destination']);
            }
            //SI on a provenance dans les critere de recherche
            if (array_key_exists('provenance', $criteres)) {
                $qb->andWhere("pes.provenance = :provenance");
                $qb->setParameter('provenance', $criteres['provenance']);
                unset($criteres['provenance']);
            }
            //SI on a produit dans les critere de recherche
            if (array_key_exists('produit', $criteres)) {
                $qb->andWhere("pes.produit = :produit");
                $qb->setParameter('produit', $criteres['produit']);
                unset($criteres['produit']);
            }
            //SI on a fourniseur dans les critere de recherche
            if (array_key_exists('fournisseur', $criteres)) {
                $qb->andWhere("pes.fournisseur = :fournisseur");
                $qb->setParameter('fournisseur', $criteres['fournisseur']);
                unset($criteres['fournisseur']);
            }
            //SI on a client dans les critere de recherche
            if (array_key_exists('client', $criteres)) {
                $qb->andWhere("pes.client = :client");
                $qb->setParameter('client', $criteres['client']);
                unset($criteres['client']);
            }

            //SI on a transporteur dans les critere de recherche
            if (array_key_exists('transporteur', $criteres)) {

                $qb->andWhere("pes.transporteur = :transporteur");
                $qb->setParameter('transporteur', $criteres['transporteur']);
                unset($criteres['transporteur']);
            }
            //SI on a vehicule dans les critere de recherche
            if (array_key_exists('vehicule', $criteres)) {

                $qb->andWhere("pes.immatriculation = :vehicule");
                $qb->setParameter('vehicule', $criteres['vehicule']);
                unset($criteres['vehicule']);
            }

            if ($limit) {
                $qb->setMaxResults($limit);
            }

            $query = $qb->getQuery();
            return $query->getResult();
        }
        catch (\Exception $exc) {
            ob_start();
            echo $exc->getMessage();
            $content = ob_get_clean();
            file_put_contents("erreur_rfigerche_figurer.txt", $content . "\n", FILE_APPEND);
            return [];
        }
    }

    /* Pour le frontend
     */
    public function getPaginatedOperations(
        array $criteres = [],
        ?string $dateDebut = null,
        ?string $dateFin = null,
        int $page = 1,
        int $limit = 20
    ): array
    {
        $query = $this->createQueryBuilder('o')->where('o.date2 IS NOT NULL')->orderBy('o.date2', 'DESC');
        $this->applyFiltres($query, $criteres, $dateDebut, $dateFin);

        $total = (clone $query)->select('COUNT(o.id)')->getQuery()->getSingleScalarResult();

        if(!empty($criteres['codeprefix'])) {
            $query->andWhere('o.code LIKE :codeprefix')->setParameter('codeprefix', $criteres['codeprefix'] . '%');
        }

        $results = $query
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
        return [
            'data' => $results,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / $limit)
        ];
    }

    public function getDernieresOperations(Entreprise $entreprise, int $limit = 8): array
    {
        return $this->createQueryBuilder('o')
            ->join(Site::class, 's', 'WITH', 's.codesite = o.code')
            ->where('s.entreprise = :entreprise')
            ->andWhere('o.date2 IS NOT NULL')
            ->setParameter('entreprise', $entreprise)
            ->orderBy('o.date2', 'DESC')
            ->addOrderBy('o.temps2', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getStatsByPeriode(
        string $codeentreprise,
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select(
                'o.code AS codesite',
                'o.libellesite',
                'o.produit',
                'SUM(o.poidsnet) AS total_poidsnet',
                'SUM(o.poidsbrut) AS total_poidsbrut',
                'COUNT(o.id) AS nombre_operations'
            )
            ->where('o.date2 IS NOT NULL')
            ->andWhere('o.code LIKE :prefix')
            ->setParameter('prefix', substr($codeentreprise, 0, 3) . '%')
            ->groupBy('o.code', 'o.libellesite', 'o.produit')
            ->orderBy('total_poidsnet', 'DESC')
        ;
        if($dateDebut && $dateFin) {
            $qb->andWhere('o.date2 BETWEEN :dateDebut AND :dateFin')
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function getStatsByJour(
        string $codeentreprise,
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->select(
                'o.date2 AS jour',
                'SUM(o.poidsnet) AS total_poidsnet',
                'COUNT(o.id) AS nombre_operations'
            )
            ->where('o.date2 IS NOT NULL')
            ->andWhere('o.code LIKE :prefix')
            ->setParameter('prefix', substr($codeentreprise, 0, 3) . '%')
            ->groupBy('o.date2')
            ->orderBy('o.date2', 'ASC');

        if($dateDebut && $dateFin) {
            $qb->andWhere('o.date2 BETWEEN :dateDebut AND :dateFin')
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    private function applyFiltres(
        QueryBuilder $query,
        array $criteres,
        ?string $dateDebut,
        ?string $dateFin
    ): void
    {
        if($dateDebut && $dateFin) {
            $query->andWhere('o.date2 BETWEEN :dateDebut AND :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);
        }

        $champs = ['code', 'mouvement', 'client', 'fournisseur', 'destination', 'provenance', 'produit', 'transporteur'];
        foreach($champs as $champ) {
            if(!empty($criteres[$champ])) {
                $query->andWhere("o.$champ = :$champ")->setParameter($champ, $criteres[$champ]);
            }
        }

        if(!empty($criteres['vehicule'])) {
            $query->andWhere('o.immatriculation = :vehicule')->setParameter('vehicule', $criteres['vehicule']);
        }
    }

    //    /**
    //     * @return Operation[] Returns an array of Operation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Operation
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
