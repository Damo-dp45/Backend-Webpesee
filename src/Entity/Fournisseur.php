<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Domain\Enum\ReferenceStatus;
use App\Entity\Interface\SiteOwnedInterface;
use App\Repository\FournisseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FournisseurRepository::class)]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Fournisseur', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Fournisseur']],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Fournisseur')",
            openapi: new OpenApiOperation(
                summary: 'La liste des fournisseurs',
                description: 'Permet de voir la liste des fournisseurs',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new OpenApiOperation(
                summary: 'Le fournisseur',
                description: 'Permet de voir un fournisseur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Fournisseur')",
            // processor: EntrepriseInjectionProcessor::class, -- FournisseurProcessor
            openapi: new OpenApiOperation(
                summary: 'Création d\'un fournisseur',
                description: 'Permet de créer un fournisseur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            // processor: UpdatedbyProcessor::class,
            openapi: new OpenApiOperation(
                summary: 'Modification d\'un fournisseur',
                description: 'Permet de modifier un fournisseur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/fournisseurs/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            // processor: SoftDeleteProcessor::class,
            openapi: new OpenApiOperation(
                summary: 'Mise en corbeille d\'un fournisseur',
                description: 'Permet de mettre un fournisseur en corbeille',
                security: [['bearerAuth' => []]]
            )
        )
    ],
    openapi: new OpenApiOperation(
        security: [['bearerAuth' => []]]
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'nom' => 'partial',
    'codefournisseur' => 'partial',
    'statut' => 'exact',
    'site.id' => 'exact'
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'createdAt'
])]
class Fournisseur extends EntityBase implements SiteOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Fournisseur'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Fournisseur'])]
    private ?string $codefournisseur = null; // On.. 'nullable' pour ne pas bloquer le desktop

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur', 'read:Operation', 'read:Paiement'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?string $prenom = null; // On.. 'nullable' pour ne pas bloquer le desktop

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 10)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?string $contact1 = null; // On.. 'nullable' pour ne pas bloquer le desktop

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Length(min: 10)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?string $contact2 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?int $prixspeciale = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = ReferenceStatus::ACTIF->value;

    #[ORM\ManyToOne(inversedBy: 'fournisseurs')]
    #[Groups(['read:Fournisseur', 'write:Fournisseur'])]
    private ?Site $site = null; // !!

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

    public function __construct()
    {
        $this->operations = new ArrayCollection();
        $this->paiements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodefournisseur(): ?string
    {
        return $this->codefournisseur;
    }

    public function setCodefournisseur(?string $codefournisseur): static
    {
        $this->codefournisseur = $codefournisseur;

        return $this;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getContact1(): ?string
    {
        return $this->contact1;
    }

    public function setContact1(?string $contact1): static
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

    public function getPrixspeciale(): ?int
    {
        return $this->prixspeciale;
    }

    public function setPrixspeciale(?int $prixspeciale): static
    {
        $this->prixspeciale = $prixspeciale;

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

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setFournisseur($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getFournisseur() === $this) {
                $operation->setFournisseur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setFournisseur($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getFournisseur() === $this) {
                $paiement->setFournisseur(null);
            }
        }

        return $this;
    }

}
