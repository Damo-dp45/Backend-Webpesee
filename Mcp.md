### Mcp

- **Important !**
    > Le principe
        > L'administrateur système crée l'entreprise et l'utilisateur via le web pour ensuite donné les informations comme le `codeentreprise` et de l'authentification au client
        > La partie `Desktop`
            > L'application une par pont bascule pousse leurs pesées via `/api/synchronisation` et le préfixe ou les 3 premières lettres du code du site qu'il envoi doit être celui de l'entreprise pour qu'on lie le site à l'entreprise..
                > La logique `SOF010` → préfixe `SOF` → on cherche une `Entreprise` dont le `codeentreprise` commence par `SOF`
            > !! récupère des listes de référentiels via `/api/client`, `/api/fournisseur`.. pour alimenter leurs formulaires
            > !! filtrer les opérations via `/api/lister`
        > !! `Frontend`
            > L'utilisateur se connecte à son compte pour avoir accès au tableau de bord pour voir les données de son entreprise
            > Ensuite des appels sont faites avec le `jwt`..
                > `/api/frontend/operations/stats` pour les totaux par site, par produit, par période..
                > `/api/frontend/operations` et `/api/frontend/sites` pour liste paginée, filtrée des opérations et la liste des sites de l'entreprise connectée

    > Les endpoints de l'api
        > L'endpoint `/api/synchronisation` pour la réception des données depuis les appareils
        > !! `/api/lister` pour la liste filtrée des pesées avec total poids net dont les référenciels sont..
            > `/api/site` pour la liste des ponts bascule par code, `/api/mouvement`, `/api/client`, `/api/fournisseur`, `/api/transporteur`, `/api/produit`, `/api/destination`, `/api/provenance`, `/api/vehicule` les données de référence pour les filtres
- - 

- - 
Salut Claude, mon patron m'a démandé de développé une application qui a pour objectif de générer un certifcat de vérification en 2 pages de vérification mais il ne m'a pas donnée assez d'informations. Voici quelque notion que j'ai compris :

Le technicien se rend chez le client pour faire une intervention et renseigne les informations sur la fiche d'intervention, le type d'équipements sur lequel le technicien intervient est un Pont bascule ou autre appariels

Après l'intervention du technicien il renseignera les informations dans l'application ce qui fera l'objet de quelque informations sur la première page du certificat de vérification, ensuite la sécrétaire vient complèter les informations ce qui fera l'objet de quelque informations sur la deuxième du certificat sur lequel mon patron m'a dit qu'il y'a 3 contrôles qui se faits(contrôle de fidélité, contrôle de justesse et le contrôle d'excentration), mon patron m'a aussi dit que pour le contrôle d'excentration le pont bascule peut avoir entre 4,6,8 et 12 capteurs ce qui fait les colonnes du tableau de contrôle d'excentration

J'ai l'image de la fiche d'intervention du technicien et les images du certificat de vérification que l'application doit générer, je peux te les envoyées

Analyse bien le projet pour comprendre

Client, Equipement, Typeclient
- - 

- - 
Tous ce qu'on a fait a été validé, maintenant mon patron demande de faire des ajouts ce qui vas changer beaucoup de chose dans la partie backend et frontend, je t'explique tous ce que j'ai compris :

L'entreprise a un solde globale, l'entreprise attribut un montant à chacun de ses pont bascule

Chaque pont bascule aura un solde et les sorties de caisse du pont bascule se fait à chaque paiement des planteurs, aussi on doit voir l'inventaire de chaque pont

Un opérateur peut gérer un ou plusieurs pont bascule dans la même entreprise et n'a accès qu'aux données des ponts qu'il gère

Chaque fournisseur à son montant

Mon patron a aussi dit qu'on doit détaché les informations du produit et fournissuer de la table opération donc voici quelque champ j'ai prélevé :
    Fournisseur(codefournisseur, nom, prenom, contact1(10 carac..), contact2(10 carac..), prixspeciale, statut)
    Produit(codeproduit (nullable), libelle, prix)

