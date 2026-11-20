<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateTypecast;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\User;

abstract class TypecastGeneratorTest extends BaseTest
{
    #[\Testo\Test]
    public function testCompiledUser(): void
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        $c = new Compiler();
        $schema = $c->compile($r, [new RenderTables(), new GenerateTypecast()]);


        \Testo\Assert::same($schema['user'][Schema::TYPECAST]['p_id'], 'int');
        \Testo\Assert::same($schema['user'][Schema::TYPECAST]['p_balance'], 'float');
        \Testo\Assert::same($schema['user'][Schema::TYPECAST]['p_created_at'], 'datetime');
        \Testo\Assert::same($schema['user'][Schema::TYPECAST]['p_name'], 'string');

        \Testo\Assert::true(in_array($schema['user'][Schema::TYPECAST]['p_id'], ['int', 'bool']));
    }
}
