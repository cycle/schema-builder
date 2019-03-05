<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Relation;

use Cycle\ORM\Relation;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Traits\FieldTrait;
use Cycle\Schema\Relation\Traits\ForeignKeyTrait;

class ManyToMany extends RelationSchema
{
    use FieldTrait, ForeignKeyTrait;

    // internal relation type
    protected const RELATION_TYPE = Relation::HAS_ONE;

    // relation schema options
    protected const RELATION_SCHEMA = [
        // save with parent
        Relation::CASCADE              => true,

        // use outer entity constrain by default
        Relation::CONSTRAIN            => true,

        // nullable by default
        Relation::NULLABLE             => true,

        // custom where condition
        Relation::WHERE                => [],

        // inner key of parent record will be used to fill "THOUGHT_INNER_KEY" in pivot table
        Relation::INNER_KEY            => '{source:primaryKey}',

        // we are going to use primary key of outer table to fill "THOUGHT_OUTER_KEY" in pivot table
        // this is technically "inner" key of outer record, we will name it "outer key" for simplicity
        Relation::OUTER_KEY            => '{target:primaryKey}',

        // name field where parent record inner key will be stored in pivot table, role + innerKey
        // by default
        Relation::THOUGHT_INNER_KEY    => '{source:role}_{innerKey}',

        // name field where inner key of outer record (outer key) will be stored in pivot table,
        // role + outerKey by default
        Relation::THOUGHT_OUTER_KEY    => '{target:role}_{outerKey}',

        // apply pivot constrain
        Relation::THOUGHT_CONSTRAIN    => true,

        // custom pivot where
        Relation::THOUGHT_WHERE        => [],

        // rendering options
        RelationSchema::INDEX_CREATE   => true,
        RelationSchema::FK_CREATE      => true,
        RelationSchema::FK_ACTION      => 'CASCADE',
        RelationSchema::BIND_INTERFACE => false
    ];

    /**
     * @param Registry $registry
     */
    public function compute(Registry $registry)
    {
        parent::compute($registry);

        $source = $registry->getEntity($this->source);
        $target = $registry->getEntity($this->target);

        $thought = $registry->getEntity($this->options->get(Relation::THOUGHT_ENTITY));

        $this->ensureField(
            $thought,
            $this->options->get(Relation::THOUGHT_INNER_KEY),
            $this->getField($source, Relation::INNER_KEY),
            $this->options->get(Relation::NULLABLE)
        );

        $this->ensureField(
            $thought,
            $this->options->get(Relation::THOUGHT_OUTER_KEY),
            $this->getField($target, Relation::OUTER_KEY),
            $this->options->get(Relation::NULLABLE)
        );
    }

    /**
     * @param Registry $registry
     */
    public function render(Registry $registry)
    {
        $source = $registry->getEntity($this->source);
        $target = $registry->getEntity($this->target);

        $thought = $registry->getEntity($this->options->get(Relation::THOUGHT_ENTITY));

        $sourceField = $this->getField($source, Relation::INNER_KEY);
        $targetField = $this->getField($target, Relation::OUTER_KEY);

        $thoughtSourceField = $this->getField($thought, Relation::THOUGHT_INNER_KEY);
        $thoughtTargetField = $this->getField($thought, Relation::THOUGHT_OUTER_KEY);

        $table = $registry->getTableSchema($thought);

        if ($this->options->get(self::INDEX_CREATE)) {
            $table->index([
                $thoughtSourceField->getColumn(),
                $thoughtTargetField->getColumn()
            ])->unique(true);
        }

        if ($this->options->get(self::FK_CREATE)) {
            $this->createForeignKey($registry, $source, $thought, $sourceField, $thoughtSourceField);
            $this->createForeignKey($registry, $target, $thought, $targetField, $thoughtTargetField);
        }
    }
}