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

class Author implements AuthorInterface, ParentInterface
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('author');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')->setPrimary(true)
        );

        $entity->getFields()->set(
            'name',
            (new Field())->setType('string(32)')->setColumn('author_name')
        );

        return $entity;
    }
}
