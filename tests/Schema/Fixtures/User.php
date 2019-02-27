<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation;

class User
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('user');
        $entity->setClass(self::class);;

        $entity->getFields()->set(
            'id', (new Field())->setType('primary')->setColumn('id')->setPrimary(true)
        );

        $entity->getFields()->set(
            'name',
            (new Field())->setType('string(32)')->setColumn('user_name')
        );

        $entity->getRelations()->set(
            'plain',
            (new Relation())->setTarget('plain')->setType('hasOne')
        );

        return $entity;
    }
}