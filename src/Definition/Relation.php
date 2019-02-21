<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Definition;

final class Relation
{
    /** @var string */
    private $type;

    /** @var string */
    private $target;

    /** @var array */
    private $options = [];

    /** @var bool */
    private $inverse = false;

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
        return $this->target;
    }

    /**
     * @param array $options
     * @return Relation
     */
    public function setOptions(array $options): Relation
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param bool $inverse
     * @return Relation
     */
    public function setInverse(bool $inverse): Relation
    {
        $this->inverse = $inverse;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInverse(): bool
    {
        return $this->inverse;
    }
}