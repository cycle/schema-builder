<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;

class EmbeddedEntity
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('embedded');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'p_embedded',
            (new Field())->setType('string')->setColumn('embedded_column'),
        );

        return $entity;
    }
}
