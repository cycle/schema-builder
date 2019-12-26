<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\User;

abstract class RegistryTest extends BaseTest
{
    public function testHasRole(): void
    {
        $r = new Registry($this->dbal);
        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $r->register($e);

        $this->assertTrue($r->hasEntity('user'));
        $this->assertTrue($r->hasEntity(User::class));

        $this->assertFalse($r->hasEntity('post'));
        $this->assertFalse($r->hasEntity(Post::class));
    }

    public function testGetEntity(): void
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
    public function testGetEntityException(): void
    {
        $r = new Registry($this->dbal);

        $r->getEntity('user');
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testLinkTableException(): void
    {
        $r = new Registry($this->dbal);

        $r->linkTable(new Entity(), 'default', 'table');
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testHasTableException(): void
    {
        $r = new Registry($this->dbal);

        $r->hasTable(new Entity());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testGetTableException(): void
    {
        $r = new Registry($this->dbal);

        $r->getTable(new Entity());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testGetDatabaseException(): void
    {
        $r = new Registry($this->dbal);

        $r->getDatabase(new Entity());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testGetTableSchemaException(): void
    {
        $r = new Registry($this->dbal);

        $r->getTableSchema(new Entity());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RegistryException
     */
    public function testRegisterChildNoEntity(): void
    {
        $e = new Entity();
        $e->setRole('parent');
        $e->setClass(Author::class);

        $e->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')
        );

        $r = new Registry($this->dbal);

        $c = new Entity();
        $c->setRole('parent');
        $c->setClass(User::class);

        $c->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')
        );

        $c->getFields()->set(
            'name',
            (new Field())->setType('string')->setColumn('name')
        );

        $r->registerChild($e, $c);
    }

    public function testRegisterChild(): void
    {
        $e = new Entity();
        $e->setRole('parent');
        $e->setClass(Author::class);

        $e->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')
        );

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'table');

        $c = new Entity();
        $c->setRole('parent');
        $c->setClass(User::class);

        $c->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id')
        );

        $c->getFields()->set(
            'name',
            (new Field())->setType('string')->setColumn('name')
        );

        $r->registerChild($e, $c);
        $this->assertTrue($e->getFields()->has('name'));

        $schema = (new Compiler())->compile($r, []);

        $this->assertSame('parent', $schema[User::class][Schema::ROLE]);
    }
}
