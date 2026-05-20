<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Interface\SiteOwnedInterface;
use App\Repository\DemandeSoldeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Domain\Enum\StatutDemande;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DemandeSoldeRepository::class)]
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
            openapi: new OpenApiOperation(
                summary: 'Liste des demandes de solde',
                description: 'Permet de voir la liste des demandes de solde',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new OpenApiOperation(
                summary: 'Une demande de solde',
                description: 'Permet de voir une demandes de solde',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'DemandeSolde')", /*
                - Uniquement l'opérateur dont le site est épuisé
            */
            // processor: DemandeSoldeProcessor::class,
            openapi: new OpenApiOperation(
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
            /*
            processor: ApprouverDemandeProcessor::class, 
                - Crédite Site.solde, débite Entreprise.solde, crée MouvementCaisse
            */
            openapi: new OpenApiOperation(
                summary: 'Approuver une demande de solde',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('TRAITER', object)",
            uriTemplate: '/demandes-solde/{id}/rejeter',
            requirements: ['id' => '\d+'],
            // input: RejeterDemandeInput::class,
            // processor: RejeterDemandeProcessor::class,
            denormalizationContext: ['groups' => ['write:RejeterDemande']],
            openapi: new OpenApiOperation(
                summary: 'Rejeter une demande de solde',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new OpenApiOperation(
        security: [['bearerAuth' => []]]
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'statut' => 'exact',
    'site.id' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
class DemandeSolde extends EntityBase implements SiteOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:DemandeSolde'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['read:DemandeSolde', 'write:DemandeSolde'])]
    private ?int $montantdemande = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:DemandeSolde'])]
    private ?string $statut = StatutDemande::EN_ATTENTE->value;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:DemandeSolde', 'write:DemandeSolde'])]
    private ?string $motif = null;

    #[ORM\ManyToOne(inversedBy: 'demandeSoldes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:DemandeSolde'])]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'demandeSoldes')]
    #[Groups(['read:DemandeSolde'])]
    private ?User $traitePar = null; // L'utilisateur qui a traité la demande 'agent' ou 'admin'

    #[ORM\Column(nullable: true)]
    #[Groups(['read:DemandeSolde'])]
    private ?\DateTimeImmutable $traiteAt = null;

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'demandeSolde')]
    private Collection $mouvementCaisses;

    public function __construct()
    {
        $this->mouvementCaisses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontantdemande(): ?int
    {
        return $this->montantdemande;
    }

    public function setMontantdemande(int $montantdemande): static
    {
        $this->montantdemande = $montantdemande;

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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

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

    public function getTraitePar(): ?User
    {
        return $this->traitePar;
    }

    public function setTraitePar(?User $traitePar): static
    {
        $this->traitePar = $traitePar;

        return $this;
    }

    public function getTraiteAt(): ?\DateTimeImmutable
    {
        return $this->traiteAt;
    }

    public function setTraiteAt(?\DateTimeImmutable $traiteAt): static
    {
        $this->traiteAt = $traiteAt;

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
            $mouvementCaiss->setDemandeSolde($this);
        }

        return $this;
    }

    public function removeMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if ($this->mouvementCaisses->removeElement($mouvementCaiss)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCaiss->getDemandeSolde() === $this) {
                $mouvementCaiss->setDemandeSolde(null);
            }
        }

        return $this;
    }
}
