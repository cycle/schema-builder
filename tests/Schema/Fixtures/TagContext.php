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

class TagContext
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('tagContext');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')->setPrimary(true)
        );

        return $entity;
    }
}