Quand on a le poid net on calcule par le prix unitaire du produit mais on vérifie d'abord si le fournisseur a un prix spéciale sinon on prend le prix unitaire du produit

On doit pouvoir gérer les pont bascules(site) et aussi bloquer un pont bascule de sorte à ce qu'il ne reçois plus de donnée

Le super admin doit pouvoir voir la liste des entreprises, désactiver une entreprise ce que aura pour conséquence de bloqué la connexion à tous les utilisateurs de l'entreprise etc.., désactiver un pont bascule

L'entreprise doit pouvoir payer les planteurs à partir du site via le paiement en ligne vers les réseaux téléphoniques

Il m'a aussi parler d'un chose :
    L'administrateur voit tous les sites, l'agent gère les opérateurs, l'opérateur ne voit que les informations de son site...

Gestion des ponts, gestion des entreprises et des utilisateurs par site, gestion de la paye, le rapport sur les paiement, tableau de bord, etc...

Pour cette nouvelle version on vas utiliser API Platform, aussi liste tous les modules qu'on aura dans l'application ainsi que les tables
- - 















- Pour les rôles utilisateurs on utiliser le système de `role_hierarchy` via `security.yaml` qui permet de définir un rôle qui hérite automatiquement des permissions d'un autre rôle, ex: `ROLE_ADMIN: [ROLE_AGENT]` l'utilisateur avec `ROLE_ADMIN` a automatiquement aussi `ROLE_AGENT` sans qu'on ait besoin de lui attribuer les 2 dans la base de données
- !! la sécurité on a un voter par entité vu qu'on une logique d'héritage
- !! la partie `synchronisation` on l'a sécurisé avec `ROLE_SITE` et on peut ajouter le champ `sitecompte` pour tracer à quel compte machine est lié le compte et empêcher de créer 2 comptes pour le même site





Pourquoi dans les voters tu fais in_array('ROLE_AGENT', $user->getRoles()) sur des actions qui autorise aussi 'ROLE_ADMIN' or dans le role_hierarchy on a :

role_hierarchy:
    ROLE_SITE: []
    ROLE_OPERATEUR: [ROLE_USER]
    ROLE_AGENT: [ROLE_OPERATEUR]
    ROLE_ADMIN: [ROLE_AGENT]
    ROLE_SUPER_ADMIN: [ROLE_ADMIN]

Aussi en discutant avec mon patron il m'a dis qu'il va modifier le desktop pour qu'il envoi aussi le 'codeproduit' et 'prix' du Produit ainsi que le 'codefournisseur' et 'prixspeciale ou null' du Fournisseur, donc on vas modifié le SynchronisationController

Il a aussi dit qu'on peut rajouter les champs 'prixfournisseur' et 'prixproduit' dans Operation vu que le prix peut changer chaque mois






















Commençons par les processors dans l'ordre logique métier.

---

### 1. `SoftDeleteProcessor` — réutilisable sur toutes les entités

```php
// src/State/Processor/SoftDeleteProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\EntityBase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SoftDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof EntityBase) {
            throw new BadRequestHttpException('Cette entité ne supporte pas la suppression logique.');
        }

        /** @var User $user */
        $user = $this->security->getUser();

        $data->setDeletedAt(new \DateTimeImmutable());
        $data->setDeletedBy($user->getId());

        $this->em->flush();

        return $data;
    }
}
```

---

### 2. `EntrepriseProcessor`

```php
// src/State/Processor/EntrepriseProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var Entreprise $data */
        /** @var User $user */
        $user = $this->security->getUser();

        if ($operation instanceof Post) {
            $data->setCreatedBy($user->getId());
        }

        $data->setUpdatedBy($user->getId());

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
```

---

### 3. `ToggleStatutEntrepriseProcessor`

```php
// src/State/Processor/ToggleStatutEntrepriseProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ToggleStatutEntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var Entreprise $data */
        /** @var User $user */
        $user = $this->security->getUser();

        $data->setStatut(
            $data->getStatut() === ReferenceStatus::ACTIF->value
                ? ReferenceStatus::SUSPENDU->value
                : ReferenceStatus::ACTIF->value
        );
        $data->setUpdatedBy($user->getId());

        $this->em->flush();

        return $data;
    }
}
```

