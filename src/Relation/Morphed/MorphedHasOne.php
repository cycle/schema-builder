<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Relation\Morphed;

use Cycle\ORM\Relation;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\RelationSchema;
use Cycle\Schema\Relation\Traits\FieldTrait;
use Cycle\Schema\Relation\Traits\MorphTrait;

class MorphedHasOne extends RelationSchema
{
    use FieldTrait, MorphTrait;

    // internal relation type
    protected const RELATION_TYPE = Relation::MORPHED_HAS_ONE;

    // relation schema options
    protected const RELATION_SCHEMA = [
        // save with parent
        Relation::CASCADE                => true,
        
        // nullable by default
        Relation::NULLABLE               => false,

        // default field name for inner key
        Relation::OUTER_KEY              => '{relation}_{source:primaryKey}',

        // link to parent entity primary key by default
        Relation::INNER_KEY              => '{source:primaryKey}',

        // link to parent entity primary key by default
        Relation::MORPH_KEY              => '{relation}_role',

        // rendering options
        RelationSchema::INDEX_CREATE     => true,
        RelationSchema::MORPH_KEY_LENGTH => 32
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
            $this->getField($source, Relation::INNER_KEY),
            $this->options->get(Relation::NULLABLE)
        );

        // create target outer field
        $this->ensureMorphField(
            $target,
            $this->options->get(Relation::MORPH_KEY),
            $this->options->get(RelationSchema::MORPH_KEY_LENGTH),
            $this->options->get(Relation::NULLABLE)
        );
    }

    /**
     * @param Registry $registry
     */
    public function render(Registry $registry)
    {
        $target = $registry->getEntity($this->target);

        $outerField = $this->getField($target, Relation::OUTER_KEY);
        $morphField = $this->getField($target, Relation::MORPH_KEY);

        $table = $registry->getTableSchema($target);

        if ($this->options->get(self::INDEX_CREATE)) {
            $table->index([$outerField->getColumn(), $morphField->getColumn()]);
        }
    }
}
