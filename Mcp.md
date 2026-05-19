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






Fournisseur (Planteur)







Parfait, tout est clair. Voici la couche sécurité complète.

---

### 1. `SuperAdminVoter`

Il vote `true` sur **n'importe quel attribut/sujet** si l'utilisateur est `ROLE_SUPER_ADMIN`. Les autres voters n'ont donc jamais besoin de gérer ce cas.

```php
// src/Security/Voter/SuperAdminVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SuperAdminVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Il s'applique sur tout — laisse passer si pas super admin
        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return in_array('ROLE_SUPER_ADMIN', $user->getRoles());
    }
}
```

> Symfony appelle tous les voters et fait un `OR` logique — si `SuperAdminVoter` retourne `true`, l'accès est accordé sans que les autres voters soient consultés (stratégie `affirmative` par défaut).

---

### 2. `EntrepriseVoter`

Plus besoin de gérer `ROLE_SUPER_ADMIN` ici.

```php
// src/Security/Voter/EntrepriseVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EntrepriseVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['VOIR', 'MODIFIER'])
            && $subject instanceof Entreprise;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Entreprise $entreprise */
        $entreprise = $subject;

        return match($attribute) {
            'VOIR'     => $user->getEntreprise()?->getId() === $entreprise->getId(),
            'MODIFIER' => in_array('ROLE_ADMIN', $user->getRoles())
                       && $user->getEntreprise()?->getId() === $entreprise->getId(),
            default    => false
        };
    }
}
```

---

### 3. `SiteVoter`

```php
// src/Security/Voter/SiteVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\Site;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SiteVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, ['VOIR', 'CREER']) && $subject === 'Site') {
            return true;
        }
        return in_array($attribute, ['VOIR', 'MODIFIER']) && $subject instanceof Site;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Collection
        if ($subject === 'Site') {
            return match($attribute) {
                'VOIR'  => true, // Filtré par EntrepriseScopeExtension
                'CREER' => in_array('ROLE_ADMIN', $user->getRoles()),
                default => false
            };
        }

        /** @var Site $site */
        $site = $subject;
        $memeEntreprise = $site->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            'VOIR' => $memeEntreprise
                   || $site->getOperateur()?->getId() === $user->getId(),
            'MODIFIER' => $memeEntreprise
                       && (in_array('ROLE_ADMIN', $user->getRoles())
                        || in_array('ROLE_AGENT', $user->getRoles())),
            default => false
        };
    }
}
```

---

### 4. `UserVoter`

```php
// src/Security/Voter/UserVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, ['VOIR', 'CREER']) && $subject === 'User') {
            return true;
        }
        return in_array($attribute, ['VOIR', 'MODIFIER', 'SUSPENDRE']) && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Collection
        if ($subject === 'User') {
            return match($attribute) {
                'VOIR'  => in_array('ROLE_ADMIN', $user->getRoles())
                        || in_array('ROLE_AGENT', $user->getRoles()),
                'CREER' => in_array('ROLE_ADMIN', $user->getRoles())
                        || in_array('ROLE_AGENT', $user->getRoles()),
                default => false
            };
        }

        /** @var User $cible */
        $cible = $subject;
        $memeEntreprise = $cible->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            'VOIR'      => $memeEntreprise || $cible->getId() === $user->getId(),
            'MODIFIER'  => $memeEntreprise
                        && (in_array('ROLE_ADMIN', $user->getRoles())
                         || in_array('ROLE_AGENT', $user->getRoles()))
                        || $cible->getId() === $user->getId(), // Peut modifier son propre profil
            'SUSPENDRE' => $memeEntreprise
                        && in_array('ROLE_ADMIN', $user->getRoles())
                        && !in_array('ROLE_ADMIN', $cible->getRoles()), // Un admin ne suspend pas un autre admin
            default     => false
        };
    }
}
```

---

### 5. `FournisseurVoter`

```php
// src/Security/Voter/FournisseurVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\Fournisseur;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FournisseurVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, ['VOIR', 'CREER']) && $subject === 'Fournisseur') {
            return true;
        }
        return in_array($attribute, ['VOIR', 'MODIFIER', 'SUPPRIMER']) && $subject instanceof Fournisseur;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Collection
        if ($subject === 'Fournisseur') {
            return true; // Filtré par EntrepriseScopeExtension
        }

        /** @var Fournisseur $fournisseur */
        $fournisseur = $subject;
        $sonSite = $fournisseur->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $fournisseur->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            'VOIR'      => $memeEntreprise || $sonSite,
            'MODIFIER'  => $memeEntreprise
                        && (in_array('ROLE_ADMIN', $user->getRoles())
                         || in_array('ROLE_AGENT', $user->getRoles())
                         || $sonSite),
            'SUPPRIMER' => $memeEntreprise
                        && (in_array('ROLE_ADMIN', $user->getRoles())
                         || in_array('ROLE_AGENT', $user->getRoles())),
            default     => false
        };
    }
}
```

---

