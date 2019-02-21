<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;

class Dummy
{
    public static function makeEntity(): Entity
    {
        $entity = new Entity();
        $entity->setRole('dummy');

        $id = new Field();
        $id->setType('primary')->setColumn('id');

        $entity->getFields()->set('id', $id);

        return $entity;
    }
}