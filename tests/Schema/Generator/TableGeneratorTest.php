<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\Typecaster;
use Cycle\Schema\Tests\Fixtures\User;

abstract class TableGeneratorTest extends BaseTest
{
    #[\Testo\Test]
    public function testRenderTable(): void
    {
        $e = Plain::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');

        (new RenderTables())->run($r);

        $table = $r->getTableSchema($e);

        \Testo\Assert::same($table->getName(), 'plain');
        \Testo\Assert::same($table->getPrimaryKeys(), ['id']);
        \Testo\Assert::true($table->hasColumn('id'));
        \Testo\Assert::same($table->column('id')->getAbstractType(), 'primary');
    }

    #[\Testo\Test]
    public function testCompiled(): void
    {
        $e = Plain::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');

        $c = new Compiler();
        $schema = $c->compile(
            (new RenderTables())->run($r),
            defaults: [
                Schema::TYPECAST_HANDLER => 'default_typecaster',
            ],
        );

        \Testo\Assert::same($schema, [
            'plain' => [
                Schema::ENTITY => Plain::class,
                Schema::MAPPER => Mapper::class,
                Schema::SOURCE => Source::class,
                Schema::REPOSITORY => Repository::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'plain',
                Schema::PRIMARY_KEY => ['p_id'],
                Schema::FIND_BY_KEYS => ['p_id'],
                Schema::COLUMNS => ['p_id' => 'id'],
                Schema::RELATIONS => [],
                Schema::SCOPE => null,
                Schema::TYPECAST => [],
                Schema::SCHEMA => [],
                Schema::TYPECAST_HANDLER => [Typecaster::class, 'default_typecaster'],
                Schema::GENERATED_FIELDS => [],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testCompiledWithPassedDefaultTypecastHandler(): void
    {
        $e = Post::define();
        $e->setTypecast(null);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');

        $c = new Compiler();
        $schema = $c->compile(
            (new RenderTables())->run($r),
            defaults: [
                Schema::TYPECAST_HANDLER => Typecaster::class,
            ],
        );

        \Testo\Assert::same($schema, [
            'post' => [
                Schema::ENTITY => Post::class,
                Schema::MAPPER => Mapper::class,
                Schema::SOURCE => Source::class,
                Schema::REPOSITORY => Repository::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                Schema::PRIMARY_KEY => ['p_id'],
                Schema::FIND_BY_KEYS => ['p_id'],
                Schema::COLUMNS => ['p_id' => 'id'],
                Schema::RELATIONS => [],
                Schema::SCOPE => null,
                Schema::TYPECAST => [],
                Schema::SCHEMA => [],
                Schema::TYPECAST_HANDLER => [Typecaster::class],
                Schema::GENERATED_FIELDS => [],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testRenderUserTable(): void
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        (new RenderTables())->run($r);

        $table = $r->getTableSchema($e);

        \Testo\Assert::same($table->getName(), 'user');
        \Testo\Assert::same($table->getPrimaryKeys(), ['id']);

        \Testo\Assert::true($table->hasColumn('id'));
        \Testo\Assert::same($table->column('id')->getAbstractType(), 'primary');

        \Testo\Assert::true($table->hasColumn('user_name'));
        \Testo\Assert::same($table->column('user_name')->getType(), 'string');
        \Testo\Assert::same($table->column('user_name')->getSize(), 32);

        \Testo\Assert::true($table->hasColumn('active'));
        \Testo\Assert::true(in_array($table->column('active')->getAbstractType(), ['boolean', 'integer']));

        \Testo\Assert::true($table->hasColumn('balance'));
        \Testo\Assert::same($table->column('balance')->getAbstractType(), 'float');

        \Testo\Assert::true($table->hasColumn('created_at'));
    }

    #[\Testo\Test]
    public function testCompiledUser(): void
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        $c = new Compiler();
        $c->compile($r, [new RenderTables()]);

        \Testo\Assert::same($c->getSchema(), [
            'user' => [
                Schema::ENTITY => User::class,
                Schema::MAPPER => Mapper::class,
                Schema::SOURCE => Source::class,
                Schema::REPOSITORY => Repository::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => ['p_id'],
                Schema::FIND_BY_KEYS => ['p_id'],
                Schema::COLUMNS => [
                    'p_id' => 'id',
                    'p_name' => 'user_name',
                    'p_active' => 'active',
                    'p_balance' => 'balance',
                    'p_created_at' => 'created_at',
                ],
                Schema::RELATIONS => [],
                Schema::SCOPE => null,
                Schema::TYPECAST => [],
                Schema::SCHEMA => [],
                Schema::TYPECAST_HANDLER => [
                    Typecaster::class,
                ],
                Schema::GENERATED_FIELDS => [],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testCompiledPost(): void
    {
        $e = Post::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');

        $c = new Compiler();
        $c->compile($r, [new RenderTables()]);

        \Testo\Assert::same($c->getSchema(), [
            'post' => [
                Schema::ENTITY => Post::class,
                Schema::MAPPER => Mapper::class,
                Schema::SOURCE => Source::class,
                Schema::REPOSITORY => Repository::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                Schema::PRIMARY_KEY => ['p_id'],
                Schema::FIND_BY_KEYS => ['p_id'],
                Schema::COLUMNS => [
                    'p_id' => 'id',
                ],
                Schema::RELATIONS => [],
                Schema::SCOPE => null,
                Schema::TYPECAST => [],
                Schema::SCHEMA => [],
                Schema::TYPECAST_HANDLER => null,
                Schema::GENERATED_FIELDS => [],
            ],
        ]);
    }
}
