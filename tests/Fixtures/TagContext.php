<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;

class TagContext
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('tagContext');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'p_id',
            (new Field())->setType('bigPrimary')->setColumn('id')->setPrimary(true),
        );

        return $entity;
    }
}
