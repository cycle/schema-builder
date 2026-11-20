<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Generator;

use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Generator\ResetTables;
use Cycle\Schema\Generator\SyncTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ResetTablesTest extends BaseTest
{
    public function forgetsEverythingAnExistingTableDeclares(): void
    {
        $this->createUserAndPlainTables();
        $plain = Plain::define();
        $r = new Registry($this->dbal);
        $r->register($plain)->linkTable($plain, 'default', 'plain');

        (new ResetTables())->run($r);

        $table = $r->getTableSchema($plain);
        Assert::same($table->getColumns(), []);
        Assert::same($table->getIndexes(), []);
        Assert::same($table->getForeignKeys(), []);
        Assert::same($table->getPrimaryKeys(), []);
    }

    public function leavesNewTablesAndEntitiesWithoutATableAlone(): void
    {
        $user = User::define();
        $r = new Registry($this->dbal);
        $r->register(Author::define());
        $r->register($user)->linkTable($user, 'default', 'user');
        $r->getTableSchema($user)->column('id')->primary();

        (new ResetTables())->run($r);

        Assert::array($r->getTableSchema($user)->getColumns())->hasKeys('id');
    }

    private function createUserAndPlainTables(): void
    {
        $plain = Plain::define();
        $user = User::define();
        $r = new Registry($this->dbal);
        $r->register($plain)->linkTable($plain, 'default', 'plain');
        $r->register($user)->linkTable($user, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()]),
            new RenderTables(),
            new RenderRelations(),
            new SyncTables(),
        ]);

        Assert::true($this->getDriver()->getSchema('plain')->hasForeignKey(['user_p_id']));
    }
}
