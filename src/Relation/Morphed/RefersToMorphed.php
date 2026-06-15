<?php

declare(strict_types=1);

namespace Cycle\Schema\Relation\Morphed;

use Cycle\ORM\Relation;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\RelationSchema;
use Cycle\Schema\Relation\Traits\FieldTrait;
use Cycle\Schema\Relation\Traits\MorphTrait;

/**
 * Morphed variation of the RefersTo relation. It stores the outer key and the target role (morph
 * key) on the source entity exactly like {@see BelongsToMorphed}, but the ORM resolves the outer
 * key in a deferred way, which allows self-linked and cyclic morphed references.
 */
final class RefersToMorphed extends RelationSchema
{
    use FieldTrait;
    use MorphTrait;

    // internal relation type
    protected const RELATION_TYPE = Relation::REFERS_TO_MORPHED;

    // relation schema options
    protected const RELATION_SCHEMA = [
        // save with parent
        Relation::CASCADE => true,

        // do not pre-load relation by default
        Relation::LOAD => Relation::LOAD_PROMISE,

        // nullable by default
        Relation::NULLABLE => true,

        // default field name for inner key
        Relation::OUTER_KEY => '{target:primaryKey}',

        // link to parent entity primary key by default
        Relation::INNER_KEY => '{relation}_{outerKey}',

        // store the related entity role
        Relation::MORPH_KEY => '{relation}_role',

        // rendering options
        RelationSchema::INDEX_CREATE => true,
        RelationSchema::MORPH_KEY_LENGTH => 32,
    ];

    public function compute(Registry $registry): void
    {
        // compute local key
        $this->options = $this->options->withContext([
            'source:primaryKey' => $this->getPrimaryColumns($registry->getEntity($this->source)),
        ]);

        $source = $registry->getEntity($this->source);

        [$outerKeys, $outerFields] = $this->findOuterKey($registry, $this->target);

        // register primary key reference
        $this->options = $this->options->withContext([
            'target:primaryKey' => $outerKeys,
        ]);

        $outerKeys = array_combine($outerKeys, (array) $this->options->get(Relation::INNER_KEY));

        // create source inner field
        foreach ($outerKeys as $key => $innerKey) {
            $outerField = $outerFields->get($key);

            $this->ensureField(
                $source,
                $innerKey,
                $outerField,
                $this->options->get(Relation::NULLABLE),
            );
        }

        foreach ((array) $this->options->get(Relation::MORPH_KEY) as $key) {
            $this->ensureMorphField(
                $source,
                $key,
                $this->options->get(RelationSchema::MORPH_KEY_LENGTH),
                $this->options->get(Relation::NULLABLE),
            );
        }
    }

    public function render(Registry $registry): void
    {
        $source = $registry->getEntity($this->source);
        $innerFields = $this->getFields($source, Relation::INNER_KEY);
        $morphFields = $this->getFields($source, Relation::MORPH_KEY);

        $this->mergeIndex($registry, $source, $innerFields, $morphFields);
    }
}
