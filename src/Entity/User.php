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
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Enum\ReferenceStatus;
use App\Entity\Input\ChangePasswordInput;
use App\Entity\Input\ForgotPasswordInput;
use App\Entity\Input\RegisterInput;
use App\Entity\Input\ResetPasswordInput;
use App\Repository\UserRepository;
use App\State\ChangePasswordProcessor;
use App\State\ForgotPasswordProcessor;
use App\State\RegisterProcessor;
use App\State\ResetPasswordProcessor;
use App\State\SuspendreUserProcessor;
use App\State\UserProcessor;
use App\State\UserProfileProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    normalizationContext: ['groups' => ['read:User'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['write:User']],
    paginationItemsPerPage: 25,
    paginationClientItemsPerPage: true,
    operations: [
        new Post(
            name: 'Register',
            uriTemplate: '/register',
            input: RegisterInput::class,
            processor: RegisterProcessor::class,
            denormalizationContext: ['groups' => ['write:Register']],
            status: Response::HTTP_CREATED,
            openapi: new Operation(
                summary: 'Permet à un utilisateur de créer une entreprise et devenir administrateur',
                description: 'Crée un nouvel utilisateur et son entreprise'
            )
        ),
        /* Admin
         */
        new GetCollection(
            security: "is_granted('VOIR', 'User')", /*
                - Pour le filtre du 'entreprise' on l'a fais dans 'UserEntrepriseExtension' et on.. 'ROLE_ADMIN' qui donne accès à l'admin et au super admin gràce à 'role_hierarchy'
            */
            openapi: new Operation(
                summary: 'La liste des utilisateurs',
                description: 'Permet de voir la liste des utilisateurs',
                security: [['bearerAuth' => []]]
            )
        ),
        new Get(
            security: "is_granted('VOIR', object)",
            requirements: ['id' => '\d+'], /*
                - Pour le filtre du 'entreprise' on l'a fais dans 'UserEntrepriseExtension'
            */
            openapi: new Operation(
                summary: 'L\'utilisateur',
                description: 'Permet de voir un utilisateur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('CREER', 'User')",
            processor: UserProcessor::class,
            openapi: new Operation(
                summary: 'Créer un utilisateur',
                description: 'Permet de créer un utilisateur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('MODIFIER', object)",
            requirements: ['id' => '\d+'], 
            processor: UserProcessor::class,
            openapi: new Operation(
                summary: 'Modifier un utilisateur',
                description: 'Permet de modifier un utilisateur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')",
            uriTemplate: '/users/{id}/suspendre',
            requirements: ['id' => '\d+'],
            input: false,
            processor: SuspendreUserProcessor::class,
            openapi: new Operation(
                summary: 'Suspendre ou réactiver un utilisateur',
                security: [['bearerAuth' => []]]
            )
        ),
        /* Me
         */
        new Get(
            security: "is_granted('ROLE_USER')",
            name: 'Me',
            uriTemplate: '/me',
            paginationEnabled: false,
            openapi: new Operation(
                security: [['bearerAuth' => []]]
            )
        ),
        new Patch(
            security: "is_granted('ROLE_USER')",
            uriTemplate: '/me',
            denormalizationContext: ['groups' => ['write:User:profil']],
            processor: UserProfileProcessor::class,
            openapi: new Operation(
                summary: 'Modification du profil utilisateur',
                description: 'Permet de mettre un profil utilisateur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            uriTemplate: '/me/password',
            input: ChangePasswordInput::class,
            processor: ChangePasswordProcessor::class,
            denormalizationContext: ['groups' => ['write:User:password']],
            openapi: new Operation(
                summary: 'Modification de mot de passe utilisateur',
                description: 'Permet de mettre un mot de passe utilisateur',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            uriTemplate: '/forgot',
            denormalizationContext: ['groups' => ['write:ForgotPasswordInput']],
            input: ForgotPasswordInput::class,
            processor: ForgotPasswordProcessor::class,
            openapi: new Operation(
                summary: 'Demande de réinitialisation de mot de passe',
                description: 'Permet de demander la réinitialisation du mot de passe'
            )
        ),
        new Post(
            uriTemplate: '/reset',
            denormalizationContext: ['groups' => ['write:ResetPasswordInput']],
            input: ResetPasswordInput::class,
            processor: ResetPasswordProcessor::class,
            openapi: new Operation(
                summary: 'Modification du mot de passe',
                description: 'Permet de modifier le mot de passe'
            )
        )
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'nom' => 'partial',
    'email' => 'partial',
    'statut' => 'exact'
])]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'nom',
    'prenom'
])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:User'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank()]
    #[Assert\Email()]
    #[Groups(['read:User', 'write:User', 'write:User:profil'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['read:User'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups(['read:User', 'write:User', 'write:User:profil'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[Groups(['read:User', 'write:User', 'write:User:profil'])]
    private ?string $prenom = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(['read:User'])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(length: 255)] // On.. 'options: ['default' => '']'
    #[Groups(['read:User'])]
    private ?string $statut = ReferenceStatus::ACTIF->value;

    #[Groups(['write:User'])]
    private ?string $plainPassword = null;

    /**
     * @var Collection<int, Site>
     */
    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'operateur')]
    private Collection $sites; // Les sites là ou l'utilisateur est l'opérateur

    /**
     * @var Collection<int, DemandeSolde>
     */
    #[ORM\OneToMany(targetEntity: DemandeSolde::class, mappedBy: 'traitePar')]
    private Collection $demandeSoldes;

    /**
     * @var Collection<int, PasswordResetToken>
     */
    #[ORM\OneToMany(targetEntity: PasswordResetToken::class, mappedBy: 'user')]
    private Collection $passwordResetTokens;

    public function __construct()
    {
        $this->sites = new ArrayCollection();
        $this->demandeSoldes = new ArrayCollection();
        $this->passwordResetTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
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

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

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
            $site->setOperateur($this);
        }

        return $this;
    }

    public function removeSite(Site $site): static
    {
        if ($this->sites->removeElement($site)) {
            // set the owning side to null (unless already changed)
            if ($site->getOperateur() === $this) {
                $site->setOperateur(null);
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
            $demandeSolde->setTraitePar($this);
        }

        return $this;
    }

    public function removeDemandeSolde(DemandeSolde $demandeSolde): static
    {
        if ($this->demandeSoldes->removeElement($demandeSolde)) {
            // set the owning side to null (unless already changed)
            if ($demandeSolde->getTraitePar() === $this) {
                $demandeSolde->setTraitePar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PasswordResetToken>
     */
    public function getPasswordResetTokens(): Collection
    {
        return $this->passwordResetTokens;
    }

    public function addPasswordResetToken(PasswordResetToken $passwordResetToken): static
    {
        if (!$this->passwordResetTokens->contains($passwordResetToken)) {
            $this->passwordResetTokens->add($passwordResetToken);
            $passwordResetToken->setUser($this);
        }

        return $this;
    }

    public function removePasswordResetToken(PasswordResetToken $passwordResetToken): static
    {
        if ($this->passwordResetTokens->removeElement($passwordResetToken)) {
            // set the owning side to null (unless already changed)
            if ($passwordResetToken->getUser() === $this) {
                $passwordResetToken->setUser(null);
            }
        }

        return $this;
    }

    public function getSitesCount(): int
    {
        return $this->sites->count();
    }
}
