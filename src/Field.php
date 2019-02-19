<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

/**
 * Field declaration, it's type and mapping to column.
 */
final class Field
{
    /** @var array|string */
    private $typecast;

    /** @var string */
    private $column;

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
     */
    public function getColumn(): string
    {
        return $this->column;
    }
}