### 6. `ProduitVoter`

Logique identique à `FournisseurVoter` — l'opérateur peut voir et modifier les produits de ses sites.

```php
// src/Security/Voter/ProduitVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\Produit;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProduitVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, ['VOIR', 'CREER']) && $subject === 'Produit') {
            return true;
        }
        return in_array($attribute, ['VOIR', 'MODIFIER', 'SUPPRIMER']) && $subject instanceof Produit;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($subject === 'Produit') {
            return true; // Filtré par EntrepriseScopeExtension
        }

        /** @var Produit $produit */
        $produit = $subject;
        $sonSite = $produit->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $produit->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            'VOIR'      => $memeEntreprise || $sonSite,
            'MODIFIER'  => $memeEntreprise
                        && (in_array('ROLE_ADMIN', $user->getRoles())
                         || in_array('ROLE_AGENT', $user->getRoles())
                         || $sonSite),
            'SUPPRIMER' => $memeEntreprise
                        && (in_array('ROLE_ADMIN', $user->getRoles())
                         || in_array('ROLE_AGENT', $user->getRoles())),
            default     => false
        };
    }
}
```

---

### 7. `PaiementVoter`

```php
// src/Security/Voter/PaiementVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\Paiement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PaiementVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, ['VOIR', 'CREER']) && $subject === 'Paiement') {
            return true;
        }
        return in_array($attribute, ['VOIR']) && $subject instanceof Paiement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($subject === 'Paiement') {
            return true; // Filtré par EntrepriseScopeExtension
        }

        /** @var Paiement $paiement */
        $paiement = $subject;
        $sonSite = $paiement->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $paiement->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            // Créer un paiement : uniquement l'opérateur du site concerné
            'CREER' => $sonSite,
            'VOIR'  => $memeEntreprise || $sonSite,
            default => false
        };
    }
}
```

---

### 8. `DemandeSoldeVoter`

```php
// src/Security/Voter/DemandeSoldeVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\DemandeSolde;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DemandeSoldeVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, ['VOIR', 'CREER']) && $subject === 'DemandeSolde') {
            return true;
        }
        return in_array($attribute, ['VOIR', 'TRAITER']) && $subject instanceof DemandeSolde;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($subject === 'DemandeSolde') {
            return true; // Filtré par EntrepriseScopeExtension
        }

        /** @var DemandeSolde $demande */
        $demande = $subject;
        $sonSite = $demande->getSite()?->getOperateur()?->getId() === $user->getId();
        $memeEntreprise = $demande->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId();

        return match($attribute) {
            'VOIR'    => $memeEntreprise || $sonSite,
            // Seul l'opérateur du site peut créer une demande
            'CREER'   => $sonSite,
            // Admin ou Agent traitent les demandes
            'TRAITER' => $memeEntreprise
                      && (in_array('ROLE_ADMIN', $user->getRoles())
                       || in_array('ROLE_AGENT', $user->getRoles())),
            default   => false
        };
    }
}
```

---

### 9. `MouvementCaisseVoter`

Lecture seule — les mouvements sont créés automatiquement par les processors.

```php
// src/Security/Voter/MouvementCaisseVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\MouvementCaisse;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MouvementCaisseVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === 'VOIR' && $subject === 'MouvementCaisse') {
            return true;
        }
        return $attribute === 'VOIR' && $subject instanceof MouvementCaisse;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($subject === 'MouvementCaisse') {
            return true; // Filtré par EntrepriseScopeExtension
        }

        /** @var MouvementCaisse $mouvement */
        $mouvement = $subject;

        return $mouvement->getSite()?->getEntreprise()?->getId() === $user->getEntreprise()?->getId()
            || $mouvement->getSite()?->getOperateur()?->getId() === $user->getId();
    }
}
```

---

### Récap des règles par rôle

| Action | SUPER_ADMIN | ADMIN | AGENT | OPERATEUR |
|---|---|---|---|---|
| Gérer entreprises | ✅ | ✗ | ✗ | ✗ |
| Voir/modifier son entreprise | ✅ | ✅ | ✗ | ✗ |
| Recharger solde entreprise | ✅ | ✗ | ✗ | ✗ |
| Gérer sites | ✅ | ✅ | ✅ | ✗ |
| Attribuer solde site | ✅ | ✅ | ✗ | ✗ |
| Gérer fournisseurs/produits | ✅ | ✅ | ✅ | son site |
| Voir opérations | ✅ | ✅ | ✅ | ses sites |
| Créer paiement | ✅ | ✗ | ✗ | son site |
| Traiter demande solde | ✅ | ✅ | ✅ | ✗ |
| Créer demande solde | ✅ | ✗ | ✗ | son site |
| Gérer utilisateurs | ✅ | ✅ | ✅* | ✗ |

> *L'agent gère uniquement les opérateurs.

On continue avec les `#[ApiResource]` des entités restantes ?







Tableau final corrigé :

