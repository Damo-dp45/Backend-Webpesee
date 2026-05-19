<?php

namespace App\Entity;

use App\Repository\MouvementCaisseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementCaisseRepository::class)]
class MouvementCaisse extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?int $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motif = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    private ?Paiement $paiement = null; // On le renseigne si le mouvement correspond à un paiement de planteur 'debit'

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
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