---

### 4. `RechargerSoldeEntrepriseProcessor`

```php
// src/State/Processor/RechargerSoldeEntrepriseProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Input\RechargerSoldeInput;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RechargerSoldeEntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var RechargerSoldeInput $data */
        if (!$data instanceof RechargerSoldeInput) {
            throw new BadRequestHttpException('Données invalides.');
        }

        if ($data->montant <= 0) {
            throw new BadRequestHttpException('Le montant doit être positif.');
        }

        /** @var User $user */
        $user = $this->security->getUser();
        $entreprise = $user->getEntreprise();

        $entreprise->setSolde($entreprise->getSolde() + $data->montant);
        $entreprise->setUpdatedBy($user->getId());

        $this->em->flush();

        return $entreprise;
    }
}
```

```php
// src/Input/RechargerSoldeInput.php
<?php

namespace App\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class RechargerSoldeInput
{
    #[Groups(['write:RechargerSolde'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $montant = null;
}
```

---

### 5. `SiteProcessor`

```php
// src/State/Processor/SiteProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class SiteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var Site $data */
        /** @var User $user */
        $user = $this->security->getUser();

        if ($operation instanceof Post) {
            // On rattache automatiquement le site à l'entreprise de l'admin
            $data->setEntreprise($user->getEntreprise());
            $data->setCreatedBy($user->getId());
        }

        $data->setUpdatedBy($user->getId());

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
```

---

### 6. `AttribuerSoldeProcessor`

```php
// src/State/Processor/AttribuerSoldeProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Site;
use App\Entity\User;
use App\Input\AttribuerSoldeInput;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AttribuerSoldeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var AttribuerSoldeInput $data */
        if (!$data instanceof AttribuerSoldeInput) {
            throw new BadRequestHttpException('Données invalides.');
        }

        if ($data->montant <= 0) {
            throw new BadRequestHttpException('Le montant doit être positif.');
        }

        /** @var User $user */
        $user       = $this->security->getUser();
        $entreprise = $user->getEntreprise();

        // Vérification que le site appartient à l'entreprise de l'admin
        /** @var Site $site */
        $site = $data->site; /*
            - Le site est résolu par API Platform via l'uriVariable {id}
              et injecté dans le contexte — on le récupère depuis $context
        */
        $site = $context['previous_data']; /*
            - 'previous_data' contient l'entité Site chargée par API Platform
              avant que l'input ne soit désérialisé
        */

        if ($site->getEntreprise()?->getId() !== $entreprise->getId()) {
            throw new BadRequestHttpException('Ce site n\'appartient pas à votre entreprise.');
        }

        if ($entreprise->getSolde() < $data->montant) {
            throw new BadRequestHttpException('Solde entreprise insuffisant.');
        }

        // Débit entreprise / Crédit site
        $entreprise->setSolde($entreprise->getSolde() - $data->montant);
        $site->setSolde($site->getSolde() + $data->montant);

        $entreprise->setUpdatedBy($user->getId());
        $site->setUpdatedBy($user->getId());

        $this->em->flush();

        return $site;
    }
}
```

```php
// src/Input/AttribuerSoldeInput.php
<?php

namespace App\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class AttribuerSoldeInput
{
    #[Groups(['write:AttribuerSolde'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $montant = null;
}
```

---

### 7. `PaiementProcessor`

C'est le processor le plus important — il gère la sortie de caisse, crée le `MouvementCaisse` et valide le solde.