| Action | SUPER_ADMIN | ADMIN | AGENT | OPERATEUR |
|---|---|---|---|---|
| Voir liste entreprises | ✅ | ✗ | ✗ | ✗ |
| Suspendre/activer entreprise | ✅ | ✗ | ✗ | ✗ |
| Voir/modifier son entreprise | ✅ | ✅ | ✗ | ✗ |
| Recharger solde entreprise | ✗ | ✅ | ✗ | ✗ |
| Attribuer solde site | ✗ | ✅ | ✗ | ✗ |
| Gérer sites | ✅ | ✅ | ✅ | ✗ |
| Gérer fournisseurs/produits | ✅ | ✅ | ✅ | son site |
| Voir opérations | ✅ | ✅ | ✅ | ses sites |
| Créer paiement | ✗ | ✗ | ✗ | son site |
| Traiter demande solde | ✗ | ✅ | ✅ | ✗ |
| Créer demande solde | ✗ | ✗ | ✗ | son site |
| **Gérer tous les utilisateurs** | ✅ | ✅ | ✅* | ✗ |
| **Suspendre tout utilisateur** | ✅ | ✅** | ✗ | ✗ |

> *Agent gère uniquement les opérateurs.
> **Admin suspend uniquement les utilisateurs de son entreprise (pas les autres admins).

`UserVoter` à corriger en conséquence — `SUSPENDRE` :

```php
'SUSPENDRE' => match(true) {
    // Super admin géré par SuperAdminVoter
    // Admin — suspend tout utilisateur de son entreprise sauf un autre admin
    in_array('ROLE_ADMIN', $user->getRoles()) =>
        $memeEntreprise && !in_array('ROLE_ADMIN', $cible->getRoles()),
    default => false
},
```

On attaque les `#[ApiResource]` restants (`Fournisseur`, `Produit`, `Operation`, `Paiement`, `MouvementCaisse`, `DemandeSolde`) ?












### 1. `Fournisseur`

```php
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Fournisseur', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Fournisseur']],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Fournisseur')",
            openapi: new Operation(
                summary: 'Liste des fournisseurs',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Un fournisseur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Fournisseur')",
            processor: FournisseurProcessor::class,
            openapi: new Operation(
                summary: 'Créer un fournisseur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            processor: FournisseurProcessor::class,
            openapi: new Operation(
                summary: 'Modifier un fournisseur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/fournisseurs/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Supprimer un fournisseur',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(security: [['bearerAuth' => []]])
)]
#[ApiFilter(SearchFilter::class, properties: [
    'nom' => 'partial',
    'codefournisseur' => 'partial',
    'statut' => 'exact',
    'site.id' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'nom', 'createdAt'])]
class Fournisseur extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Fournisseur', 'read:Operation', 'read:Paiement'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?string $codefournisseur = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur', 'read:Operation', 'read:Paiement'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?string $contact1 = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?string $contact2 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?int $prixspeciale = null;

    #[ORM\Column(length: 50)]
    #[Groups(['read:Fournisseur'])]
    private string $statut = ReferenceStatus::ACTIF->value;

    #[ORM\ManyToOne(inversedBy: 'fournisseurs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?Site $site = null;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'fournisseur')]
    private Collection $operations;

    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'fournisseur')]
    private Collection $paiements;

    ...
}
```

---

### 2. `Produit`

```php
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Produit', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Produit']],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Produit')",
            openapi: new Operation(
                summary: 'Liste des produits',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Un produit',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Produit')",
            processor: ProduitProcessor::class,
            openapi: new Operation(
                summary: 'Créer un produit',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            processor: ProduitProcessor::class,
            openapi: new Operation(
                summary: 'Modifier un produit',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/produits/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SoftDeleteProcessor::class,
            openapi: new Operation(
                summary: 'Supprimer un produit',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(security: [['bearerAuth' => []]])
)]
#[ApiFilter(SearchFilter::class, properties: [
    'libelle' => 'partial',
    'codeproduit' => 'partial',
    'site.id' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'libelle', 'prix', 'createdAt'])]
class Produit extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Produit', 'read:Operation'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Produit', 'write:Produit'])]
    private ?string $codeproduit = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['read:Produit', 'write:Produit', 'read:Operation'])]
    private ?string $libelle = null;

    #[ORM\Column]
    #[Groups(['read:Produit', 'write:Produit'])]
    private int $prix = 0;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Produit', 'write:Produit'])]
    private ?Site $site = null;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'produit')]
    private Collection $operations;

    ...
}
```

---

### 3. `Operation`

> Lecture seule depuis l'API Platform — les opérations arrivent uniquement via `/api/synchronisation`. Pas de POST/PATCH/DELETE depuis le frontend.

