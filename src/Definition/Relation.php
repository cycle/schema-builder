<?php
/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
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

    /** @var string */
    private $target;

    /** @var string|null */
    private $inverse = null;

    /** @var string|null */
    private $inverseType = null;

    /**
     * Relation constructor.
     */
    public function __construct()
    {
        $this->options = new OptionMap();
    }

    /**
     * @return OptionMap
     */
    public function getOptions(): OptionMap
    {
        return $this->options;
    }

    /**
     * @param string $type
     * @return Relation
     */
    public function setType(string $type): Relation
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if ($this->type === null) {
            throw new RelationException("Relation type must be set");
        }

        return $this->type;
    }

    /**
     * @param string $target
     * @return Relation
     */
    public function setTarget(string $target): Relation
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        if ($this->target === null) {
            throw new RelationException("Relation target must be set");
        }

        return $this->target;
    }

    /**
     * @param string $into
     * @param string $as
     * @return Relation
     */
    public function setInverse(string $into, string $as): Relation
    {
        $this->inverse = $into;
        $this->inverseType = $as;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInversed(): bool
    {
        return $this->inverse != null;
    }

    /**
     * @return string|null
     */
    public function getInverseName(): ?string
    {
        return $this->inverse;
    }

    /**
     * @return string|null
     */
    public function getInverseType(): ?string
    {
        return $this->inverseType;
    }
}