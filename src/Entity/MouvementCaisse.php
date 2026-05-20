<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Interface\SiteOwnedInterface;
use App\Repository\MouvementCaisseRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MouvementCaisseRepository::class)]
#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    normalizationContext: ['groups' => ['read:MouvementCaisse', 'read:Base'], 'skip_null_values' => false],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    order: ['createdAt' => 'DESC'],
    operations: [
        new GetCollection(
            security: "is_granted('VOIR', 'MouvementCaisse')",
            openapi: new OpenApiOperation(
                summary: 'Liste des mouvements de caisse',
                description: 'Permet de voir la liste des mouvements de caisse',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'],
            openapi: new OpenApiOperation(
                summary: 'Un mouvement de caisse',
                description: 'Permet de voir un mouvement de caisse',
                security: [['bearerAuth' => []]]
            )
        ),
    ],
    openapi: new OpenApiOperation(
        security: [['bearerAuth' => []]]
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'type' => 'exact',
    'site.id' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'montant',
    'createdAt'
])]
class MouvementCaisse extends EntityBase implements SiteOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:MouvementCaisse'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:MouvementCaisse'])]
    private ?string $type = null; // TypeMouvement::CREDIT / DEBIT

    #[ORM\Column]
    #[Groups(['read:MouvementCaisse'])]
    private ?int $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:MouvementCaisse'])]
    private ?string $motif = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:MouvementCaisse'])]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[Groups(['read:MouvementCaisse'])]
    private ?Paiement $paiement = null; // On le renseigne si le mouvement correspond à un paiement de planteur 'debit'

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[Groups(['read:MouvementCaisse'])]
    private ?DemandeSolde $demandeSolde = null; // !! mouvement correspond à une recharge de solde 'credit'

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
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

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        $this->paiement = $paiement;

        return $this;
    }

    public function getDemandeSolde(): ?DemandeSolde
    {
        return $this->demandeSolde;
    }

    public function setDemandeSolde(?DemandeSolde $demandeSolde): static
    {
        $this->demandeSolde = $demandeSolde;

        return $this;
    }
}
