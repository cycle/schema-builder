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

class HasOne extends AbstractSchema
{
    use FieldTrait;

    protected const RELATION_TYPE = Relation::HAS_ONE;

    protected const OPTION_SCHEMA = [
        // save with parent
        Relation::CASCADE   => true,

        // use outer entity constrain by default
        Relation::CONSTRAIN => true,

        // not nullable by default
        Relation::NULLABLE  => false,

        // link to parent entity primary key by default
        Relation::INNER_KEY => '{source:primaryKey}',

        // default field name for inner key
        Relation::OUTER_KEY => '{source:role}_{innerKey}',
    ];

    /**
     * @param Registry $registry
     */
    public function compute(Registry $registry)
    {
        parent::compute($registry);

        // generate external field
        $target = $registry->getEntity($this->target);

        if (!$this->hasField($target, Relation::OUTER_KEY)) {
            // create target outer field
            $this->createField(
                $target,
                $this->getField($registry->getEntity($this->source), Relation::INNER_KEY),
                $this->options->get(Relation::OUTER_KEY)
            );
        }
    }
}