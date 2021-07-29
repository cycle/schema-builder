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

class User implements AuthorInterface
{
    public static function defineCompositePK(): Entity
    {
        $entity = self::define();

        $entity->getFields()->set(
            'slug',
            (new Field())->setType('primary')->setColumn('slug')->setPrimary(true)
        );

        return $entity;
    }

    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('user');
        $entity->setClass(self::class);
        ;

        $entity->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')->setPrimary(true)
        );

        $entity->getFields()->set(
            'name',
            (new Field())->setType('string(32)')->setColumn('user_name')
        );

        $entity->getFields()->set(
            'active',
            (new Field())->setType('bool')->setColumn('active')
        );

        $entity->getFields()->set(
            'balance',
            (new Field())->setType('float')->setColumn('balance')
        );


        $entity->getFields()->set(
            'created_at',
            (new Field())->setType('datetime')->setColumn('created_at')
        );

        $entity->getRelations()->set(
            'plain',
            (new Relation())->setTarget('plain')->setType('hasOne')
        );

        return $entity;
    }
}
