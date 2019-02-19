<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Map;

use Cycle\Schema\Field;

/**
 * Manage the set of fields associated with the entity.
 */
final class FieldMap
{
    /** @var Field[] */
    private $fields = [];

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * @param string $name
     * @return Field
     */
    public function get(string $name): Field
    {
        // todo: check exists
        return $this->fields[$name];
    }

    /**
     * @param string $name
     * @param Field  $field
     * @return FieldMap
     */
    public function set(string $name, Field $field): self
    {
        $this->fields[$name] = $field;

        // todo: check exists
        return $this;
    }

    /**
     * @return array
     */
    public function packColumns(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function packTypecast(): array
    {
        return [];
    }
}