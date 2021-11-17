<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition\Inheritance;

use Cycle\Schema\Definition\Inheritance;

final class SingleTable extends Inheritance
{
    /** @var array<non-empty-string, class-string> */
    private array $children = [];
    private ?string $discriminator = null;

    /**
     * @param non-empty-string $discriminatorValue
     * @param class-string $class
     */
    public function addChild(string $discriminatorValue, string $class): void
    {
        $this->children[$discriminatorValue] = $class;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getDiscriminator(): ?string
    {
        return $this->discriminator;
    }

    /**
     * @param non-empty-string|null $discriminator
     */
    public function setDiscriminator(?string $discriminator): void
    {
        $this->discriminator = $discriminator;
    }
}
