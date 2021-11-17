<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition\Inheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Inheritance;

final class JoinedTable extends Inheritance
{
    public function __construct(
        private Entity $parent,
        private ?string $outerKey = null
    ) {
    }

    public function getOuterKey(): ?string
    {
        return $this->outerKey;
    }

    public function getParent(): Entity
    {
        return $this->parent;
    }
}
