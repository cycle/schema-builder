<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Compiler;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Compiler;
use Cycle\Schema\Processor\RenderTable;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;

abstract class RenderTableTest extends BaseTest
{
    public function testRenderTable()
    {
        $e = Plain::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain')->run(new RenderTable());

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
        $r->run(new RenderTable())->run($c);

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

            ],
        ], $c->getResult());
    }
}