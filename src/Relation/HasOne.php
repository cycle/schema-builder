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

class HasOne extends AbstractSchema
{
    /**
     * {@inheritdoc}
     */
    protected const OPTION_SCHEMA = [
        // save with parent
        Relation::CASCADE   => true,

        // not nullable by default
        Relation::NULLABLE  => false,

        // use outer entity constrain by default
        Relation::CONSTRAIN => true,

        // link to parent entity primary key by default
        Relation::INNER_KEY => '{source:primaryKey}',

        // default field name for inner key
        Relation::OUTER_KEY => '{source:role}_{innerKey}',
    ];
}