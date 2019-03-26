<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Relation;

use Cycle\ORM\Relation;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Traits\FieldTrait;
use Cycle\Schema\Relation\Traits\ForeignKeyTrait;

class ManyToMany extends RelationSchema
{
    use FieldTrait, ForeignKeyTrait;

    // internal relation type
    protected const RELATION_TYPE = Relation::MANY_TO_MANY;

    // relation schema options
    protected const RELATION_SCHEMA = [
        // save with parent
        Relation::CASCADE            => true,

        // use outer entity constrain by default
        Relation::CONSTRAIN          => true,

        // nullable by default
        Relation::NULLABLE           => true,

        // custom where condition
        Relation::WHERE              => [],

        // inner key of parent record will be used to fill "THOUGHT_INNER_KEY" in pivot table
        Relation::INNER_KEY          => '{source:primaryKey}',

        // we are going to use primary key of outer table to fill "THOUGHT_OUTER_KEY" in pivot table
        // this is technically "inner" key of outer record, we will name it "outer key" for simplicity
        Relation::OUTER_KEY          => '{target:primaryKey}',

        // thought entity role name
        Relation::THOUGH_ENTITY      => null,

        // name field where parent record inner key will be stored in pivot table, role + innerKey
        // by default
        Relation::THOUGH_INNER_KEY   => '{source:role}_{innerKey}',

        // name field where inner key of outer record (outer key) will be stored in pivot table,
        // role + outerKey by default
        Relation::THOUGH_OUTER_KEY   => '{target:role}_{outerKey}',

        // apply pivot constrain
        Relation::THOUGH_CONSTRAIN   => true,

        // custom pivot where
        Relation::THOUGH_WHERE       => [],

        // rendering options
        RelationSchema::INDEX_CREATE => true,
        RelationSchema::FK_CREATE    => true,
        RelationSchema::FK_ACTION    => 'CASCADE'
    ];

    /**
     * @param Registry $registry
     */
    public function compute(Registry $registry)
    {
        parent::compute($registry);

        $source = $registry->getEntity($this->source);
        $target = $registry->getEntity($this->target);

        $thought = $registry->getEntity($this->options->get(Relation::THOUGH_ENTITY));

        if ($registry->getDatabase($source) !== $registry->getDatabase($target)) {
            throw new RelationException(sprintf(
                "Relation ManyToMany can only link entities from same database (%s, %s)",
                $source->getRole(),
                $target->getRole()
            ));
        }

        if ($registry->getDatabase($source) !== $registry->getDatabase($thought)) {
            throw new RelationException(sprintf(
                "Relation ManyToMany can only link entities from same database (%s, %s)",
                $source->getRole(),
                $thought->getRole()
            ));
        }

        $this->ensureField(
            $thought,
            $this->options->get(Relation::THOUGH_INNER_KEY),
            $this->getField($source, Relation::INNER_KEY),
            $this->options->get(Relation::NULLABLE)
        );

        $this->ensureField(
            $thought,
            $this->options->get(Relation::THOUGH_OUTER_KEY),
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

        $thought = $registry->getEntity($this->options->get(Relation::THOUGH_ENTITY));

        $sourceField = $this->getField($source, Relation::INNER_KEY);
        $targetField = $this->getField($target, Relation::OUTER_KEY);

        $thoughtSourceField = $this->getField($thought, Relation::THOUGH_INNER_KEY);
        $thoughtTargetField = $this->getField($thought, Relation::THOUGH_OUTER_KEY);

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