```php
// src/State/Processor/PaiementProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MouvementCaisse;
use App\Entity\Paiement;
use App\Entity\User;
use App\Enum\StatutPaiement;
use App\Enum\TypeMouvement;
use App\Input\PaiementInput;
use App\Repository\FournisseurRepository;
use App\Repository\OperationRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaiementProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly SiteRepository $siteRepository,
        private readonly FournisseurRepository $fournisseurRepository,
        private readonly OperationRepository $operationRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var PaiementInput $data */
        if (!$data instanceof PaiementInput) {
            throw new BadRequestHttpException('Données invalides.');
        }

        /** @var User $user */
        $user = $this->security->getUser();

        // Récupération du site de l'opérateur
        $site = $this->siteRepository->find($data->siteId);
        if (!$site || $site->getOperateur()?->getId() !== $user->getId()) {
            throw new BadRequestHttpException('Site invalide ou non assigné.');
        }

        // Vérification statut site
        if (!$site->isActif()) {
            throw new BadRequestHttpException('Ce pont bascule est bloqué.');
        }

        // Récupération du fournisseur
        $fournisseur = $this->fournisseurRepository->find($data->fournisseurId);
        if (!$fournisseur || $fournisseur->getSite()?->getId() !== $site->getId()) {
            throw new BadRequestHttpException('Fournisseur invalide.');
        }

        // Opération liée — optionnelle
        $operationEntity = null;
        if ($data->operationId) {
            $operationEntity = $this->operationRepository->find($data->operationId);
        }

        if ($data->montant <= 0) {
            throw new BadRequestHttpException('Le montant doit être positif.');
        }

        // Vérification solde site
        if ($site->getSolde() < $data->montant) {
            throw new BadRequestHttpException('Solde du pont bascule insuffisant.');
        }

        // Création du paiement
        $paiement = new Paiement();
        $paiement
            ->setMontant($data->montant)
            ->setModepaiement($data->modepaiement)
            ->setFournisseur($fournisseur)
            ->setSite($site)
            ->setOperation($operationEntity)
            ->setStatut(StatutPaiement::VALIDE->value) /*
                - ESPECES → validé immédiatement
                - MOBILE_MONEY → EN_ATTENTE jusqu'à confirmation callback
            */
            ->setCreatedBy($user->getId())
            ->setUpdatedBy($user->getId())
        ;

        if ($data->modepaiement === ModePaiement::MOBILE_MONEY->value) {
            $paiement->setStatut(StatutPaiement::EN_ATTENTE->value);
            /*
                - TODO : déclencher l'appel API mobile money ici
                - La référence sera mise à jour via callback
            */
        }

        // Débit du solde site
        $site->setSolde($site->getSolde() - $data->montant);
        $site->setUpdatedBy($user->getId());

        // Création du MouvementCaisse
        $mouvement = new MouvementCaisse();
        $mouvement
            ->setType(TypeMouvement::DEBIT->value)
            ->setMontant($data->montant)
            ->setMotif('Paiement fournisseur : ' . $fournisseur->getNom())
            ->setSite($site)
            ->setPaiement($paiement)
            ->setCreatedBy($user->getId())
            ->setUpdatedBy($user->getId())
        ;

        $this->em->persist($paiement);
        $this->em->persist($mouvement);
        $this->em->flush();

        return $paiement;
    }
}
```

```php
// src/Input/PaiementInput.php
<?php

namespace App\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class PaiementInput
{
    #[Groups(['write:PaiementInput'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $montant = null;

    #[Groups(['write:PaiementInput'])]
    #[Assert\NotBlank]
    public ?string $modepaiement = null; // ModePaiement::ESPECES / MOBILE_MONEY

    #[Groups(['write:PaiementInput'])]
    #[Assert\NotNull]
    public ?int $siteId = null;

    #[Groups(['write:PaiementInput'])]
    #[Assert\NotNull]
    public ?int $fournisseurId = null;

    #[Groups(['write:PaiementInput'])]
    public ?int $operationId = null; // Optionnel
}
```

---

### 8. `ApprouverDemandeProcessor`

