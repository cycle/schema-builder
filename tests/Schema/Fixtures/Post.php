<?php declare(strict_types=1);
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

class Post
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('post');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')->setPrimary(true)
        );

        $entity->getRelations()->set(
            'author',
            (new Relation())->setTarget(Author::class)->setType('belongsTo')
        );

        return $entity;
    }
}