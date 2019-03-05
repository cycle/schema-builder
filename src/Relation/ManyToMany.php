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
}