```php
// src/State/Processor/ApprouverDemandeProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\DemandeSolde;
use App\Entity\MouvementCaisse;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Enum\TypeMouvement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApprouverDemandeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var DemandeSolde $data */
        /** @var User $user */
        $user = $this->security->getUser();

        if ($data->getStatut() !== StatutDemande::EN_ATTENTE->value) {
            throw new BadRequestHttpException('Cette demande a déjà été traitée.');
        }

        $site       = $data->getSite();
        $entreprise = $site->getEntreprise();

        // Vérification solde entreprise
        if ($entreprise->getSolde() < $data->getMontantdemande()) {
            throw new BadRequestHttpException('Solde entreprise insuffisant pour approuver cette demande.');
        }

        // Approbation
        $data->setStatut(StatutDemande::APPROUVEE->value);
        $data->setTraitePar($user);
        $data->setTraiteAt(new \DateTimeImmutable());
        $data->setUpdatedBy($user->getId());

        // Débit entreprise / Crédit site
        $entreprise->setSolde($entreprise->getSolde() - $data->getMontantdemande());
        $entreprise->setUpdatedBy($user->getId());

        $site->setSolde($site->getSolde() + $data->getMontantdemande());
        $site->setUpdatedBy($user->getId());

        // MouvementCaisse CREDIT sur le site
        $mouvement = new MouvementCaisse();
        $mouvement
            ->setType(TypeMouvement::CREDIT->value)
            ->setMontant($data->getMontantdemande())
            ->setMotif('Recharge approuvée par : ' . $user->getNom())
            ->setSite($site)
            ->setDemandeSolde($data)
            ->setCreatedBy($user->getId())
            ->setUpdatedBy($user->getId())
        ;

        $this->em->persist($mouvement);
        $this->em->flush();

        return $data;
    }
}
```

---

### 9. `RejeterDemandeProcessor`

```php
// src/State/Processor/RejeterDemandeProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\DemandeSolde;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Input\RejeterDemandeInput;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RejeterDemandeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var RejeterDemandeInput $data */
        if (!$data instanceof RejeterDemandeInput) {
            throw new BadRequestHttpException('Données invalides.');
        }

        /** @var User $user */
        $user = $this->security->getUser();

        /** @var DemandeSolde $demande */
        $demande = $context['previous_data'];

        if ($demande->getStatut() !== StatutDemande::EN_ATTENTE->value) {
            throw new BadRequestHttpException('Cette demande a déjà été traitée.');
        }

        $demande->setStatut(StatutDemande::REJETEE->value);
        $demande->setTraitePar($user);
        $demande->setTraiteAt(new \DateTimeImmutable());
        $demande->setMotif($data->motif);
        $demande->setUpdatedBy($user->getId());

        $this->em->flush();

        return $demande;
    }
}
```

```php
// src/Input/RejeterDemandeInput.php
<?php

namespace App\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class RejeterDemandeInput
{
    #[Groups(['write:RejeterDemande'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5)]
    public ?string $motif = null;
}
```

---

### 10. `DemandeSoldeProcessor`

```php
// src/State/Processor/DemandeSoldeProcessor.php
<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\DemandeSolde;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DemandeSoldeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var DemandeSolde $data */
        /** @var User $user */
        $user = $this->security->getUser();

        // L'opérateur ne peut avoir qu'une demande EN_ATTENTE à la fois
        foreach ($user->getSitesGeres() as $site) {
            foreach ($site->getDemandesSolde() as $demande) {
                if ($demande->getStatut() === StatutDemande::EN_ATTENTE->value) {
                    throw new BadRequestHttpException('Une demande est déjà en attente pour ce site.');
                }
            }
        }

        // Rattachement au site de l'opérateur
        $sites = $user->getSitesGeres();
        if ($sites->isEmpty()) {
            throw new BadRequestHttpException('Aucun site assigné à cet opérateur.');
        }

        $data->setSite($sites->first());
        $data->setCreatedBy($user->getId());
        $data->setUpdatedBy($user->getId());

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
```

---

### Récap des processors

