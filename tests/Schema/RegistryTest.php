<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\User;

abstract class RegistryTest extends BaseTest
{
    public function testHasRole()
    {
        $r = new Registry($this->dbal);
        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $r->register($e);

        $this->assertTrue($r->hasEntity($e));
        $this->assertTrue($r->hasRole('user'));
        $this->assertTrue($r->hasRole(User::class));

        $this->assertFalse($r->hasRole('post'));
        $this->assertFalse($r->hasRole(Post::class));
    }

    public function testGetEntity()
    {
        $r = new Registry($this->dbal);
        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $r->register($e);

        $this->assertSame($e, $r->getEntity('user'));
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testGetEntityException()
    {
        $r = new Registry($this->dbal);

        $r->getEntity('user');
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testLinkTableException()
    {
        $r = new Registry($this->dbal);

        $r->linkTable(new Entity(), 'default', 'table');
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testHasTableException()
    {
        $r = new Registry($this->dbal);

        $r->hasTable(new Entity());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testGetTableException()
    {
        $r = new Registry($this->dbal);

        $r->getTable(new Entity());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testGetDatabaseException()
    {
        $r = new Registry($this->dbal);

        $r->getDatabase(new Entity());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testGetTableSchemaException()
    {
        $r = new Registry($this->dbal);

        $r->getTableSchema(new Entity());
    }
}