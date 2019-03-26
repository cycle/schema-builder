<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Relation\Morphed;

use Cycle\ORM\Relation;
use Cycle\Schema\InvertibleInterface;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\RelationSchema;
use Cycle\Schema\Relation\Traits\FieldTrait;
use Cycle\Schema\Relation\Traits\MorphTrait;

class BelongsToMorphed extends RelationSchema implements InvertibleInterface
{
    use FieldTrait, MorphTrait;

    // internal relation type
    protected const RELATION_TYPE = Relation::BELONGS_TO_MORPHED;

    // relation schema options
    protected const RELATION_SCHEMA = [
        // nullable by default
        Relation::NULLABLE               => true,

        // default field name for inner key
        Relation::OUTER_KEY              => '{target:primaryKey}',

        // link to parent entity primary key by default
        Relation::INNER_KEY              => '{relation}_{outerKey}',

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
        // compute local key
        $this->options = $this->options->withContext([
            'source:primaryKey' => $this->getPrimary($registry->getEntity($this->source))
        ]);

        $source = $registry->getEntity($this->source);

        list($outerKey, $outerField) = $this->findOuterKey($registry, $this->target);

        // register primary key reference
        $this->options = $this->options->withContext(['target:primaryKey' => $outerKey]);

        // create target outer field
        $this->ensureField(
            $source,
            $this->options->get(Relation::INNER_KEY),
            $outerField,
            $this->options->get(Relation::NULLABLE)
        );

        $this->ensureMorphField(
            $source,
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
        $source = $registry->getEntity($this->source);

        $innerField = $this->getField($source, Relation::INNER_KEY);
        $morphField = $this->getField($source, Relation::MORPH_KEY);

        $table = $registry->getTableSchema($source);

        if ($this->options->get(self::INDEX_CREATE)) {
            $table->index([$innerField->getColumn(), $morphField->getColumn()]);
        }
    }
}