```php
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Operation', 'read:Base'], 'skip_null_values' => false],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['date2' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Operation')",
            openapi: new Operation(
                summary: 'Liste des opérations de pesée',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Une opération de pesée',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(security: [['bearerAuth' => []]])
)]
#[ApiFilter(SearchFilter::class, properties: [
    'mouvement' => 'partial',
    'client' => 'partial',
    'fournisseur.id' => 'exact',
    'produit.id' => 'exact',
    'site.id' => 'exact',
    'transporteur' => 'partial',
    'immatriculation' => 'partial',
    'provenance' => 'partial',
    'destination' => 'partial',
])]
#[ApiFilter(DateFilter::class, properties: ['date2'])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'date2', 'poidsnet', 'poidsbrut'])]
class Operation extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Operation'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $mouvement = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $destination = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $provenance = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $transporteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $chauffeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $remorque = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Operation'])]
    private ?int $poids1 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Operation'])]
    private ?int $poids2 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Operation'])]
    private ?int $poidsbrut = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Operation'])]
    private ?int $poidsnet = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Operation'])]
    private ?int $prixunitaire = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Operation'])]
    private ?int $montantcalcule = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?\DateTime $date1 = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?\DateTime $date2 = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?\DateTime $temps1 = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?\DateTime $temps2 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $datesearch = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $codepesee = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $numticket = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null; // Champ plat desktop — non exposé

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codesite = null; // Champ plat desktop — non exposé

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $codesecret = null; // Champ plat desktop — non exposé

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $libellesite = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Operation'])]
    private ?string $peseur = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    #[Groups(['read:Operation'])]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    #[Groups(['read:Operation'])]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    #[Groups(['read:Operation'])]
    private ?Produit $produit = null;

    ...
}
```

---

### 4. `Paiement`

```php
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Paiement', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Paiement']],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Paiement')",
            openapi: new Operation(
                summary: 'Liste des paiements',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Un paiement',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Paiement')",
            input: PaiementInput::class,
            processor: PaiementProcessor::class,
            denormalizationContext: ['groups' => ['write:PaiementInput']], /*
                - Le processor calcule le montant, débite le site,
                  crée le MouvementCaisse et déclenche le mobile money si besoin
            */
            openapi: new Operation(
                summary: 'Créer un paiement',
                description: 'Payer un fournisseur (planteur) en une ou plusieurs fois',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(security: [['bearerAuth' => []]])
)]
#[ApiFilter(SearchFilter::class, properties: [
    'statut' => 'exact',
    'modepaiement' => 'exact',
    'fournisseur.id' => 'exact',
    'site.id' => 'exact',
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'montant', 'createdAt'])]
class Paiement extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Paiement', 'read:MouvementCaisse'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['read:Paiement', 'read:MouvementCaisse'])]
    private int $montant = 0;

    #[ORM\Column(length: 50)]
    #[Groups(['read:Paiement'])]
    private string $modepaiement = ModePaiement::ESPECES->value;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Paiement'])]
    private ?string $referencemobile = null;

    #[ORM\Column(length: 50)]
    #[Groups(['read:Paiement'])]
    private string $statut = StatutPaiement::EN_ATTENTE->value;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Paiement'])]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Paiement'])]
    private ?Site $site = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['read:Paiement'])]
    private ?Operation $operation = null;

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'paiement')]
    private Collection $mouvementsCaisse;

    ...
}
```

---

### 5. `MouvementCaisse`

> Lecture seule — créé automatiquement par les processors.

```php
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:MouvementCaisse', 'read:Base'], 'skip_null_values' => false],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'MouvementCaisse')",
            openapi: new Operation(
                summary: 'Liste des mouvements de caisse',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Un mouvement de caisse',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(security: [['bearerAuth' => []]])
)]
#[ApiFilter(SearchFilter::class, properties: [
    'type' => 'exact',
    'site.id' => 'exact',
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'montant', 'createdAt'])]
class MouvementCaisse extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:MouvementCaisse'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['read:MouvementCaisse'])]
    private ?string $type = null; // TypeMouvement::CREDIT / DEBIT

    #[ORM\Column]
    #[Groups(['read:MouvementCaisse'])]
    private int $montant = 0;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['read:MouvementCaisse'])]
    private ?string $motif = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementsCaisse')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:MouvementCaisse'])]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementsCaisse')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['read:MouvementCaisse'])]
    private ?Paiement $paiement = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementsCaisse')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['read:MouvementCaisse'])]
    private ?DemandeSolde $demandeSolde = null;

    ...
}
```

---

### 6. `DemandeSolde`

