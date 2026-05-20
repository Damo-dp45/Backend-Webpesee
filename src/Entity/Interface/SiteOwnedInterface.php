<?php

namespace App\Entity\Interface;

use App\Entity\Site;

interface SiteOwnedInterface
{
    public function getSite(): ?Site;

    public function setSite(?Site $site): static;
}