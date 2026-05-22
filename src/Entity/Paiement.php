<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Domain\Enum\ModePaiement;
use App\Domain\Enum\StatutPaiement;
use App\Entity\Interface\SiteOwnedInterface;
use App\Repository\PaiementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Entity\Input\PaiementInput;
use App\State\PaiementProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:Paiement', 'read:Base'], 'skip_null_values' => false],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'Paiement')",
            openapi: new OpenApiOperation(
                summary: 'La liste des paiements',
                description: 'Permet de voir la liste des paiements',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new OpenApiOperation(
                summary: 'Le paiement',
                description: 'Permet de voir un paiement',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'Paiement')",
            input: PaiementInput::class,
            processor: PaiementProcessor::class,
            denormalizationContext: ['groups' => ['write:PaiementInput']], /*
                - Le processor calcule le montant, débite le site, crée le 'MouvementCaisse' et déclenche le mobile money si besoin
            */
            openapi: new OpenApiOperation(
                summary: 'Créer un paiement',
                description: 'Payer un fournisseur ou planteur en une ou plusieurs fois',
                security: [['bearerAuth' => []]]
            )
        )
    ],
    openapi: new OpenApiOperation(
        security: [['bearerAuth' => []]]
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'statut' => 'exact',
    'modepaiement' => 'exact',
    'fournisseur.id' => 'exact',
    'site.id' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'montant',
    'createdAt'
])]
class Paiement extends EntityBase implements SiteOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Paiement', 'read:MouvementCaisse'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['read:Paiement', 'read:MouvementCaisse'])]
    private ?int $montant = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Paiement'])]
    private ?string $modepaiement = ModePaiement::ESPECES->value;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Paiement'])]
    private ?string $referencemobile = null; // La référence de transaction mobile money 'numéro de reçu', 'Id transaction'

    #[ORM\Column(length: 255)]
    #[Groups(['read:Paiement'])]
    private ?string $statut = StatutPaiement::EN_ATTENTE->value;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[Groups(['read:Paiement'])]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[Groups(['read:Paiement'])]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[Groups(['read:Paiement'])]
    private ?Operation $operation = null; // La pesée associée, 'optionnel' vu que le paiement global est possible

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'paiement')]
    private Collection $mouvementCaisses;

    public function __construct()
    {
        $this->mouvementCaisses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getModepaiement(): ?string
    {
        return $this->modepaiement;
    }

    public function setModepaiement(string $modepaiement): static
    {
        $this->modepaiement = $modepaiement;

        return $this;
    }

    public function getReferencemobile(): ?string
    {
        return $this->referencemobile;
    }

    public function setReferencemobile(?string $referencemobile): static
    {
        $this->referencemobile = $referencemobile;

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

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

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

    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    public function setOperation(?Operation $operation): static
    {
        $this->operation = $operation;

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
            $mouvementCaiss->setPaiement($this);
        }

        return $this;
    }

    public function removeMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if ($this->mouvementCaisses->removeElement($mouvementCaiss)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCaiss->getPaiement() === $this) {
                $mouvementCaiss->setPaiement(null);
            }
        }

        return $this;
    }
}
