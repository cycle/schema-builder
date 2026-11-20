<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Generator;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateTypecast;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Test;

final class TypecastGeneratorTest extends BaseTest
{
    #[Test]
    public function testCompiledUser(): void
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        $c = new Compiler();
        $schema = $c->compile($r, [new RenderTables(), new GenerateTypecast()]);


        Assert::same($schema['user'][Schema::TYPECAST]['p_id'], 'int');
        Assert::same($schema['user'][Schema::TYPECAST]['p_balance'], 'float');
        Assert::same($schema['user'][Schema::TYPECAST]['p_created_at'], 'datetime');
        Assert::same($schema['user'][Schema::TYPECAST]['p_name'], 'string');

        Assert::true(in_array($schema['user'][Schema::TYPECAST]['p_id'], ['int', 'bool']));
    }

    #[Test]
    public function leavesAnEntityWithoutATableAlone(): void
    {
        $e = User::define();
        $r = new Registry($this->dbal);
        $r->register($e);

        (new GenerateTypecast())->run($r);

        Assert::false($e->getFields()->get('p_id')->hasTypecast());
    }

    #[Test]
    public function keepsADeclaredTypecastAndSkipsFieldsWithoutAColumn(): void
    {
        $e = User::define();
        $e->getFields()->get('p_balance')->setTypecast('money');
        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');
        $table = $r->getTableSchema($e);
        $table->column('id')->primary();
        $table->column('balance')->float();

        (new GenerateTypecast())->run($r);

        Assert::same($e->getFields()->get('p_id')->getTypecast(), 'int');
        Assert::same($e->getFields()->get('p_balance')->getTypecast(), 'money');
        Assert::false($e->getFields()->get('p_name')->hasTypecast());
    }
}
