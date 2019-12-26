<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation;

class MorphedTo
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('morphed');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')->setPrimary(true)
        );

        $entity->getRelations()->set(
            'parent',
            (new Relation())->setTarget(ParentInterface::class)->setType('belongsToMorphed')
        );

        return $entity;
    }
}