```php
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:DemandeSolde', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:DemandeSolde']],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'DemandeSolde')",
            openapi: new Operation(
                summary: 'Liste des demandes de solde',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Une demande de solde',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'DemandeSolde')", /*
                - Uniquement l'opérateur dont le site est épuisé
            */
            processor: DemandeSoldeProcessor::class,
            openapi: new Operation(
                summary: 'Créer une demande de solde',
                description: 'L\'opérateur demande une recharge de solde pour son site',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('TRAITER', object)",
            uriTemplate: '/demandes-solde/{id}/approuver',
            requirements: ['id' => '\d+'],
            input: false,
            processor: ApprouverDemandeProcessor::class, /*
                - Crédite Site.solde, débite Entreprise.solde, crée MouvementCaisse
            */
            openapi: new Operation(
                summary: 'Approuver une demande de solde',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('TRAITER', object)",
            uriTemplate: '/demandes-solde/{id}/rejeter',
            requirements: ['id' => '\d+'],
            input: RejeterDemandeInput::class,
            processor: RejeterDemandeProcessor::class,
            denormalizationContext: ['groups' => ['write:RejeterDemande']],
            openapi: new Operation(
                summary: 'Rejeter une demande de solde',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new Operation(security: [['bearerAuth' => []]])
)]
#[ApiFilter(SearchFilter::class, properties: [
    'statut' => 'exact',
    'site.id' => 'exact',
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
class DemandeSolde extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:DemandeSolde'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['read:DemandeSolde', 'write:DemandeSolde'])]
    private int $montantdemande = 0;

    #[ORM\Column(length: 50)]
    #[Groups(['read:DemandeSolde'])]
    private string $statut = StatutDemande::EN_ATTENTE->value;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['read:DemandeSolde', 'write:DemandeSolde'])]
    private ?string $motif = null;

    #[ORM\ManyToOne(inversedBy: 'demandesSolde')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:DemandeSolde'])]
    private ?Site $site = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['read:DemandeSolde'])]
    private ?User $traitePar = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:DemandeSolde'])]
    private ?\DateTimeImmutable $traiteAt = null;

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'demandeSolde')]
    private Collection $mouvementsCaisse;

    ...
}
```

---

Toutes les ressources sont couvertes. On attaque le `SynchronisationController` avec le matching auto fournisseur/produit ?










### `SynchronisationController` mis à jour

La logique de matching suit cet ordre pour chaque pesée :

```
1. Site       → findOneByCode(code) ou création auto
               → rattachement Entreprise via préfixe
2. Fournisseur → findOneByNomAndSite(libelle, site) ou création auto
3. Produit    → findOneByLibelleAndSite(libelle, site) ou création auto
4. Calcul     → prixunitaire = fournisseur.prixspeciale ?? produit.prix
               → montantcalcule = poidsnet × prixunitaire
5. Operation  → findOneByCodesecret(codesecret) ou création
               → update systématique (upsert)
```

---

### 1. Méthodes à ajouter dans les repositories

```php
// src/Repository/FournisseurRepository.php

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
    ;
}
```

```php
// src/Repository/ProduitRepository.php

public function findOneByLibelleAndSite(string $libelle, Site $site): ?Produit
{
    return $this->createQueryBuilder('p')
        ->where('LOWER(p.libelle) = LOWER(:libelle)')
        ->andWhere('p.site = :site')
        ->setParameter('libelle', $libelle)
        ->setParameter('site', $site)
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult()
    ;
}
```

---

### 2. `SynchronisationController`

