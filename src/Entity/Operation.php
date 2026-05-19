<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $codesecret = null;

    #[ORM\Column(length: 255)]
    private ?string $mouvement = null;

    #[ORM\Column(length: 255)]
    private ?string $client = null;

    #[ORM\Column(length: 255)]
    private ?string $destination = null;

    #[ORM\Column(length: 255)]
    private ?string $provenance = null;

    #[ORM\Column(length: 255)]
    private ?string $transporteur = null;

    #[ORM\Column(length: 255)]
    private ?string $chauffeur = null;

    #[ORM\Column(length: 255)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $remorque = null;

    #[ORM\Column]
    private ?int $poids1 = null;

    #[ORM\Column]
    private ?int $poids2 = null;

    #[ORM\Column]
    private ?int $poidsbrut = null;

    #[ORM\Column]
    private ?int $poidsnet = null;

    #[ORM\Column]
    private ?\DateTime $date1 = null;

    #[ORM\Column]
    private ?\DateTime $date2 = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $temps1 = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $temps2 = null;

    #[ORM\Column]
    private ?\DateTime $datesearch = null;

    #[ORM\Column(length: 255)]
    private ?string $codepesee = null;

    #[ORM\Column(length: 255)]
    private ?string $numticket = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $peseur = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Produit $produit = null;

    #[ORM\Column(length: 255)]
    private ?string $codesite = null; // L'id interne de l'application desktop '1, 2..'

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libellesite = null;

    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'operation')]
    private Collection $paiements;

    #[ORM\Column(nullable: true)]
    private ?int $prixunitaire = null; // Le prix unitaire appliqué au moment de la pesée '=' prixspeciale du fournisseur si défini, sinon prix du produit

    #[ORM\Column(nullable: true)]
    private ?int $montantcalcule = null; // Le 'poidsnet' × 'prixunitaire'

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodesecret(): ?string
    {
        return $this->codesecret;
    }

    public function setCodesecret(string $codesecret): static
    {
        $this->codesecret = $codesecret;

        return $this;
    }

    public function getMouvement(): ?string
    {
        return $this->mouvement;
    }

    public function setMouvement(string $mouvement): static
    {
        $this->mouvement = $mouvement;

        return $this;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(string $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getProvenance(): ?string
    {
        return $this->provenance;
    }

    public function setProvenance(string $provenance): static
    {
        $this->provenance = $provenance;

        return $this;
    }

    public function getTransporteur(): ?string
    {
        return $this->transporteur;
    }

    public function setTransporteur(string $transporteur): static
    {
        $this->transporteur = $transporteur;

        return $this;
    }

    public function getChauffeur(): ?string
    {
        return $this->chauffeur;
    }

    public function setChauffeur(string $chauffeur): static
    {
        $this->chauffeur = $chauffeur;

        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getRemorque(): ?string
    {
        return $this->remorque;
    }

    public function setRemorque(?string $remorque): static
    {
        $this->remorque = $remorque;

        return $this;
    }

    public function getPoids1(): ?int
    {
        return $this->poids1;
    }

    public function setPoids1(int $poids1): static
    {
        $this->poids1 = $poids1;

        return $this;
    }

    public function getPoids2(): ?int
    {
        return $this->poids2;
    }

    public function setPoids2(int $poids2): static
    {
        $this->poids2 = $poids2;

        return $this;
    }

    public function getPoidsbrut(): ?int
    {
        return $this->poidsbrut;
    }

    public function setPoidsbrut(int $poidsbrut): static
    {
        $this->poidsbrut = $poidsbrut;

        return $this;
    }

    public function getPoidsnet(): ?int
    {
        return $this->poidsnet;
    }

    public function setPoidsnet(int $poidsnet): static
    {
        $this->poidsnet = $poidsnet;

        return $this;
    }

    public function getDate1(): ?\DateTime
    {
        return $this->date1;
    }

    public function setDate1(\DateTime $date1): static
    {
        $this->date1 = $date1;

        return $this;
    }

    public function getDate2(): ?\DateTime
    {
        return $this->date2;
    }

    public function setDate2(\DateTime $date2): static
    {
        $this->date2 = $date2;

        return $this;
    }

    public function getTemps1(): ?\DateTime
    {
        return $this->temps1;
    }

    public function setTemps1(\DateTime $temps1): static
    {
        $this->temps1 = $temps1;

        return $this;
    }

    public function getTemps2(): ?\DateTime
    {
        return $this->temps2;
    }

    public function setTemps2(\DateTime $temps2): static
    {
        $this->temps2 = $temps2;

        return $this;
    }

    public function getDatesearch(): ?\DateTime
    {
        return $this->datesearch;
    }

    public function setDatesearch(\DateTime $datesearch): static
    {
        $this->datesearch = $datesearch;

        return $this;
    }

    public function getCodepesee(): ?string
    {
        return $this->codepesee;
    }

    public function setCodepesee(string $codepesee): static
    {
        $this->codepesee = $codepesee;

        return $this;
    }

    public function getNumticket(): ?string
    {
        return $this->numticket;
    }

    public function setNumticket(string $numticket): static
    {
        $this->numticket = $numticket;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getPeseur(): ?string
    {
        return $this->peseur;
    }

    public function setPeseur(?string $peseur): static
    {
        $this->peseur = $peseur;

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

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
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

    public function setLibellesite(?string $libellesite): static
    {
        $this->libellesite = $libellesite;

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
            $paiement->setOperation($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getOperation() === $this) {
                $paiement->setOperation(null);
            }
        }

        return $this;
    }

    public function getPrixunitaire(): ?int
    {
        return $this->prixunitaire;
    }

    public function setPrixunitaire(?int $prixunitaire): static
    {
        $this->prixunitaire = $prixunitaire;

        return $this;
    }

    public function getMontantcalcule(): ?int
    {
        return $this->montantcalcule;
    }

    public function setMontantcalcule(?int $montantcalcule): static
    {
        $this->montantcalcule = $montantcalcule;

        return $this;
    }

}
