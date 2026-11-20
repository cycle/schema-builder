<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Schema\Compiler;
use Cycle\Schema\Defaults;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\LegacyDatabase;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\SpyDatabase;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class RegistryTest extends BaseTest
{
    #[Test]
    public function testHasRole(): void
    {
        $r = new Registry($this->dbal);
        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $r->register($e);

        Assert::true($r->hasEntity('user'));
        Assert::true($r->hasEntity(User::class));

        Assert::false($r->hasEntity('post'));
        Assert::false($r->hasEntity(Post::class));
    }

    #[Test]
    public function testDuplicateRoleShouldThrowAnException(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Duplicate entity `user`');

        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('user')->setClass(Author::class);


        $r->register($e);
        $r->register($e2);
    }

    #[Test]
    public function testGetEntity(): void
    {
        $r = new Registry($this->dbal);
        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $r->register($e);

        Assert::same($r->getEntity('user'), $e);
    }

    #[Test]
    public function testGetEntityException(): void
    {
        $r = new Registry($this->dbal);

        Expect::exception(RegistryException::class);

        $r->getEntity('user');
    }

    #[Test]
    public function testLinkTableException(): void
    {
        $r = new Registry($this->dbal);

        Expect::exception(RegistryException::class);

        $r->linkTable(new Entity(), 'default', 'table');
    }

    #[Test]
    public function testHasTableException(): void
    {
        $r = new Registry($this->dbal);

        Expect::exception(RegistryException::class);

        $r->hasTable(new Entity());
    }

    #[Test]
    public function testGetTableException(): void
    {
        $r = new Registry($this->dbal);

        Expect::exception(RegistryException::class);

        $r->getTable(new Entity());
    }

    #[Test]
    public function testGetDatabaseException(): void
    {
        $r = new Registry($this->dbal);

        Expect::exception(RegistryException::class);

        $r->getDatabase(new Entity());
    }

    #[Test]
    public function testGetTableSchemaException(): void
    {
        $r = new Registry($this->dbal);

        Expect::exception(RegistryException::class);

        $r->getTableSchema(new Entity());
    }

    #[Test]
    public function testGetTableNotLinked(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e);

        Expect::exception(RegistryException::class)->withMessageContaining('Entity `user` has no assigned table');

        $r->getTable($e);
    }

    #[Test]
    public function usesTheGivenDefaults(): void
    {
        $defaults = new Defaults();

        Assert::same((new Registry($this->dbal, $defaults))->getDefaults(), $defaults);
    }

    #[Test]
    public function loadsSchemasPastAnEntityWithoutTable(): void
    {
        $r = new Registry($this->dbal);
        $embedded = (new Entity())->setRole('embedded');
        $user = (new Entity())->setRole('user')->setClass(User::class);
        $r->register($embedded);
        $r->register($user)->linkTable($user, 'default', 'user');

        Assert::same($r->getTableSchema($user)->getName(), 'user');
    }

    #[Test]
    public function loadsEveryDatabaseInOneRequest(): void
    {
        $spies = [
            'default' => new SpyDatabase($this->dbal->database('default')),
            'secondary' => new SpyDatabase($this->dbal->database('secondary')),
        ];
        $provider = new class($spies) implements DatabaseProviderInterface {
            public function __construct(private readonly array $spies) {}

            public function database(?string $database = null): DatabaseInterface
            {
                return $this->spies[$database];
            }
        };
        $r = new Registry($provider);
        $user = (new Entity())->setRole('user')->setClass(User::class);
        $post = (new Entity())->setRole('post')->setClass(Post::class);
        $r->register($user)->linkTable($user, 'default', 'user');
        $r->register($post)->linkTable($post, 'secondary', 'post');

        $r->getTableSchema($user);

        Assert::same($spies['default']->getSchemasCalls, [['user']]);
        Assert::same($spies['secondary']->getSchemasCalls, [['post']]);
    }

    #[Test]
    public function loadsOnlyTheTablesNotLoadedYet(): void
    {
        $spy = new SpyDatabase($this->dbal->database('default'));
        $r = new Registry($spy);
        $user = (new Entity())->setRole('user')->setClass(User::class);
        $r->register($user)->linkTable($user, 'default', 'user');
        $userSchema = $r->getTableSchema($user);
        $author = (new Entity())->setRole('author')->setClass(Author::class);
        $post = (new Entity())->setRole('post')->setClass(Post::class);
        $r->register($author)->linkTable($author, 'default', 'user');
        $r->register($post)->linkTable($post, 'default', 'post');

        Assert::same($r->getTableSchema($author), $userSchema);
        Assert::same($r->getTableSchema($post)->getName(), 'post');
        Assert::same($spy->getSchemasCalls, [['user'], ['post']]);
    }

    #[Test]
    public function loadsTheNextDatabaseWhenTheFirstIsLoaded(): void
    {
        $r = new Registry($this->dbal);
        $user = (new Entity())->setRole('user')->setClass(User::class);
        $r->register($user)->linkTable($user, 'default', 'user');
        $userSchema = $r->getTableSchema($user);
        $author = (new Entity())->setRole('author')->setClass(Author::class);
        $post = (new Entity())->setRole('post')->setClass(Post::class);
        $r->register($author)->linkTable($author, 'default', 'user');
        $r->register($post)->linkTable($post, 'secondary', 'post');

        Assert::same($r->getTableSchema($post)->getName(), 'secondary_post');
        Assert::same($r->getTableSchema($author), $userSchema);
    }

    #[Test]
    public function testLinkTableDoesNotLoadSchema(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');

        Assert::true($r->hasTable($e));
        Assert::same($r->getDatabase($e), 'default');
        Assert::same($r->getTable($e), 'user');
    }

    #[Test]
    public function testEntitiesOnSameTableShareSchema(): void
    {
        $r = new Registry($this->dbal);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'user');

        Assert::same($r->getTableSchema($e2), $r->getTableSchema($e));
    }

    #[Test]
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

        Assert::same($r->getTableSchema($e2), $schema);
    }

    #[Test]
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
        Assert::same($spy->getSchemasCalls, []);

        // the first schema request loads every pending table in a single batched call
        $r->getTableSchema($e);
        Assert::count($spy->getSchemasCalls, 1);
        Assert::array($spy->getSchemasCalls[0])->sameElementsAs(['user', 'post']);

        // schemas for the other tables are already resolved, no further introspection
        $r->getTableSchema($e2);
        Assert::count($spy->getSchemasCalls, 1);
    }

    #[Test]
    public function testLateLinkedTableTriggersOnlyOneAdditionalBatch(): void
    {
        $spy = new SpyDatabase($this->dbal->database('default'));
        $r = new Registry($spy);

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');
        $r->getTableSchema($e);

        Assert::same($spy->getSchemasCalls, [['user']]);

        // a table linked after the first load must be introspected on its own, and only it
        $e2 = new Entity();
        $e2->setRole('post')->setClass(Post::class);
        $r->register($e2)->linkTable($e2, 'default', 'post');
        $r->getTableSchema($e2);

        Assert::same($spy->getSchemasCalls, [['user'], ['post']]);
    }

    #[Test]
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

        Assert::notSame($postSchema, $userSchema);
        Assert::same($userSchema->getName(), 'user');
        Assert::same($postSchema->getName(), 'post');
    }

    #[Test]
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

        Assert::notSame($secondarySchema, $defaultSchema);
        Assert::same($r->getDatabase($e), 'default');
        Assert::same($r->getDatabase($e2), 'secondary');
    }

    #[Test]
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

        Assert::notSame($postSchema, $userSchema);
        // the already loaded 'user' schema must not have been replaced/reloaded
        Assert::same($r->getTableSchema($e), $userSchema);
    }

    #[Test]
    public function testLinkTableWithoutGetSchemasSupport(): void
    {
        $r = new Registry(new LegacyDatabase($this->dbal->database('default')));

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);

        $e2 = new Entity();
        $e2->setRole('author')->setClass(Author::class);

        $r->register($e)->linkTable($e, 'default', 'user');
        $r->register($e2)->linkTable($e2, 'default', 'user');

        Assert::instanceOf($r->getTableSchema($e), AbstractTable::class);
        Assert::same($r->getTableSchema($e2), $r->getTableSchema($e));
    }

    #[Test]
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

        Assert::notSame($postSchema, $userSchema);
        Assert::same($userSchema->getName(), 'user');
        Assert::same($postSchema->getName(), 'post');
    }

    #[Test]
    public function testLinkTableWithoutSchemaSupportThrowsAnException(): void
    {
        $r = new Registry(new LegacyDatabase($this->dbal->database('default'), bareTables: true));

        $e = new Entity();
        $e->setRole('user')->setClass(User::class);
        $r->register($e)->linkTable($e, 'default', 'user');

        Expect::exception(RegistryException::class)->withMessageContaining('Unable to retrieve table schema.');

        $r->getTableSchema($e);
    }

    #[Test]
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

        Expect::exception(RegistryException::class);

        $r->registerChild($e, $c);
    }

    #[Test]
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
        Assert::true($e->getFields()->has('name'));

        $schema = (new Compiler())->compile($r, []);
    }

    #[Test]
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

        Assert::same($registry->getChildren($parent), [$child]);
    }
}