```php
// src/Controller/SynchronisationController.php
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
#[IsGranted('ROLE_SITE')]
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
            'code'      => 2,
            'fail'      => 0,
            'success'   => 0,
            'operation' => [],
            'message'   => 'Echec'
        ];

        $pesees = $operations['pesees'] ?? null;
        if (!$pesees) {
            $data['code']    = 3;
            $data['message'] = 'Pas de donnees';
            return $this->json($data);
        }

        foreach ($pesees as $donnees) {
            try {
                /* Extraction des champs
                 */
                $libellemouvement   = $donnees['libellemouvement'];
                $libelleclient      = $donnees['libelleclient'];
                $libelledestination = $donnees['libelledestination'];
                $libelleprovenance  = $donnees['libelleprovenance'];
                $libellefournisseur = $donnees['libellefournisseur'];
                $libelletransporteur = $donnees['libelletransporteur'];
                $libelleproduit     = $donnees['libelleproduit'];
                $immatriculation    = $donnees['immatriculation'];
                $remorque           = $donnees['remorque'];
                $date1              = $donnees['datepesee1'];
                $date2              = $donnees['datepesee2'];
                $temps1             = $donnees['temps1'];
                $temps2             = $donnees['temps2'];
                $dsearch            = $donnees['datesearch'];
                $poids1             = $donnees['poids1'];
                $poids2             = $donnees['poids2'];
                $poidsbrut          = $donnees['poidsbrut'];
                $poidsnet           = $donnees['poidsnet'];
                $peseur             = $donnees['peseur'];
                $code               = $donnees['code'];
                $id                 = $donnees['id'];
                $codepesee          = $donnees['codepesee'];
                $numticket          = $donnees['numticket'];
                $libellesite        = $donnees['libellesite'];
                $codesecret         = $code . '_' . $id . '_' . $codepesee;

                if (!$codesecret) {
                    $data['code']    = 3;
                    $data['message'] = 'Pas de donnees';
                    $data['fail']++;
                    continue;
                }

                /* 1. Site — création ou mise à jour
                 */
                $site = $siteRepository->findOneByCode($code);
                if (!$site) {
                    $site = new Site();
                    $site->setCodesite($code);
                    $site->setLibellesite($libellesite);

                    $prefix     = substr($code, 0, 3);
                    $entreprise = $entrepriseRepository->findOneByCodePrefix($prefix);
                    if ($entreprise) {
                        $site->setEntreprise($entreprise);
                    }

                    $em->persist($site);
                    $em->flush(); /*
                        - Flush ici pour avoir l'id du site
                          avant de créer fournisseur/produit
                    */
                } else {
                    $site->setLibellesite($libellesite);
                    $em->flush();
                }

                /* 2. Fournisseur — matching par libellé + site ou création auto
                 */
                $fournisseur = null;
                if ($libellefournisseur) {
                    $fournisseur = $fournisseurRepository->findOneByNomAndSite(
                        $libellefournisseur,
                        $site
                    );
                    if (!$fournisseur) {
                        $fournisseur = new Fournisseur();
                        $fournisseur->setNom($libellefournisseur);
                        $fournisseur->setSite($site);
                        /*
                            - prixspeciale = null par défaut
                            - L'admin complète ensuite depuis l'interface
                        */
                        $em->persist($fournisseur);
                        $em->flush();
                    }
                }

                /* 3. Produit — matching par libellé + site ou création auto
                 */
                $produit = null;
                if ($libelleproduit) {
                    $produit = $produitRepository->findOneByLibelleAndSite(
                        $libelleproduit,
                        $site
                    );
                    if (!$produit) {
                        $produit = new Produit();
                        $produit->setLibelle($libelleproduit);
                        $produit->setSite($site);
                        /*
                            - prix = 0 par défaut
                            - L'admin complète ensuite depuis l'interface
                        */
                        $em->persist($produit);
                        $em->flush();
                    }
                }

                /* 4. Calcul du prix unitaire et montant
                 *    prixunitaire = fournisseur.prixspeciale ?? produit.prix
                 *    montantcalcule = poidsnet × prixunitaire
                 */
                $prixunitaire   = null;
                $montantcalcule = null;
                if ($produit) {
                    $prixunitaire = ($fournisseur?->getPrixspeciale() !== null)
                        ? $fournisseur->getPrixspeciale()
                        : $produit->getPrix();

                    if ($prixunitaire !== null && $poidsnet !== null) {
                        $montantcalcule = $poidsnet * $prixunitaire;
                    }
                }

                /* 5. Operation — upsert via codesecret
                 */
                $operation = $operationRepository->findOneByCodesecret($codesecret);
                if (!$operation) {
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
                    ->setDate1(\DateTime::createFromFormat('Y-m-d', $date1) ?: null)
                    ->setDate2(\DateTime::createFromFormat('Y-m-d', $date2) ?: null)
                    ->setTemps1(\DateTime::createFromFormat('H:i:s', $temps1) ?: null)
                    ->setTemps2(\DateTime::createFromFormat('H:i:s', $temps2) ?: null)
                    ->setDatesearch(\DateTime::createFromFormat('Y-m-d H:i:s', $dsearch) ?: null)
                    ->setPoids1($poids1)
                    ->setPoids2($poids2)
                    ->setPoidsbrut($poidsbrut)
                    ->setPoidsnet($poidsnet)
                    ->setCodepesee($codepesee)
                    ->setNumticket($numticket)
                    ->setCode($code)       // Champ plat — compatibilité desktop
                    ->setCodesite($id)     // Champ plat — compatibilité desktop
                    ->setSite($site)
                    ->setFournisseur($fournisseur)
                    ->setProduit($produit)
                    ->setPrixunitaire($prixunitaire)
                    ->setMontantcalcule($montantcalcule)
                ;

                $em->persist($operation);
                $em->flush();

                $data['operation'][] = ['id' => $id];
                $data['code']        = 1;
                $data['message']     = 'Operation effectuee avec succes';
                $data['success']++;

            } catch (\Exception $e) {
                $data['code']    = 2;
                $data['message'] = 'Echec operation : ' . $e->getMessage();
                $data['fail']++;
                continue; /*
                    - On continue le foreach même si une pesée échoue
                      pour ne pas bloquer les autres
                */
            }
        }

        return $this->json($data);
    }

    /* Endpoints référentiels — consommés par les applications desktop
     */
    #[Route('/mouvement', name: 'mouvement', methods: ['POST'])]
    public function getMouvement(OperationRepository $operationRepository, Request $request): JsonResponse
    {
        $code  = $request->toArray();
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
            'msg'   => 'OK',
            'total' => 0,
            'rows'  => []
        ];

        try {
            $critere = ['deletedAt' => null];
            $limit   = 500;

            if (!empty($donnees['mouvement']))     $critere['mouvement']    = $donnees['mouvement'];
            if (!empty($donnees['code']))           $critere['code']         = $donnees['code'];
            if (!empty($donnees['fournisseur']))    $critere['fournisseur']  = $donnees['fournisseur'];
            if (!empty($donnees['client']))         $critere['client']       = $donnees['client'];
            if (!empty($donnees['destination']))    $critere['destination']  = $donnees['destination'];
            if (!empty($donnees['provenance']))     $critere['provenance']   = $donnees['provenance'];
            if (!empty($donnees['produit']))        $critere['produit']      = $donnees['produit'];
            if (!empty($donnees['transporteur']))   $critere['transporteur'] = $donnees['transporteur'];
            if (!empty($donnees['immatriculation'])) $critere['vehicule']   = $donnees['immatriculation'];

            $liste = $operationRepository->getAllBy(
                $critere,
                $donnees['datepesee1'] ?? null,
                $donnees['datepesee2'] ?? null,
                $limit
            );

            $total = 0;
            foreach ($liste as $operation) {
                $total += $operation->getPoidsnet();
            }

            $jsonData['rows']  = $liste;
            $jsonData['total'] = $total;

        } catch (\Exception $e) {
            $jsonData['msg'] = $e->getMessage();
        }

        return $this->json($jsonData);
    }
}
```

