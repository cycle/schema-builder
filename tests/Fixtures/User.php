<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation;

class User implements AuthorInterface
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('user');
        $entity->setClass(self::class);
        $entity->setTypecast([Typecaster::class]);

        $entity->getFields()->set(
            'p_id',
            (new Field())->setType('primary')->setColumn('id')->setPrimary(true),
        );

        $entity->getFields()->set(
            'p_name',
            (new Field())->setType('string(32)')->setColumn('user_name'),
        );

        $entity->getFields()->set(
            'p_active',
            (new Field())->setType('bool')->setColumn('active'),
        );

        $entity->getFields()->set(
            'p_balance',
            (new Field())->setType('float')->setColumn('balance'),
        );


        $entity->getFields()->set(
            'p_created_at',
            (new Field())->setType('datetime')->setColumn('created_at'),
        );

        $entity->getRelations()->set(
            'plain',
            (new Relation())->setTarget('plain')->setType('hasOne'),
        );

        return $entity;
    }

    public static function defineCompositePK(): Entity
    {
        $entity = self::define();

        $entity->getFields()->set(
            'p_slug',
            (new Field())->setType('string')->setColumn('slug')->setPrimary(true),
        );

        return $entity;
    }

    public static function defineWithoutPK(): Entity
    {
        $entity = self::define();

        $entity->getFields()->remove('p_id');

        $entity->getFields()->set(
            'p_id',
            (new Field())->setColumn('id'),
        );

        return $entity;
    }
}
