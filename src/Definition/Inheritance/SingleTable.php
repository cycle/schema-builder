<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition\Inheritance;

use Cycle\Schema\Definition\Inheritance;

final class SingleTable extends Inheritance
{
    /** @var array<non-empty-string, class-string> */
    private array $children = [];
    private ?string $discriminator = null;

    public function addChild(string $discriminatorValue, string $class): void
    {
        $this->children[$discriminatorValue] = $class;
    }

    public function hasChild(string $class): bool
    {
        return \in_array($class, $this->children, true);
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getDiscriminator(): ?string
    {
        return $this->discriminator;
    }

    public function setDiscriminator(?string $discriminator): void
    {
        $this->discriminator = $discriminator;
    }
}