---

### Points notables

**Flush intermédiaire** — on flush après la création du site avant de créer fournisseur/produit pour que l'id du site soit disponible en base. C'est intentionnel.

**`continue` dans le catch** — chaque pesée est traitée indépendamment. Une erreur sur une pesée n'interrompt pas les autres.

**Champs plats conservés** — `code` et `codesite` restent alimentés sur `Operation` pour que les anciens endpoints `/api/lister`, `/api/mouvement` etc. continuent à fonctionner côté desktop sans modification.

On attaque les processors maintenant (`PaiementProcessor`, `ApprouverDemandeProcessor`, `AttribuerSoldeProcessor`…) ?













Pas tout à fait. Avec un `UserVoter`, la syntaxe correcte dépend de comment tu as défini ton voter.

## Le problème

`is_granted('VOIR', 'User')` passe la **string** `'User'` comme subject. Mais dans un Voter, le subject est généralement une **instance d'objet**, pas une string.

## Les options correctes

**Option 1 — Sans subject (le plus courant pour une collection)**
```php
security: "is_granted('VOIR_LISTE_USER')"
```
Dans le Voter :
```php
protected function supports(string $attribute, mixed $subject): bool
{
    return $attribute === 'VOIR_LISTE_USER';
}
```

**Option 2 — Avec le FQCN comme subject**
```php
security: "is_granted('VOIR', object)"  // ❌ pas possible sur GetCollection (pas d'objet)
```
Sur une collection, il n'y a **pas d'objet instancié**, donc tu ne peux pas passer `object`.

**Option 3 — Passer le nom de la classe (string)**
```php
security: "is_granted('VOIR', 'App\\Entity\\User')"
```
Et dans le Voter :
```php
protected function supports(string $attribute, mixed $subject): bool
{
    return $attribute === 'VOIR' 
        && ($subject instanceof User || $subject === User::class);
}
```

## Recommandation

Pour un `GetCollection`, la pratique la plus propre est :

```php
security: "is_granted('USER_VOIR_LISTE')"
```

```php
class UserVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['USER_VOIR_LISTE', 'USER_VOIR', 'USER_MODIFIER', ...]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return match($attribute) {
            'USER_VOIR_LISTE' => $this->canVoirListe($token),
            // ...
        };
    }
}
```

Cela évite toute ambiguïté sur le subject et garde le Voter clair.





- Pour la sécurité on a un voter par entité vu qu'on une logique d'héritage


- `Entreprise` → seul le `SUPER_ADMIN` la voit/modifie
- `Site` → `SUPER_ADMIN` voit tout, `ADMIN` voit les siens, `OPERATEUR` voit uniquement ses sites assignés
- `Fournisseur`/`Produit` → `OPERATEUR` voit uniquement ceux de ses sites
- `Paiement`/`MouvementCaisse` → `OPERATEUR` voit uniquement les siens, `ADMIN` voit tout son entreprise
- `DemandeSolde` → `OPERATEUR` crée, `AGENT`/`ADMIN` approuve
- `User` → `ADMIN` gère les utilisateurs de son entreprise, `AGENT` gère les opérateurs


**`EntrepriseVoter`**

```php
// src/Security/Voter/EntrepriseVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EntrepriseVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['VOIR', 'MODIFIER'])
            && $subject instanceof Entreprise;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Entreprise $entreprise */
        $entreprise = $subject;

        return match($attribute) {
            'VOIR'     => $this->peutVoir($user, $entreprise),
            'MODIFIER' => $this->peutModifier($user, $entreprise),
            default    => false
        };
    }

    private function peutVoir(User $user, Entreprise $entreprise): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }
        // Admin/Agent/Operateur voient uniquement leur propre entreprise
        return $user->getEntreprise()?->getId() === $entreprise->getId();
    }

    private function peutModifier(User $user, Entreprise $entreprise): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }
        // Seul l'admin peut modifier son entreprise (via /me/entreprise)
        return in_array('ROLE_ADMIN', $user->getRoles())
            && $user->getEntreprise()?->getId() === $entreprise->getId();
    }
}
```

