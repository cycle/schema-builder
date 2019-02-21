<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Map;

use Cycle\ORM\Schema;
use Cycle\Schema\Exception\BuilderException;
use Cycle\Schema\Exception\FieldException;
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
        if (!$this->has($name)) {
            throw new FieldException("Undefined field `{$name}`");
        }

        return $this->fields[$name];
    }

    /**
     * @param string $name
     * @param Field  $field
     * @return FieldMap
     */
    public function set(string $name, Field $field): self
    {
        if ($this->has($name)) {
            throw new FieldException("Field `{$name}` already exists");
        }

        $this->fields[$name] = $field;

        return $this;
    }

    /**
     * Pack fields schema.
     *
     * @return array
     *
     * @throws BuilderException
     */
    public function packSchema(): array
    {
        $schema = [
            Schema::COLUMNS      => [],
            Schema::TYPECAST     => [],
            Schema::FIND_BY_KEYS => []
        ];

        foreach ($this->fields as $name => $field) {
            try {
                $schema[Schema::COLUMNS][$name] = $field->getColumn();

                if ($field->hasTypecast()) {
                    $schema[Schema::TYPECAST][$name] = $field->getTypecast();
                }

                if ($field->isReferenced()) {
                    $schema[Schema::FIND_BY_KEYS][] = $name;
                }

            } catch (FieldException $e) {
                throw new BuilderException(
                    "Unable to pack field `{$name}`",
                    $e->getCode(),
                    $e->getMessage()
                );
            }
        }

        return $schema;
    }
}