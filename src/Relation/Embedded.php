<?php

declare(strict_types=1);

namespace Cycle\Schema\Relation;

use Cycle\ORM\Relation;
use Cycle\Schema\Exception\FieldException\EmbeddedPrimaryKeyException;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Traits\ForeignKeyTrait;

final class Embedded extends RelationSchema
{
    use ForeignKeyTrait;

    // internal relation type
    protected const RELATION_TYPE = Relation::EMBEDDED;

    // relation schema options
    protected const RELATION_SCHEMA = [
        Relation::LOAD => Relation::LOAD_EAGER,
        self::EMBEDDED_PREFIX => '',
    ];

    /**
     * @param Registry $registry
     */
    public function compute(Registry $registry): void
    {
        $source = $registry->getEntity($this->source);
        $target = $registry->getEntity($this->target);

        $targetRole = $target->getRole();
        $sourceRole = $source->getRole();
        \assert($targetRole !== null && $sourceRole !== null);

        // each embedded entity must isolated
        $target = clone $target;
        $target->setRole($sourceRole . ':' . $targetRole . ':' . $this->name);
        $targetRole = $target->getRole();
        \assert($targetRole !== null);

        // embedded entity must point to the same table as parent entity
        $registry->register($target);
        $registry->linkTable($target, $registry->getDatabase($source), $registry->getTable($source));

        // isolated
        $this->target = $targetRole;

        $prefix = $this->getOptions()->get(self::EMBEDDED_PREFIX);
        assert(\is_string($prefix));
        foreach ($target->getFields() as $field) {
            /** @var non-empty-string $columnName */
            $columnName = $prefix . $field->getColumn();
            $field->setColumn($columnName);
        }

        foreach ($source->getFields() as $name => $field) {
            if ($field->isPrimary()) {
                // sync primary keys
                if ($target->getFields()->has($name)) {
                    throw new EmbeddedPrimaryKeyException($target, $name);
                }
                $target->getFields()->set($name, $field);
            }
        }

        parent::compute($registry);
    }

    /**
     * @param Registry $registry
     */
    public function render(Registry $registry): void
    {
        // relation does not require any column rendering besides actual tables
    }
}
