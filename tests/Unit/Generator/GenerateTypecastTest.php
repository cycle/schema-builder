<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Generator;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateTypecast;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\User;
use Cycle\Testing\Database\Memory;
use Testo\Assert;
use Testo\Test;

#[Test]
final class GenerateTypecastTest
{
    public function derivesTheTypecastFromTheColumnType(): void
    {
        $user = User::define();
        $registry = new Registry(Memory::manager());
        $registry->register($user)->linkTable($user, 'default', 'user');

        $schema = (new Compiler())->compile($registry, [new RenderTables(), new GenerateTypecast()]);

        $typecast = $schema['user'][Schema::TYPECAST];
        Assert::same($typecast['p_id'], 'int');
        Assert::same($typecast['p_active'], 'bool');
        Assert::same($typecast['p_balance'], 'float');
        Assert::same($typecast['p_created_at'], 'datetime');
        Assert::same($typecast['p_name'], 'string');
    }
}
