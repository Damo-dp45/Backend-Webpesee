<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Enum\ReferenceStatus;
use App\Entity\Input\EntrepriseInput;
use App\Entity\Input\RechargerSoldeInput;
use App\Repository\EntrepriseRepository;
use App\State\DesactiverEntrepriseProcessor;
use App\State\MeEntrepriseProcessor;
use App\State\MeEntrepriseProvider;
use App\State\RechargerSoldeEntrepriseProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[UniqueEntity(fields: ['codeentreprise'], message: 'Ce code entreprise est déjà utilisé')]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Entreprise', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Entreprise']],
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_SUPER_ADMIN')",
            openapi: new Operation(
                summary: 'La liste des entreprises',
                description: 'Permet de voir la liste des entreprises',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('ROLE_SUPER_ADMIN')",
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'L\'entreprise',
                description: 'Permet de voir une entreprise',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('ROLE_SUPER_ADMIN')",
            uriTemplate: '/entreprises/{id}/desactiver',
            requirements: ['id' => '\d+'],
            input: false,
            processor: DesactiverEntrepriseProcessor::class,
            openapi: new Operation(
                summary: 'Activer ou désactiver une entreprise',
                security: [['bearerAuth' => []]]
            )
        ),
        /* Admin
         */
        new Get(
            uriTemplate: '/me/entreprise',
            security: "is_granted('ROLE_ADMIN') or object == user.getEntreprise()", /*
                - 'user' désigne l'utilisateur authentifié
            */
            provider: MeEntrepriseProvider::class,
            input: false,
            openapi: new Operation(
                summary: 'Voir mon entreprise',
                description: 'Permet de voir les informations d\'une entreprise',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            uriTemplate: '/me/entreprise',
            security: "is_granted('ROLE_ADMIN')",
            input: EntrepriseInput::class, /*
                - Vu qu'on n'a pas de moyen de récupérer l'entreprise ici..
            */
            processor: MeEntrepriseProcessor::class,
            denormalizationContext: ['groups' => ['write:EntrepriseInput']], /*
                - Pour éviter qu'il utilise 'write:Entreprise' sinon il ne vas pas remplir mon 'input'
            */
            openapi: new Operation(
                summary: 'Modifier une entreprise',
                description: 'Permet à l\'administrateur de modifier les informations de son entreprise',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            uriTemplate: '/entreprises/{id}/recharger',
            requirements: ['id' => '\d+'],
            input: RechargerSoldeInput::class,
            processor: RechargerSoldeEntrepriseProcessor::class,
            denormalizationContext: ['groups' => ['write:Recharger']],
            openapi: new Operation(
                summary: 'Recharger le solde d\'une entreprise',
                description: 'Permet de recharger le solde d\'une entreprise',
                security: [['bearerAuth' => []]]
            )
        )
    ],
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'nom' => 'partial',
    'codeentreprise' => 'partial',
    'statut' => 'exact'
])]
class Entreprise extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Entreprise', 'read:Site', 'read:User'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups(['read:Entreprise', 'write:Entreprise'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(min: 2)]
    #[Groups(['read:Entreprise', 'write:Entreprise'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups(['read:Entreprise', 'write:Entreprise'])]
    private ?string $contact1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(min: 2)]
    #[Groups(['read:Entreprise', 'write:Entreprise'])]
    private ?string $contact2 = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    /*
        #[Assert\Regex(
            pattern: '/^[A-Z]{2,6}$/',
            message: 'Le code doit contenir 2 à 6 lettres majuscules uniquement'
        )]
    */
    #[Groups(['read:Entreprise', 'write:Entreprise'])]
    private ?string $codeentreprise = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'entreprise')]
    private Collection $users;

    /**
     * @var Collection<int, Site>
     */
    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'entreprise')]
    private Collection $sites;

    #[ORM\Column]
    #[Groups(['read:Entreprise', 'write:Entreprise'])]
    private ?int $solde = null; // Le solde globale

    #[ORM\Column(length: 255)]
    #[Groups(['read:Entreprise'])]
    private ?string $statut = ReferenceStatus::ACTIF->value;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->sites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getContact1(): ?string
    {
        return $this->contact1;
    }

    public function setContact1(string $contact1): static
    {
        $this->contact1 = $contact1;

        return $this;
    }

    public function getContact2(): ?string
    {
        return $this->contact2;
    }

    public function setContact2(?string $contact2): static
    {
        $this->contact2 = $contact2;

        return $this;
    }

    public function getCodeentreprise(): ?string
    {
        return $this->codeentreprise;
    }

    public function setCodeentreprise(string $codeentreprise): static
    {
        $this->codeentreprise = $codeentreprise;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setEntreprise($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getEntreprise() === $this) {
                $user->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Site>
     */
    public function getSites(): Collection
    {
        return $this->sites;
    }

    public function addSite(Site $site): static
    {
        if (!$this->sites->contains($site)) {
            $this->sites->add($site);
            $site->setEntreprise($this);
        }

        return $this;
    }

    public function removeSite(Site $site): static
    {
        if ($this->sites->removeElement($site)) {
            // set the owning side to null (unless already changed)
            if ($site->getEntreprise() === $this) {
                $site->setEntreprise(null);
            }
        }

        return $this;
    }

    public function getSolde(): ?int
    {
        return $this->solde;
    }

    public function setSolde(int $solde): static
    {
        $this->solde = $solde;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

}
