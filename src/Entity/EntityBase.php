<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\MappedSuperclass] /*
    - Pour que 'Doctrine' l'intègre correctement dans le cycle de vie des entités enfants sinon les attributs 'PrePersist' et 'PreUpdate' peuvent ne pas être prises en compte correctement
*/
#[ORM\HasLifecycleCallbacks]
abstract class EntityBase
{
    #[ORM\Column(name: "created_at", type: "datetime_immutable", nullable: true)]
    #[Groups(['read:Base'])]
    protected ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: "updated_at", type: "datetime_immutable", nullable: true)]
    protected ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: "deleted_at", type: "datetime_immutable", nullable: true)]
    protected ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: "created_by", type: 'integer', nullable: true)]
    protected ?int $createdBy = null;

    #[ORM\Column(name: "updated_by", type: 'integer', nullable: true)]
    protected ?int $updatedBy = null;

    #[ORM\Column(name: "deleted_by", type: 'integer', nullable: true)]
    protected ?int $deletedBy = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?int $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getDeletedBy(): ?int
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?int $deletedBy): static
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }
}
