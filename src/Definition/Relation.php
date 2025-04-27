<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition;

use Cycle\Schema\Definition\Map\OptionMap;
use Cycle\Schema\Exception\RelationException;

final class Relation
{
    /** @var OptionMap */
    private $options;

    /** @var string */
    private $type;

    /** @var non-empty-string */
    private $target;

    /** @var string|null */
    private $inverse = null;

    /** @var string|null */
    private $inverseType = null;

    /** @var int|null */
    private $inverseLoad = null;

    /**
     * Relation constructor.
     */
    public function __construct()
    {
        $this->options = new OptionMap();
    }

    public function getOptions(): OptionMap
    {
        return $this->options;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        if ($this->type === null) {
            throw new RelationException('Relation type must be set');
        }

        return $this->type;
    }

    /**
     * @param non-empty-string $target
     *
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return non-empty-string
     */
    public function getTarget(): string
    {
        if ($this->target === null) {
            throw new RelationException('Relation target must be set');
        }

        return $this->target;
    }

    public function setInverse(string $into, string $as, ?int $load = null): self
    {
        $this->inverse = $into;
        $this->inverseType = $as;
        $this->inverseLoad = $load;

        return $this;
    }

    public function isInversed(): bool
    {
        return $this->inverse !== null;
    }

    public function getInverseName(): ?string
    {
        return $this->inverse;
    }

    public function getInverseType(): ?string
    {
        return $this->inverseType;
    }

    public function getInverseLoad(): ?int
    {
        return $this->inverseLoad;
    }

    /**
     * Cloning.
     */
    public function __clone()
    {
        $this->options = clone $this->options;
    }
}
