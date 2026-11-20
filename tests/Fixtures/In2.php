<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;

class In2 implements ParentInterface
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('in2');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('uuid')->setPrimary(true),
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
}
