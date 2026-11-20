<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;

class Author implements AuthorInterface, ParentInterface
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('author');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'p_id',
            (new Field())->setType('primary')->setColumn('id')->setPrimary(true),
        );

        $entity->getFields()->set(
            'p_name',
            (new Field())->setType('string(32)')->setColumn('author_name'),
        );

        return $entity;
    }

    public static function defineWithoutPK(): Entity
    {
        $entity = self::define();

        $entity->getFields()->remove('p_id');

        return $entity;
    }

    public static function defineWithUser(): Entity
    {
        $entity = self::define();

        $entity->getFields()->set(
            'p_user_id',
            (new Field())->setType('int(11)')->setColumn('user_id'),
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
