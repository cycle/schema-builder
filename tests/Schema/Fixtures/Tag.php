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

class Tag
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('tag');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'id',
            (new Field())->setType('bigPrimary')->setColumn('id')->setPrimary(true)
        );

        $entity->getFields()->set(
            'name',
            (new Field())->setType('string(32)')->setColumn('name')
        );

        return $entity;
    }
}