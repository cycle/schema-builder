<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    public function testRole()
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());
    }

    public function testFields()
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set("id", new Field());

        $this->assertTrue($e->getFields()->has("id"));
        $e->getFields()->remove("id");
        $this->assertFalse($e->getFields()->has("id"));
    }

    public function testSetRelation()
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());

        $e->getRelations()->set("test", new Relation());

        $this->assertTrue($e->getRelations()->has("test"));
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RelationException
     */
    public function testGetUndefined()
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());

        $e->getRelations()->has("test");
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RelationException
     */
    public function testSetRelationDouble()
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());

        $e->getRelations()->set("test", new Relation());
        $e->getRelations()->set("test", new Relation());
    }
}