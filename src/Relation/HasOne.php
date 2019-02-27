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

class HasOne extends RelationSchema
{
    use FieldTrait;

    protected const RELATION_TYPE = Relation::HAS_ONE;

    protected const OPTION_SCHEMA = [
        // save with parent
        Relation::CASCADE            => true,

        // use outer entity constrain by default
        Relation::CONSTRAIN          => true,

        // not nullable by default
        Relation::NULLABLE           => false,

        // link to parent entity primary key by default
        Relation::INNER_KEY          => '{source:primaryKey}',

        // default field name for inner key
        Relation::OUTER_KEY          => '{source:role}_{innerKey}',

        // rendering options
        RelationSchema::FK_CREATE    => true,
        RelationSchema::FK_ACTION    => 'CASCADE',
        RelationSchema::INDEX_CREATE => true,
    ];

    /**
     * @param Registry $registry
     */
    public function compute(Registry $registry)
    {
        parent::compute($registry);

        $source = $registry->getEntity($this->source);
        $target = $registry->getEntity($this->target);

        // create target outer field
        $this->ensureField(
            $target,
            $this->options->get(Relation::OUTER_KEY),
            $this->getField($source, Relation::INNER_KEY)
        );
    }

    /**
     * @param Registry $registry
     */
    public function render(Registry $registry)
    {
        $source = $registry->getEntity($this->source);
        $target = $registry->getEntity($this->target);

        $innerField = $this->getField($source, Relation::INNER_KEY);
        $outerField = $this->getField($target, Relation::OUTER_KEY);

        $table = $registry->getTableSchema($target);

        if ($this->options->get(self::INDEX_CREATE)) {
            $table->index([$outerField->getColumn()]);
        }

        if ($this->options->get(self::FK_CREATE)) {
            $fk = $table->foreignKey($outerField->getColumn());

            // todo: what if database is different?

            $fk->references($registry->getTable($source), $innerField->getColumn());

            $fk->onUpdate($this->options->get(self::FK_ACTION));
            $fk->onDelete($this->options->get(self::FK_ACTION));
        }
    }
}