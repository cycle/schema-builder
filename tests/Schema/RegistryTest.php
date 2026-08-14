<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\Database\Schema\AbstractTable;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\LegacyDatabase;
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

    public function testDuplicateRoleShouldThrowAnException(): void
    {
        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage('Duplicate entity `user`');

        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('user')->setClass(Author::class);


        $r->register($e);
        $r->register($e2);
    }

    public function testGetEntity(): void
    {
        $r = new Registry($this->dbal);
        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $r->register($e);

        $this->assertSame($e, $r->getEntity('user'));
    }

    public function testGetEntityException(): void
    {
        $r = new Registry($this->dbal);

        $this->expectException(RegistryException::class);

        $r->getEntity('user');
    }

    public function testLinkTableException(): void
    {
        $r = new Registry($this->dbal);

        $this->expectException(RegistryException::class);

        $r->linkTable(new Entity(), 'default', 'table');
    }

    public function testHasTableException(): void
    {
        $r = new Registry($this->dbal);

        $this->expectException(RegistryException::class);

        $r->hasTable(new Entity());
    }

    public function testGetTableException(): void
    {
        $r = new Registry($this->dbal);

        $this->expectException(RegistryException::class);

        $r->getTable(new Entity());
    }

    public function testGetDatabaseException(): void
    {
        $r = new Registry($this->dbal);

        $this->expectException(RegistryException::class);

        $r->getDatabase(new Entity());
    }

    public function testGetTableSchemaException(): void
    {
        $r = new Registry($this->dbal);

        $this->expectException(RegistryException::class);

        $r->getTableSchema(new Entity());
    }

    public function testGetTableNotLinked(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e);

        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage('Entity `user` has no assigned table');

        $r->getTable($e);
    }

    public function testLinkTableDoesNotLoadSchema(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');

        $this->assertTrue($r->hasTable($e));
        $this->assertSame('default', $r->getDatabase($e));
        $this->assertSame('user', $r->getTable($e));
    }

    public function testEntitiesOnSameTableShareSchema(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'user');

        $this->assertSame($r->getTableSchema($e), $r->getTableSchema($e2));
    }

    public function testLateLinkedEntityReusesLoadedSchema(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');

        // triggers the bulk load of every linked table
        $schema = $r->getTableSchema($e);

        // an entity linked after the load (embedded relations do this) must reuse the instance
        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);
        $r->register($e2)->linkTable($e2, 'default', 'user');

        $this->assertSame($schema, $r->getTableSchema($e2));
    }

    public function testLinkTableWithoutGetSchemasSupport(): void
    {
        $r = new Registry(new LegacyDatabase($this->dbal->database('default')));

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'user');

        $this->assertInstanceOf(AbstractTable::class, $r->getTableSchema($e));
        $this->assertSame($r->getTableSchema($e), $r->getTableSchema($e2));
    }

    public function testLinkTableWithoutSchemaSupportThrowsAnException(): void
    {
        $r = new Registry(new LegacyDatabase($this->dbal->database('default'), bareTables: true));

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');

        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage('Unable to retrieve table schema.');

        $r->getTableSchema($e);
    }

    public function testRegisterChildNoEntity(): void
    {
        $e = new Entity();
        $e->setRole('parent');
        $e->setClass(Author::class);

        $e->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id'),
        );

        $r = new Registry($this->dbal);

        $c = new Entity();
        $c->setRole('parent');
        $c->setClass(User::class);

        $c->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id'),
        );

        $c->getFields()->set(
            'name',
            (new Field())->setType('string')->setColumn('name'),
        );

        $this->expectException(RegistryException::class);

        $r->registerChild($e, $c);
    }

    public function testRegisterChild(): void
    {
        $e = new Entity();
        $e->setRole('parent');
        $e->setClass(Author::class);

        $e->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id'),
        );

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'table');

        $c = new Entity();
        $c->setRole('child');
        $c->setClass(User::class);

        $c->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id'),
        );

        $c->getFields()->set(
            'name',
            (new Field())->setType('string')->setColumn('name'),
        );

        $r->registerChild($e, $c);
        $this->assertTrue($e->getFields()->has('name'));

        $schema = (new Compiler())->compile($r, []);
    }

    public function testRegisterChildWithoutMerge(): void
    {
        $parent = new Entity();
        $parent->setRole('parent');
        $parent->setClass(Author::class);

        $registry = new Registry($this->dbal);
        $registry->register($parent)->linkTable($parent, 'default', 'table');

        $child = new Entity();
        $child->setRole('child');
        $child->setClass(User::class);

        $registry->registerChildWithoutMerge($parent, $child);

        $this->assertSame([$child], $registry->getChildren($parent));
    }
}
