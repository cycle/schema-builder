<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Definition;

use Cycle\Schema\Exception\FieldException;

/**
 * Field declaration, it's type and mapping to column.
 */
final class Field
{
    /** @var array|string */
    private $typecast;

    /** @var string */
    private $column;

    /** @var bool */
    private $referenced = false;

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