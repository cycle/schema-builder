<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Generator;

use Cycle\Database\Schema\AbstractTable;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Cycle\Testing\Database\Memory;
use Testo\Assert;
use Testo\Test;

#[Test]
final class RenderTablesTest
{
    public function listsAReferencedTableBeforeTheTableReferencingIt(): void
    {
        $plain = Plain::define();
        $user = User::define();
        $registry = new Registry(Memory::manager());
        $registry->register($plain)->linkTable($plain, 'default', 'plains');
        $registry->register($user)->linkTable($user, 'default', 'users');
        $plains = $registry->getTableSchema($plain);
        $plains->integer('user_id');
        $plains->foreignKey(['user_id'])->references('users', ['id']);
        $tables = new RenderTables();

        $tables->run($registry);

        $names = \array_map(static fn(AbstractTable $table): string => $table->getName(), $tables->getTables());
        Assert::same($names, ['users', 'plains']);
    }
}
