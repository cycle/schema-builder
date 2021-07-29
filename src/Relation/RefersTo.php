<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Relation;

use Cycle\ORM\Relation;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Traits\FieldTrait;
use Cycle\Schema\Relation\Traits\ForeignKeyTrait;

/**
 * Similar to BelongsTo relation but does not force external object to always exists.
 */
final class RefersTo extends RelationSchema
{
    use FieldTrait;
    use ForeignKeyTrait;

    // internal relation type
    protected const RELATION_TYPE = Relation::REFERS_TO;

    // relation schema options
    protected const RELATION_SCHEMA = [
        // save with parent
        Relation::CASCADE            => true,

        // do not pre-load relation by default
        Relation::LOAD               => Relation::LOAD_PROMISE,

        // nullable by default
        Relation::NULLABLE           => true,

        // link to parent entity primary key by default
        Relation::INNER_KEY          => '{relation}_{outerKey}',

        // default field name for inner key
        Relation::OUTER_KEY          => '{target:primaryKey}',

        // rendering options
        RelationSchema::INDEX_CREATE => true,
        RelationSchema::FK_CREATE    => true,
        RelationSchema::FK_ACTION    => 'SET NULL'
    ];

    /**
     * @param Registry $registry
     */
    public function compute(Registry $registry): void
    {
        parent::compute($registry);

        $source = $registry->getEntity($this->source);
        $target = $registry->getEntity($this->target);

        // create target outer field
        foreach ($this->getFields($target, Relation::OUTER_KEY) as $field) {
            foreach ((array)$this->options->get(Relation::INNER_KEY) as $innerField) {
                $this->ensureField(
                    $source,
                    $innerField,
                    $field,
                    $this->options->get(Relation::NULLABLE)
                );
            }
        }
    }

    /**
     * @param Registry $registry
     */
    public function render(Registry $registry): void
    {
        $source = $registry->getEntity($this->source);
        $target = $registry->getEntity($this->target);

        $innerFields = $this->getFields($source, Relation::INNER_KEY);
        $outerFields = $this->getFields($target, Relation::OUTER_KEY);

        $table = $registry->getTableSchema($source);

        if ($this->options->get(self::INDEX_CREATE)) {
            $table->index(array_map(function (Field $field) {
                return $field->getColumn();
            }, $innerFields));
        }

        if ($this->options->get(self::FK_CREATE)) {
            $this->createForeignCompositeKey($registry, $target, $source, $outerFields, $innerFields);
        }
    }
}
