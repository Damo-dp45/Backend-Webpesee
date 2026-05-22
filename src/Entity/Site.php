<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Domain\Enum\SiteStatus;
use App\Entity\Input\AssignerOperateurInput;
use App\Entity\Input\AttribuerSoldeInput;
use App\Entity\Interface\EntrepriseOwnedInterface;
use App\Repository\SiteRepository;
use App\State\AssignerOperateurProcessor;
use App\State\AttribuerSoldeProcessor;
use App\State\SiteProcessor;
use App\State\SuspendreSiteProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[UniqueEntity(fields: ['codesite'], message: 'Ce code site est déjà utilisé')]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Site', 'read:Base'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:Site']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Site')",
            openapi: new OpenApiOperation(
                summary: 'La liste des sites',
                description: 'Permet de voir la liste des sites',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new OpenApiOperation(
                summary: 'Un site',
                description: 'Permet de voir un site',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Site')",
            processor: SiteProcessor::class,
            openapi: new OpenApiOperation(
                summary: 'Créer un site',
                description: 'Permet de créer un site',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'],
            processor: SiteProcessor::class,
            openapi: new OpenApiOperation(
                summary: 'Modifier un site',
                description: 'Permet de modifier un site',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')",
            uriTemplate: '/sites/{id}/suspendre',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SuspendreSiteProcessor::class,
            openapi: new OpenApiOperation(
                summary: 'Bloquer ou débloquer un site',
                description: 'Permet de bloquer ou débloquer un site',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_AGENT')",
            uriTemplate: '/sites/{id}/assigner',
            requirements: ['id' => '\d+'],
            input: AssignerOperateurInput::class,
            processor: AssignerOperateurProcessor::class,
            denormalizationContext: ['groups' => ['write:Assigner']],
            openapi: new OpenApiOperation(
                summary: 'Assigner un opérateur à un site',
                description: 'Permet d\'assigner un opérateur à un site',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            uriTemplate: '/sites/{id}/attribuersolde',
            requirements: ['id' => '\d+'],
            input: AttribuerSoldeInput::class,
            processor: AttribuerSoldeProcessor::class,
            denormalizationContext: ['groups' => ['write:Attribuer']],
            openapi: new OpenApiOperation(
                summary: 'Attribuer un solde à un site',
                description: 'Débite le solde de l\'entreprise et crédite le site',
                security: [['bearerAuth' => []]]
            )
        )
    ],
    openapi: new OpenApiOperation(
        security: [['bearerAuth' => []]]
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'codesite' => 'partial',
    'libellesite' => 'partial',
    'statut' => 'exact'
])]
class Site extends EntityBase implements EntrepriseOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Site', 'read:Operation', 'read:Fournisseur', 'read:Produit', 'read:User'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['read:Site', 'write:Site'])]
    private ?string $codesite = null; // On.. 'unique' par entreprise

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['read:Site', 'write:Site'])]
    private ?string $libellesite = null;

    #[ORM\ManyToOne(inversedBy: 'sites')]
    #[Groups(['read:Site'])]
    private ?Entreprise $entreprise = null; // On.. 'nullable' pour ne pas casser la synchro desktop

    #[ORM\Column]
    #[Groups(['read:Site'])]
    private ?int $solde = null; // Le solde attribué par l'entreprise

    #[ORM\Column(length: 255)]
    #[Groups(['read:Site'])]
    private ?string $statut = SiteStatus::ACTIF->value;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'site')]
    private Collection $operations;

    /**
     * @var Collection<int, Fournisseur>
     */
    #[ORM\OneToMany(targetEntity: Fournisseur::class, mappedBy: 'site')]
    private Collection $fournisseurs;

    /**
     * @var Collection<int, Produit>
     */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'site')]
    private Collection $produits;

    #[ORM\ManyToOne(inversedBy: 'sites')]
    #[Groups(['read:Site'])]
    private ?User $operateur = null; // Un site a un opérateur et un opérateur peut gérer plusieurs sites

    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'site')]
    private Collection $paiements;

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'site')]
    private Collection $mouvementCaisses;

    /**
     * @var Collection<int, DemandeSolde>
     */
    #[ORM\OneToMany(targetEntity: DemandeSolde::class, mappedBy: 'site')]
    private Collection $demandeSoldes;

    public function __construct()
    {
        $this->operations = new ArrayCollection();
        $this->fournisseurs = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->paiements = new ArrayCollection();
        $this->mouvementCaisses = new ArrayCollection();
        $this->demandeSoldes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodesite(): ?string
    {
        return $this->codesite;
    }

    public function setCodesite(string $codesite): static
    {
        $this->codesite = $codesite;

        return $this;
    }

    public function getLibellesite(): ?string
    {
        return $this->libellesite;
    }

    public function setLibellesite(string $libellesite): static
    {
        $this->libellesite = $libellesite;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

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
            $operation->setSite($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getSite() === $this) {
                $operation->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Fournisseur>
     */
    public function getFournisseurs(): Collection
    {
        return $this->fournisseurs;
    }

    public function addFournisseur(Fournisseur $fournisseur): static
    {
        if (!$this->fournisseurs->contains($fournisseur)) {
            $this->fournisseurs->add($fournisseur);
            $fournisseur->setSite($this);
        }

        return $this;
    }

    public function removeFournisseur(Fournisseur $fournisseur): static
    {
        if ($this->fournisseurs->removeElement($fournisseur)) {
            // set the owning side to null (unless already changed)
            if ($fournisseur->getSite() === $this) {
                $fournisseur->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): static
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setSite($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): static
    {
        if ($this->produits->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getSite() === $this) {
                $produit->setSite(null);
            }
        }

        return $this;
    }

    public function getOperateur(): ?User
    {
        return $this->operateur;
    }

    public function setOperateur(?User $operateur): static
    {
        $this->operateur = $operateur;

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
            $paiement->setSite($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getSite() === $this) {
                $paiement->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MouvementCaisse>
     */
    public function getMouvementCaisses(): Collection
    {
        return $this->mouvementCaisses;
    }

    public function addMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if (!$this->mouvementCaisses->contains($mouvementCaiss)) {
            $this->mouvementCaisses->add($mouvementCaiss);
            $mouvementCaiss->setSite($this);
        }

        return $this;
    }

    public function removeMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if ($this->mouvementCaisses->removeElement($mouvementCaiss)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCaiss->getSite() === $this) {
                $mouvementCaiss->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DemandeSolde>
     */
    public function getDemandeSoldes(): Collection
    {
        return $this->demandeSoldes;
    }

    public function addDemandeSolde(DemandeSolde $demandeSolde): static
    {
        if (!$this->demandeSoldes->contains($demandeSolde)) {
            $this->demandeSoldes->add($demandeSolde);
            $demandeSolde->setSite($this);
        }

        return $this;
    }

    public function removeDemandeSolde(DemandeSolde $demandeSolde): static
    {
        if ($this->demandeSoldes->removeElement($demandeSolde)) {
            // set the owning side to null (unless already changed)
            if ($demandeSolde->getSite() === $this) {
                $demandeSolde->setSite(null);
            }
        }

        return $this;
    }
}
