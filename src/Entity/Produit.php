<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Entity\Interface\SiteOwnedInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Produit', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Produit']],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Produit')",
            openapi: new OpenApiOperation(
                summary: 'La liste des produits',
                description: 'Permet de voir la liste des produits',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new OpenApiOperation(
                summary: 'Le produit',
                description: 'Permet de voir un produit',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Produit')",
            // processor: EntrepriseInjectionProcessor::class, -- ProduitProcessor
            openapi: new OpenApiOperation(
                summary: 'Création d\'un produit',
                description: 'Permet de créer un produit',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            // processor: UpdatedbyProcessor::class,
            openapi: new OpenApiOperation(
                summary: 'Modification d\'un produit',
                description: 'Permet de modifier un produit',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('SUPPRIMER', object)",
            uriTemplate: '/produits/{id}/remove',
            requirements: ['id' => '\d+'],
            input: false,
            // processor: SoftDeleteProcessor::class,
            openapi: new OpenApiOperation(
                summary: 'Mise en corbeille d\'un produit',
                description: 'Permet de mettre un produit en corbeille',
                security: [['bearerAuth' => []]]
            )
        )
    ],
    openapi: new OpenApiOperation(
        security: [['bearerAuth' => []]]
    )
)]
class Produit extends EntityBase implements SiteOwnedInterface
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
    private ?string $libelle = null; // Ou.. le libelle brut venant du desktop lors de la création automatique

    #[ORM\Column]
    #[Groups(['read:Produit', 'write:Produit'])]
    private ?int $prix = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    #[Groups(['read:Produit', 'write:Produit'])]
    private ?Site $site = null;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'produit')]
    private Collection $operations;

    public function __construct()
    {
        $this->operations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeproduit(): ?string
    {
        return $this->codeproduit;
    }

    public function setCodeproduit(?string $codeproduit): static
    {
        $this->codeproduit = $codeproduit;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): static
    {
        $this->prix = $prix;

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
            $operation->setProduit($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getProduit() === $this) {
                $operation->setProduit(null);
            }
        }

        return $this;
    }

}
