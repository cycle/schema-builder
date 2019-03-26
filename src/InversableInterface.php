<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

/**
 * Gives ability for the relation to be inverted.
 */
interface InversableInterface extends RelationInterface
{
    /**
     * Inverse relation options into given schema.
     *
     * @param RelationInterface $relation
     * @param string            $into Target relation name.
     * @return RelationInterface
     */
    public function inverseRelation(RelationInterface $relation, string $into): RelationInterface;
}