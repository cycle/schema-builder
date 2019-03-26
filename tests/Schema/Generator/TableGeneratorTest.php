<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Generator;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;

abstract class TableGeneratorTest extends BaseTest
{
    public function testRenderTable()
    {
        $e = Plain::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');

        (new RenderTables())->run($r);

        $table = $r->getTableSchema($e);

        $this->assertSame('plain', $table->getName());
        $this->assertSame(['id'], $table->getPrimaryKeys());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertSame('primary', $table->column('id')->getAbstractType());
    }

    public function testCompiled()
    {
        $e = Plain::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');

        $c = new Compiler();
        $schema = $c->compile((new RenderTables())->run($r));

        $this->assertSame([
            'plain' => [
                Schema::ENTITY       => Plain::class,
                Schema::MAPPER       => Mapper::class,
                Schema::SOURCE       => Source::class,
                Schema::REPOSITORY   => Repository::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'plain',
                Schema::PRIMARY_KEY  => 'id',
                Schema::FIND_BY_KEYS => ['id'],
                Schema::COLUMNS      => ['id' => 'id'],
                Schema::RELATIONS    => [],
                Schema::CONSTRAIN    => null,
                Schema::TYPECAST     => [],
                Schema::SCHEMA       => []
            ],
        ], $schema);
    }

    public function testRenderUserTable()
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        (new RenderTables())->run($r);

        $table = $r->getTableSchema($e);

        $this->assertSame('user', $table->getName());
        $this->assertSame(['id'], $table->getPrimaryKeys());

        $this->assertTrue($table->hasColumn('id'));
        $this->assertSame('primary', $table->column('id')->getAbstractType());

        $this->assertTrue($table->hasColumn('user_name'));
        $this->assertSame('string', $table->column('user_name')->getType());
        $this->assertSame(32, $table->column('user_name')->getSize());

        $this->assertTrue($table->hasColumn('active'));
        $this->assertTrue(in_array($table->column('active')->getAbstractType(), ['boolean', 'integer']));

        $this->assertTrue($table->hasColumn('balance'));
        $this->assertSame('float', $table->column('balance')->getAbstractType());

        $this->assertTrue($table->hasColumn('created_at'));
    }

    public function testCompiledUser()
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        $c = new Compiler();
        $c->compile($r, [new RenderTables()]);

        $this->assertSame([
            'user' => [
                Schema::ENTITY       => User::class,
                Schema::MAPPER       => Mapper::class,
                Schema::SOURCE       => Source::class,
                Schema::REPOSITORY   => Repository::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'user',
                Schema::PRIMARY_KEY  => 'id',
                Schema::FIND_BY_KEYS => ['id'],
                Schema::COLUMNS      => [
                    'id'         => 'id',
                    'name'       => 'user_name',
                    'active'     => 'active',
                    'balance'    => 'balance',
                    'created_at' => 'created_at'
                ],
                Schema::RELATIONS    => [],
                Schema::CONSTRAIN    => null,
                Schema::TYPECAST     => [],
                Schema::SCHEMA       => []
            ],
        ], $c->getSchema());
    }
}