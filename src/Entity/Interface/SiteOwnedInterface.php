<?php

namespace App\Entity\Interface;

use App\Entity\Site;

interface SiteOwnedInterface
{
    public function getSite(): ?Site;

    public function setSite(?Site $site): static;

    public function getCreatedBy(): ?int;

    public function setCreatedBy(?int $createdBy): static;

    public function getUpdatedBy(): ?int;

    public function setUpdatedBy(?int $updatedBy): static;
}