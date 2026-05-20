<?php

namespace App\Controller;

use App\Entity\Fournisseur;
use App\Entity\Operation;
use App\Entity\Produit;
use App\Entity\Site;
use App\Repository\EntrepriseRepository;
use App\Repository\FournisseurRepository;
use App\Repository\OperationRepository;
use App\Repository\ProduitRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'synchronisation.')]
// #[IsGranted('ROLE_SITE')] // Le rôle pour les comptes machines par pont bascule
final class SynchronisationController extends AbstractController
{
    #[Route('/synchronisation', name: 'index', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        OperationRepository $operationRepository,
        SiteRepository $siteRepository,
        EntrepriseRepository $entrepriseRepository,
        FournisseurRepository $fournisseurRepository,
        ProduitRepository $produitRepository,
    ): JsonResponse
    {
        $operations = $request->toArray();
        $data = [
            'code' => 2,
            'fail' => 0,
            'success' => 0,
            'operation' => [],
            'message' => 'Echec'
        ];
        $pesees = $operations['pesees'] ?? null;
        if(!$pesees) {
            $data['code'] = 3;
            $data['message'] = 'Pas de donnees';
            return $this->json($data, 200, ['Content-Type' => 'application/json']);
        }
        foreach($pesees as $donnees) {
            try {
                /* Extraction des champs
                 */
                $libellemouvement = $donnees['libellemouvement'];
                $libelleclient = $donnees['libelleclient'];
                $libelledestination = $donnees['libelledestination'];
                $libelleprovenance = $donnees['libelleprovenance'];
                $libellefournisseur = $donnees['libellefournisseur'];
                $libelletransporteur = $donnees['libelletransporteur'];
                $libelleproduit = $donnees['libelleproduit'];
                $immatriculation = $donnees['immatriculation'];
                $remorque = $donnees['remorque'];
                $date1 = $donnees['datepesee1'];
                $date2 = $donnees['datepesee2'];
                $temps1 = $donnees['temps1'];
                $temps2 = $donnees['temps2'];
                $dsearch = $donnees['datesearch'];
                $poids1 = $donnees['poids1'];
                $poids2 = $donnees['poids2'];
                $poidsbrut = $donnees['poidsbrut'];
                $poidsnet = $donnees['poidsnet'];
                $peseur = $donnees['peseur'];
                $code = $donnees['code'];
                $id = $donnees['id'];
                $codepesee = $donnees['codepesee'];
                $numticket = $donnees['numticket'];
                $libellesite = $donnees['libellesite'];
                $codesecret = $code . '_' . $id . '_' . $codepesee;

                if(!$codesecret) {
                    $data['code'] = 3;
                    $data['message'] = 'Pas de donnees';
                    $data['fail']++;
                    continue;
                }

                /* 1. Site — création ou mise à jour
                 */
                $site = $siteRepository->findOneByCode($code);
                if(!$site) {
                    $site = new Site();
                    $site
                        ->setCodesite($code)
                        ->setLibellesite($libellesite)
                        ->setSolde(0) # !!
                    ;
                    $prefix = substr($code, 0, 3); /*
                        - On rattache l'entreprise au site via le préfixe
                    */
                    $entreprise = $entrepriseRepository->findOneByCodePrefix($prefix); /*
                        - Il est plus propre de chercher par le code de l'entreprise complet si le desktop renvoi un champ 'codeentreprise'
                    */
                    if($entreprise) {
                        $site->setEntreprise($entreprise);
                        // On.. une erreur pour indiquer que l'entreprise n'existe pas !!
                    }

                    $em->persist($site);
                    $em->flush(); /*
                        - On '->flush()' pour avoir l'id du site vu que fournisseur et produit en ont besoin
                    */
                } else {
                    $site->setLibellesite($libellesite);
                    $em->flush();
                }

                /* Le matching par libellé plus site ou création auto du fournisseur
                 */
                $fournisseur = null;
                if($libellefournisseur) {
                    $fournisseur = $fournisseurRepository->findOneByNomAndSite(
                        $libellefournisseur,
                        $site
                    );
                    if(!$fournisseur) {
                        $fournisseur = new Fournisseur();
                        $fournisseur->setNom($libellefournisseur);
                        $fournisseur->setSite($site); /*
                            - Le 'prixspeciale' null par défaut puis l'admin ou.. complète ensuite depuis l'interface
                        */
                        $em->persist($fournisseur);
                        $em->flush();
                    }
                }

                /* !! produit
                 */
                $produit = null;
                if($libelleproduit) {
                    $produit = $produitRepository->findOneByLibelleAndSite(
                        $libelleproduit,
                        $site
                    );
                    if(!$produit) {
                        $produit = new Produit();
                        $produit
                            ->setLibelle($libelleproduit)
                            ->setSite($site)
                            ->setPrix(0)
                        ; /*
                            - le 'prix' 0 par défaut puis l'admin complète depuis l'interface ou on le reçois
                        */
                        $em->persist($produit);
                        $em->flush();
                    }
                }

                /* On calcul le prix unitaire et montant, 'prixunitaire = fournisseur.prixspeciale ?? produit.prix' et 'montantcalcule = poidsnet × prixunitaire'
                 */
                $prixunitaire = null;
                $montantcalcule = null;
                if($produit) {
                    $prixunitaire = ($fournisseur?->getPrixspeciale() !== null) ? $fournisseur->getPrixspeciale() : $produit->getPrix();
                    if($prixunitaire !== null && $poidsnet !== null) {
                        $montantcalcule = $poidsnet * $prixunitaire;
                    }
                }

                $date1 = $date1 ? new \DateTime($date1) : null;
                $date2 = $date2 ? new \DateTime($date2) : null;
                $temps1 = $temps1 ? new \DateTime($temps1) : null;
                $temps2 = $temps2 ? new \DateTime($temps2) : null;
                $dsearch = $dsearch ? new \DateTime($dsearch) : null;

                /* On upsert l'operation via 'codesecret'
                 */
                $operation = $operationRepository->findOneByCodesecret($codesecret);
                if(!$operation) {
                    $operation = new Operation();
                    $operation->setCodesecret($codesecret);
                }
                $operation
                    ->setMouvement($libellemouvement)
                    ->setClient($libelleclient)
                    ->setDestination($libelledestination)
                    ->setProvenance($libelleprovenance)
                    ->setTransporteur($libelletransporteur)
                    ->setImmatriculation($immatriculation)
                    ->setRemorque($remorque)
                    ->setLibellesite($libellesite)
                    ->setPeseur($peseur)
                    ->setDate1($date1)
                    ->setDate2($date2)
                    ->setTemps1($temps1)
                    ->setTemps2($temps2)
                    ->setDatesearch($dsearch)
                    ->setPoids1($poids1)
                    ->setPoids2($poids2)
                    ->setPoidsbrut($poidsbrut)
                    ->setPoidsnet($poidsnet)
                    ->setCodepesee($codepesee)
                    ->setNumticket($numticket)
                    ->setCode($code) // Champ plat — compatibilité desktop
                    ->setCodesite($id) // Champ plat — compatibilité desktop
                    ->setSite($site)
                    ->setFournisseur($fournisseur)
                    ->setProduit($produit)
                    ->setPrixunitaire($prixunitaire)
                    ->setMontantcalcule($montantcalcule)
                ;
                $em->persist($operation);
                $em->flush();

                $data['operation'][] = ['id' => $id]; // Ou.. 'array_push($data['operation'],['id' => $id])'
                $data['code'] = 1;
                $data['message'] = 'Operation effectuee avec succes';
                $data['success']++;

            } catch (\Exception $e) {
                $data['code'] = 2;
                $data['message'] = 'Echec operation : ' . $e->getMessage();
                $data['fail']++;
                continue; /*
                    - On continue le foreach même si une pesée échoue pour ne pas bloquer les autres
                */
            }
        }

        return $this->json($data);
    }

    /* Les endpoints référentiels consommés par les applications desktop
     */
    #[Route('/mouvement', name: 'mouvement', methods: ['POST'])]
    public function getMouvement(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code = $request->toArray();
        $liste = $operationRepository->getListeMouvement($code['code']);
        return $this->json($liste);
    }

    #[Route('/client', name: 'client', methods: ['POST'])]
    public function getClient(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code  = $request->toArray();
        $liste = $operationRepository->getListeClient($code['code']);
        return $this->json($liste);
    }

    #[Route('/fournisseur', name: 'fournisseur', methods: ['POST'])]
    public function getFournisseur(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code  = $request->toArray();
        $liste = $operationRepository->getListeFournisseur($code['code']);
        return $this->json($liste);
    }

    #[Route('/destination', name: 'destination', methods: ['POST'])]
    public function getDestination(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code  = $request->toArray();
        $liste = $operationRepository->getListeDestination($code['code']);
        return $this->json($liste);
    }

    #[Route('/provenance', name: 'provenance', methods: ['POST'])]
    public function getProvenance(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code  = $request->toArray();
        $liste = $operationRepository->getListeProvenance($code['code']);
        return $this->json($liste);
    }

    #[Route('/transporteur', name: 'transporteur', methods: ['POST'])]
    public function getTransporteur(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code  = $request->toArray();
        $liste = $operationRepository->getListeTransporteur($code['code']);
        return $this->json($liste);
    }

    #[Route('/vehicule', name: 'vehicule', methods: ['POST'])]
    public function getVehicule(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code  = $request->toArray();
        $liste = $operationRepository->getListeVehicule($code['code']);
        return $this->json($liste);
    }

    #[Route('/produit', name: 'produit', methods: ['POST'])]
    public function getProduit(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code  = $request->toArray();
        $liste = $operationRepository->getListeProduit($code['code']);
        return $this->json($liste);
    }

    #[Route('/site', name: 'site', methods: ['POST'])]
    public function getSite(SiteRepository $siteRepository, Request $request): JsonResponse
    {
        $code    = $request->toArray();
        $strcode = substr($code['code'], 0, 3);
        $liste   = $siteRepository->getListeSite($strcode);
        return $this->json($liste);
    }

    #[Route('/lister', name: 'lister', methods: ['POST'])]
    public function getBilanListe(Request $request, OperationRepository $operationRepository): JsonResponse
    {
        $donnees = $request->toArray();
        $jsonData = [
            'msg' => 'OK',
            'total' => 0,
            'rows' => []
        ];
        try {
            $critere = ['deletedAt' => null];
            $limit = 500;
            /*
                if($date1) {
                    $date1 = new DateTime(date("Y-m-d", strtotime(str_replace("/", "-", $date1))));
                    unset($critere['etat']);
                }

                if($date2) {
                    $date2 = new DateTime(date("Y-m-d", strtotime(str_replace("/", "-", $date2))));
                }
            */
            if(!empty($donnees['mouvement'])) { // Ou.. 'if($mouvement)'
                $critere['mouvement'] = $donnees['mouvement'];
            }
            if(!empty($donnees['code'])) {
                $critere['code'] = $donnees['code'];
            }
            if(!empty($donnees['fournisseur'])) {
                $critere['fournisseur'] = $donnees['fournisseur'];
            }
            if(!empty($donnees['client'])) {
                $critere['client'] = $donnees['client'];
            }
            if(!empty($donnees['destination'])) {
                $critere['destination'] = $donnees['destination'];
            }
            if(!empty($donnees['provenance'])) {
                $critere['provenance'] = $donnees['provenance'];
            }
            if(!empty($donnees['produit'])) {
                $critere['produit'] = $donnees['produit'];
            }
            if(!empty($donnees['transporteur'])) {
                $critere['transporteur'] = $donnees['transporteur'];
            }
            if(!empty($donnees['immatriculation'])) {
                $critere['vehicule'] = $donnees['immatriculation'];
            }

            $liste = $operationRepository->getAllBy(
                $critere,
                $donnees['datepesee1'] ?? null, // On.. '$date1'
                $donnees['datepesee2'] ?? null, // .. '$date2'
                $limit
            );

            $total = 0;
            foreach($liste as $operation) {
                $total += $operation->getPoidsnet();
            }
            $jsonData['rows']  = $liste;
            $jsonData['total'] = $total;
        } catch (\Exception $e) {
            $jsonData['msg'] = $e->getMessage();
        }

        return $this->json($jsonData, 200, ['Content-Type' => 'application/json']);
    }
}