| Processor | Déclenché par | Ce qu'il fait |
|---|---|---|
| `SoftDeleteProcessor` | `/xxx/{id}/remove` | Pose `deletedAt` + `deletedBy` |
| `EntrepriseProcessor` | POST/PATCH entreprise | Persist + audit |
| `ToggleStatutEntrepriseProcessor` | `/entreprises/{id}/togglestatut` | Bascule statut |
| `RechargerSoldeEntrepriseProcessor` | `/entreprises/{id}/recharger` | Crédite solde entreprise |
| `SiteProcessor` | POST/PATCH site | Rattache entreprise + audit |
| `AttribuerSoldeProcessor` | `/sites/{id}/attribuersolde` | Débit entreprise → Crédit site |
| `PaiementProcessor` | POST paiement | Débit site + MouvementCaisse + mobile money |
| `ApprouverDemandeProcessor` | `/demandes-solde/{id}/approuver` | Débit entreprise → Crédit site + MouvementCaisse |
| `RejeterDemandeProcessor` | `/demandes-solde/{id}/rejeter` | Pose statut REJETEE + motif |
| `DemandeSoldeProcessor` | POST demande | Rattache site opérateur + vérifie unicité |

On attaque le frontend maintenant ?













- Donner un rôle à l'utilisateur

- `Site` → `SUPER_ADMIN` voit tout, `ADMIN` voit les siens, `OPERATEUR` voit uniquement ses sites assignés
- `Fournisseur`/`Produit` → `OPERATEUR` voit uniquement ceux de ses sites
- `Paiement`/`MouvementCaisse` → `OPERATEUR` voit uniquement les siens, `ADMIN` voit tout son entreprise
- `DemandeSolde` → `OPERATEUR` crée, `AGENT`/`ADMIN` approuve
- `User` → `ADMIN` gère les utilisateurs de son entreprise, `AGENT` gère les opérateurs

ADMIN (par entreprise)
    └── voit tous les sites de son entreprise
    └── gère les agents et opérateurs
    └── gère le solde global et attribution aux ponts
    └── gère les fournisseurs et produits

AGENT (par entreprise)
    └── gère les opérateurs
    └── supervise plusieurs ponts

OPERATEUR (par site)
    └── voit uniquement ses ponts bascule assignés
    └── gère les pesées et paiements de ses ponts






- Pour les filtres des extensions on les applique selon l'interface qu'implémente une entité, `EntrepriseOwnedInterface` pour les entités liées à une entreprise et `SiteOwnedInterface` !! un site, `User` n'est pas concerné vu qu'on a `UserEntrepriseExtension`
- On a un `scope opérateur` dans `EntrepriseScopeExtension` qui fais que l'opérateur ne voit que les données de ses sites










### Ce qu'il faut ajouter

**Dans `User`** — un champ pour lier le compte machine à son site :

```php
// Le compte machine ROLE_SITE est lié à un site précis
#[ORM\OneToOne]
#[ORM\JoinColumn(nullable: true)]
private ?Site $sitecompte = null; /*
    - Nullable car les autres rôles (ADMIN, AGENT, OPERATEUR)
      n'ont pas de site compte machine
*/
```

### Flux de création

```
ADMIN crée un User { email: 'site-sof010@entreprise.com', roles: ['ROLE_SITE'], sitecompte: Site#SOF010 }
          ↓
Donne les credentials à l'installateur du desktop
          ↓
Desktop s'authentifie via POST /api/auth/login → récupère le JWT
          ↓
Pousse ses pesées via POST /api/synchronisation avec Bearer token
```








### `paiement` *extends EntityBase*

| Champ | Type | Notes |
|---|---|---|
| `id` | int PK | |
| `fournisseur_id` | FK → Fournisseur | le planteur payé |
| `site_id` | FK → Site | site depuis lequel le paiement part |
| `montant` | decimal | montant de ce versement |
| `montant_total_du` | decimal | total dû (pour suivi paiement partiel) |
| `type` | enum | `PARTIEL`, `SOLDE` |
| `statut` | enum | `EN_ATTENTE`, `CONFIRME`, `ECHEC` |
| `moyen` | enum | `MOBILE_MONEY`, `ESPECES`, etc. |
| `numero_destinataire` | string | numéro mobile money |
| `reference_transaction` | string nullable | retour opérateur télécom |
| `operation_id` | FK → Operation nullable | si lié à une pesée précise |

