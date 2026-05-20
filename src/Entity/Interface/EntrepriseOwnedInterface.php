<?php

namespace App\Entity\Interface;

use App\Entity\Entreprise;

interface EntrepriseOwnedInterface
{
    public function getEntreprise(): ?Entreprise;

    public function setEntreprise(?Entreprise $entreprise): static;

    public function getCreatedBy(): ?int;

    public function setCreatedBy(?int $createdBy): static;

    public function getUpdatedBy(): ?int;

    public function setUpdatedBy(?int $updatedBy): static;

}