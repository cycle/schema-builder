<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

use Cycle\ORM\Schema;
use Cycle\Schema\Definition\Entity;

final class Compiler implements ProcessorInterface
{
    /** @var array */
    private $result = [];

    /**
     * Get compiled schema result.
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Compile entity and relation definitions into packed ORM schema.
     *
     * @param Registry $registry
     * @param Entity   $entity
     */
    public function compute(Registry $registry, Entity $entity)
    {
        $item = [
            Schema::ENTITY     => $entity->getClass(),
            Schema::SOURCE     => $entity->getSource(),
            Schema::MAPPER     => $entity->getMapper(),
            Schema::REPOSITORY => $entity->getRepository(),
            Schema::CONSTRAIN  => $entity->getConstrain(),
        ];

        // todo: table
        // todo: primary key
        // todo: find by keys
        // todo: relations
        // todo: columns, typecast

        // register all children
        foreach ($registry->getChildren($entity) as $child) {
            // aliasing
            $this->result[$child->getClass()] = [Schema::ROLE => $entity->getRole()];

            $item[Schema::CHILDREN][] = $child->getClass();
        }

        $this->result[$entity->getRole()] = $item;
    }
}