---

### `mouvement_solde` *extends EntityBase*
> Traçabilité complète de toutes les entrées/sorties de solde

| Champ | Type | Notes |
|---|---|---|
| `id` | int PK | |
| `type` | enum | `CREDIT`, `DEBIT` |
| `montant` | decimal | |
| `solde_avant` | decimal | snapshot avant |
| `solde_apres` | decimal | snapshot après |
| `motif` | string | ex: `ATTRIBUTION_SITE`, `PAIEMENT_PLANTEUR`, `RECHARGE` |
| `reference` | string nullable | lien vers paiement ou demande |
| `entreprise_id` | FK → Entreprise nullable | si mouvement sur solde global |
| `site_id` | FK → Site nullable | si mouvement sur solde site |

---

### `demande_recharge` *extends EntityBase*
> L'opérateur demande un réapprovisionnement quand son solde site est insuffisant

| Champ | Type | Notes |
|---|---|---|
| `id` | int PK | |
| `site_id` | FK → Site | |
| `demandeur_id` | FK → User | l'opérateur |
| `montant_demande` | decimal | |
| `statut` | enum | `EN_ATTENTE`, `APPROUVE`, `REFUSE` |
| `traite_par_id` | FK → User nullable | admin/agent qui a traité |
| `commentaire` | string nullable | |

---






- CinetPay, FedaPay et Wave.

> Un fournisseur peut être payé en **plusieurs versements** : plusieurs `Paiement` peuvent pointer vers le même `fournisseur`. Le lien vers une `Operation` est optionnel (paiement global non lié à une pesée précise).
> MouvementCaisse Trace toutes les entrées/sorties de caisse d'un site. Créé automatiquement lors d'un paiement (`DEBIT`) ou d'une recharge de solde via une `DemandeSolde` approuvée (`CREDIT`)
> L'opérateur fait une demande quand son solde est épuisé. L'admin/agent la valide → le solde du site est rechargé et un `MouvementCaisse` (CREDIT) est créé + le solde de l'entreprise est débité


class DemandeSolde extends EntityBase
{
    public function approuver(User $par): static
    {
        $this->statut    = StatutDemande::APPROUVEE;
        $this->traitePar = $par;
        $this->traiteAt  = new \DateTimeImmutable();
        return $this;
    }

    public function rejeter(User $par, string $motif): static
    {
        $this->statut    = StatutDemande::REJETEE;
        $this->traitePar = $par;
        $this->traiteAt  = new \DateTimeImmutable();
        $this->motif     = $motif;
        return $this;
    }
}




Permettre à un utilisateur de s'inscire via le code de son entreprise :

    #[Route('/api/inscription/utilisateurs', name: 'register.users', methods: ['POST'])]
    public function registerUsers(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator,
        EntrepriseRepository $entrepriseRepository
    ): JsonResponse
    {
        $data = $request->toArray();
        $codeentreprise = strtoupper(trim($data['codeentreprise'] ?? ''));
        $entreprise = $entrepriseRepository->findOneByCode($codeentreprise);

        if(!$entreprise) {
            return $this->json([
                'errors' => [
                    'codeentreprise' => 'Aucune entreprise trouvée avec ce code.',
                ]
            ], 422);
        }

        $user = new User();
        $user
            ->setNom(trim($data['nom'] ?? ''))
            ->setPrenom(trim($data['prenom'] ?? ''))
            ->setEmail(trim($data['email'] ?? ''))
            ->setRoles(['ROLE_USER'])
            ->setEntreprise($entreprise)
            ->setPlainPassword($data['password'] ?? '')
        ;
        $violations = $validator->validate($user);

        if(count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $field = $violation->getPropertyPath();
                $key = $field === 'plainPassword' ? 'password' : $field;
                $errors[$key] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 422);
        }

        $user->setPassword($hasher->hashPassword($user, $user->getPlainPassword()));
        $user->setPlainPassword('');

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Compte créé avec succès.'
        ], 201);
    }



Fournisseur(Planteur)