**`SiteVoter`**

```php
// src/Security/Voter/SiteVoter.php
<?php

namespace App\Security\Voter;

use App\Entity\Site;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SiteVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, ['VOIR', 'MODIFIER', 'CREER']) && $subject === 'Site') {
            return true;
        }
        return in_array($attribute, ['VOIR', 'MODIFIER']) && $subject instanceof Site;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Sur la collection (string)
        if ($subject === 'Site') {
            return match($attribute) {
                'VOIR'  => $this->peutVoirCollection($user),
                'CREER' => in_array('ROLE_SUPER_ADMIN', $user->getRoles())
                        || in_array('ROLE_ADMIN', $user->getRoles()),
                default => false
            };
        }

        /** @var Site $site */
        $site = $subject;

        return match($attribute) {
            'VOIR'     => $this->peutVoir($user, $site),
            'MODIFIER' => $this->peutModifier($user, $site),
            default    => false
        };
    }

    private function peutVoirCollection(User $user): bool
    {
        // Tous les rôles ont accès à la collection — filtrée par la Doctrine Extension
        return true;
    }

    private function peutVoir(User $user, Site $site): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles())) {
            return $site->getEntreprise()?->getId() === $user->getEntreprise()?->getId();
        }
        // Opérateur — uniquement ses sites assignés
        return $site->getOperateur()?->getId() === $user->getId();
    }

    private function peutModifier(User $user, Site $site): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_AGENT', $user->getRoles())) {
            return $site->getEntreprise()?->getId() === $user->getEntreprise()?->getId();
        }
        return false; // L'opérateur ne modifie pas un site
    }
}
```

---

### 3. Doctrine Extension — scope automatique

```php
// src/Doctrine/EntrepriseScopeExtension.php
<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\EntityBase;
use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class EntrepriseScopeExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private readonly Security $security) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Super admin voit tout
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        // Entreprise — l'admin voit uniquement la sienne
        if ($resourceClass === Entreprise::class) {
            $alias = $qb->getRootAliases()[0];
            $qb->andWhere("$alias.id = :entrepriseId")
               ->setParameter('entrepriseId', $user->getEntreprise()?->getId());
            return;
        }

        // Soft delete — toutes les entités EntityBase
        if (is_subclass_of($resourceClass, EntityBase::class)) {
            $alias = $qb->getRootAliases()[0];
            $qb->andWhere("$alias.deletedAt IS NULL");
        }

        // Scope entreprise — entités avec relation entreprise directe ou via site
        $this->applyScopeByRole($qb, $resourceClass, $user);
    }

    private function applyScopeByRole(QueryBuilder $qb, string $resourceClass, User $user): void
    {
        $alias = $qb->getRootAliases()[0];

        // Entités directement liées à une entreprise (Site, User)
        $entitiesWithEntreprise = [
            \App\Entity\Site::class,
            \App\Entity\User::class,
        ];

        // Entités liées via Site
        $entitiesViasite = [
            \App\Entity\Fournisseur::class,
            \App\Entity\Produit::class,
            \App\Entity\Operation::class,
            \App\Entity\Paiement::class,
            \App\Entity\MouvementCaisse::class,
            \App\Entity\DemandeSolde::class,
        ];

        if (in_array($resourceClass, $entitiesWithEntreprise)) {
            $qb->andWhere("$alias.entreprise = :entreprise")
               ->setParameter('entreprise', $user->getEntreprise());
            return;
        }

        if (in_array($resourceClass, $entitiesViasite)) {
            // L'opérateur ne voit que les données de ses sites assignés
            if ($this->security->isGranted('ROLE_OPERATEUR')
                && !$this->security->isGranted('ROLE_AGENT')
            ) {
                $qb->join("$alias.site", 'scope_site')
                   ->andWhere('scope_site.operateur = :operateur')
                   ->setParameter('operateur', $user);
                return;
            }

            // Admin/Agent — toute l'entreprise
            $qb->join("$alias.site", 'scope_site')
               ->join('scope_site.entreprise', 'scope_entreprise')
               ->andWhere('scope_entreprise = :entreprise')
               ->setParameter('entreprise', $user->getEntreprise());
        }
    }
}
```

```yaml
# config/services.yaml
services:
    App\Doctrine\EntrepriseScopeExtension:
        tags:
            - { name: api_platform.doctrine.orm.query_extension.collection }
            - { name: api_platform.doctrine.orm.query_extension.item }
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

## Récapitulatif des rôles

| Rôle | Périmètre |
|---|---|
| `ROLE_SUPER_ADMIN` | Toutes les entreprises, activation/suspension |
| `ROLE_ADMIN` | Son entreprise : tous ses sites, utilisateurs, solde |
| `ROLE_AGENT` | Gère les opérateurs de l'entreprise |
| `ROLE_OPERATEUR` | Uniquement les sites qui lui sont affectés (`user_site`) |



```
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
```



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
