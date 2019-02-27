<?php
declare(strict_types=1);
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
use Cycle\Schema\Generator\TableGenerator;
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

        $r->iterate(new TableGenerator());

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
        $r->iterate(new TableGenerator())->iterate($c);

        $this->assertSame([
            'plain' => [
                Schema::ENTITY       => Plain::class,
                Schema::MAPPER       => Mapper::class,
                Schema::SOURCE       => Source::class,
                Schema::REPOSITORY   => Repository::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'plain',
                Schema::PRIMARY_KEY  => 'id',
                Schema::FIND_BY_KEYS => [],
                Schema::COLUMNS      => ['id' => 'id'],
                Schema::RELATIONS    => [],
                Schema::CONSTRAIN    => null,
                Schema::TYPECAST     => [],
                Schema::SCHEMA       => []
            ],
        ], $c->getSchema());
    }

    public function testRenderUserTable()
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        $r->iterate(new TableGenerator());

        $table = $r->getTableSchema($e);

        $this->assertSame('user', $table->getName());
        $this->assertSame(['id'], $table->getPrimaryKeys());

        $this->assertTrue($table->hasColumn('id'));
        $this->assertSame('primary', $table->column('id')->getAbstractType());

        $this->assertTrue($table->hasColumn('user_name'));
        $this->assertSame('string', $table->column('user_name')->getType());
        $this->assertSame(32, $table->column('user_name')->getSize());
    }

    public function testCompiledUser()
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        $c = new Compiler();
        $r->iterate(new TableGenerator())->iterate($c);

        $this->assertSame([
            'user' => [
                Schema::ENTITY       => User::class,
                Schema::MAPPER       => Mapper::class,
                Schema::SOURCE       => Source::class,
                Schema::REPOSITORY   => Repository::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'user',
                Schema::PRIMARY_KEY  => 'id',
                Schema::FIND_BY_KEYS => [],
                Schema::COLUMNS      => ['id' => 'id', 'name' => 'user_name'],
                Schema::RELATIONS    => [],
                Schema::CONSTRAIN    => null,
                Schema::TYPECAST     => [],
                Schema::SCHEMA       => []
            ],
        ], $c->getSchema());
    }
}