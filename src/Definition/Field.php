<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Definition;

use Cycle\Schema\Definition\Map\OptionMap;
use Cycle\Schema\Definition\Traits\OptionsTrait;
use Cycle\Schema\Exception\FieldException;

/**
 * Field declaration, it's type and mapping to column.
 */
final class Field
{
    use OptionsTrait;

    /** @var string */
    private $column;

    /** @var string */
    private $type;

    /** @var array|string */
    private $typecast;

    /** @var bool */
    private $referenced = false;

    /**
     * Field constructor.
     */
    public function __construct()
    {
        $this->options = new OptionMap();
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if (empty($this->column)) {
            throw new FieldException("Field type must be set");
        }

        return $this->type;
    }

    /**
     * @param string $type
     * @return Field
     */
    public function setType(string $type): Field
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $column
     * @return Field
     */
    public function setColumn(string $column): Field
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @return string
     *
     * @throws FieldException
     */
    public function getColumn(): string
    {
        if (empty($this->column)) {
            throw new FieldException("Column mapping must be set");
        }

        return $this->column;
    }

    /**
     * @param array|string $typecast
     * @return Field
     */
    public function setTypecast($typecast)
    {
        $this->typecast = $typecast;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasTypecast(): bool
    {
        return $this->typecast !== null;
    }

    /**
     * @return array|string
     */
    public function getTypecast()
    {
        return $this->typecast;
    }

    /**
     * @param bool $indexed
     * @return Field
     */
    public function setReferenced(bool $indexed): Field
    {
        $this->referenced = $indexed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReferenced(): bool
    {
        return $this->referenced;
    }
}