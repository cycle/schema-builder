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
use Cycle\Schema\Tests\Fixtures\SpyDatabase;
use Cycle\Schema\Tests\Fixtures\User;

abstract class RegistryTest extends BaseTest
{
    #[\Testo\Test]
    public function testHasRole(): void
    {
        $r = new Registry($this->dbal);
        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $r->register($e);

        \Testo\Assert::true($r->hasEntity('user'));
        \Testo\Assert::true($r->hasEntity(User::class));

        \Testo\Assert::false($r->hasEntity('post'));
        \Testo\Assert::false($r->hasEntity(Post::class));
    }

    #[\Testo\Test]
    public function testDuplicateRoleShouldThrowAnException(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Duplicate entity `user`');

        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('user')->setClass(Author::class);


        $r->register($e);
        $r->register($e2);
    }

    #[\Testo\Test]
    public function testGetEntity(): void
    {
        $r = new Registry($this->dbal);
        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $r->register($e);

        \Testo\Assert::same($r->getEntity('user'), $e);
    }

    #[\Testo\Test]
    public function testGetEntityException(): void
    {
        $r = new Registry($this->dbal);

        \Testo\Expect::exception(RegistryException::class);

        $r->getEntity('user');
    }

    #[\Testo\Test]
    public function testLinkTableException(): void
    {
        $r = new Registry($this->dbal);

        \Testo\Expect::exception(RegistryException::class);

        $r->linkTable(new Entity(), 'default', 'table');
    }

    #[\Testo\Test]
    public function testHasTableException(): void
    {
        $r = new Registry($this->dbal);

        \Testo\Expect::exception(RegistryException::class);

        $r->hasTable(new Entity());
    }

    #[\Testo\Test]
    public function testGetTableException(): void
    {
        $r = new Registry($this->dbal);

        \Testo\Expect::exception(RegistryException::class);

        $r->getTable(new Entity());
    }

    #[\Testo\Test]
    public function testGetDatabaseException(): void
    {
        $r = new Registry($this->dbal);

        \Testo\Expect::exception(RegistryException::class);

        $r->getDatabase(new Entity());
    }

    #[\Testo\Test]
    public function testGetTableSchemaException(): void
    {
        $r = new Registry($this->dbal);

        \Testo\Expect::exception(RegistryException::class);

        $r->getTableSchema(new Entity());
    }

    #[\Testo\Test]
    public function testGetTableNotLinked(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e);

        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Entity `user` has no assigned table');

        $r->getTable($e);
    }

    #[\Testo\Test]
    public function testLinkTableDoesNotLoadSchema(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');

        \Testo\Assert::true($r->hasTable($e));
        \Testo\Assert::same($r->getDatabase($e), 'default');
        \Testo\Assert::same($r->getTable($e), 'user');
    }

    #[\Testo\Test]
    public function testEntitiesOnSameTableShareSchema(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'user');

        \Testo\Assert::same($r->getTableSchema($e2), $r->getTableSchema($e));
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($r->getTableSchema($e2), $schema);
    }

    #[\Testo\Test]
    public function testLinkTableDefersIntrospectionUntilFirstSchemaRequest(): void
    {
        $spy = new SpyDatabase($this->dbal->database('default'));
        $r = new Registry($spy);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('post')->setClass(Post::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'post');

        // linking alone must not introspect anything
        \Testo\Assert::same($spy->getSchemasCalls, []);

        // the first schema request loads every pending table in a single batched call
        $r->getTableSchema($e);
        \Testo\Assert::count($spy->getSchemasCalls, 1);
        \Testo\Assert::array($spy->getSchemasCalls[0])->sameElementsAs(['user', 'post']);

        // schemas for the other tables are already resolved, no further introspection
        $r->getTableSchema($e2);
        \Testo\Assert::count($spy->getSchemasCalls, 1);
    }

    #[\Testo\Test]
    public function testLateLinkedTableTriggersOnlyOneAdditionalBatch(): void
    {
        $spy = new SpyDatabase($this->dbal->database('default'));
        $r = new Registry($spy);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');
        $r->getTableSchema($e);

        \Testo\Assert::same($spy->getSchemasCalls, [['user']]);

        // a table linked after the first load must be introspected on its own, and only it
        $e2 = new Entity();
        $e2->setRole('post')->setClass(Post::class);
        $r->register($e2)->linkTable($e2, 'default', 'post');
        $r->getTableSchema($e2);

        \Testo\Assert::same($spy->getSchemasCalls, [['user'], ['post']]);
    }

    #[\Testo\Test]
    public function testBulkLoadResolvesDistinctTablesIndependently(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'post');

        $userSchema = $r->getTableSchema($e);
        $postSchema = $r->getTableSchema($e2);

        \Testo\Assert::notSame($postSchema, $userSchema);
        \Testo\Assert::same($userSchema->getName(), 'user');
        \Testo\Assert::same($postSchema->getName(), 'post');
    }

    #[\Testo\Test]
    public function testBulkLoadAcrossMultipleDatabases(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'secondary', 'user');

        $defaultSchema = $r->getTableSchema($e);
        $secondarySchema = $r->getTableSchema($e2);

        \Testo\Assert::notSame($secondarySchema, $defaultSchema);
        \Testo\Assert::same($r->getDatabase($e), 'default');
        \Testo\Assert::same($r->getDatabase($e2), 'secondary');
    }

    #[\Testo\Test]
    public function testBulkLoadDoesNotReloadAlreadyLoadedTable(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');

        // triggers the first bulk load for 'user'
        $userSchema = $r->getTableSchema($e);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);
        $r->register($e2)->linkTable($e2, 'default', 'post');

        // triggers a second bulk load, only 'post' is pending this time
        $postSchema = $r->getTableSchema($e2);

        \Testo\Assert::notSame($postSchema, $userSchema);
        // the already loaded 'user' schema must not have been replaced/reloaded
        \Testo\Assert::same($r->getTableSchema($e), $userSchema);
    }

    #[\Testo\Test]
    public function testLinkTableWithoutGetSchemasSupport(): void
    {
        $r = new Registry(new LegacyDatabase($this->dbal->database('default')));

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'user');

        \Testo\Assert::instanceOf($r->getTableSchema($e), AbstractTable::class);
        \Testo\Assert::same($r->getTableSchema($e2), $r->getTableSchema($e));
    }

    #[\Testo\Test]
    public function testLinkTableWithoutGetSchemasSupportResolvesDistinctTables(): void
    {
        $r = new Registry(new LegacyDatabase($this->dbal->database('default')));

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'post');

        $userSchema = $r->getTableSchema($e);
        $postSchema = $r->getTableSchema($e2);

        \Testo\Assert::notSame($postSchema, $userSchema);
        \Testo\Assert::same($userSchema->getName(), 'user');
        \Testo\Assert::same($postSchema->getName(), 'post');
    }

    #[\Testo\Test]
    public function testLinkTableWithoutSchemaSupportThrowsAnException(): void
    {
        $r = new Registry(new LegacyDatabase($this->dbal->database('default'), bareTables: true));

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');

        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Unable to retrieve table schema.');

        $r->getTableSchema($e);
    }

    #[\Testo\Test]
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

        \Testo\Expect::exception(RegistryException::class);

        $r->registerChild($e, $c);
    }

    #[\Testo\Test]
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
        \Testo\Assert::true($e->getFields()->has('name'));

        $schema = (new Compiler())->compile($r, []);
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($registry->getChildren($parent), [$child]);
    }
}
