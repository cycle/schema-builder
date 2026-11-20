<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Generator;

use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Generator\SyncTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Cycle\Testing\Database\Memory;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SyncTablesTest
{
    public function createsTheTablesOfEveryEntityButReadonlyOnes(): void
    {
        $dbal = Memory::manager();
        $user = User::define();
        $plain = Plain::define();
        $plain->getOptions()->set(SyncTables::READONLY_SCHEMA, true);
        $registry = new Registry($dbal);
        $registry->register($user)->linkTable($user, 'default', 'users');
        $registry->register($plain)->linkTable($plain, 'default', 'plains');

        (new SyncTables())->run((new RenderTables())->run($registry));

        $database = $dbal->database('default');
        Assert::true($database->hasTable('users'));
        Assert::true($database->table('users')->hasColumn('user_name'));
        Assert::false($database->hasTable('plains'));
    }
}
