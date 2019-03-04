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
use Cycle\Schema\Generator\GenerateTypecast;
use Cycle\Schema\Generator\RenderTable;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\User;

abstract class TypecastGeneratorTest extends BaseTest
{
    public function testCompiledUser()
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        $c = new Compiler();
        $c->compile($r, [new RenderTable(), new GenerateTypecast()]);

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
                Schema::COLUMNS      => ['id' => 'id', 'name' => 'user_name'],
                Schema::RELATIONS    => [],
                Schema::CONSTRAIN    => null,
                Schema::TYPECAST     => ['id' => 'int'],
                Schema::SCHEMA       => []
            ],
        ], $c->getSchema());
    }
}