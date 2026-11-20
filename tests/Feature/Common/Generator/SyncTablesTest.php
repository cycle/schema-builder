<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Generator;

use Cycle\Schema\Exception\SyncException;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Generator\SyncTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SyncTablesTest extends BaseTest
{
    public function createsTheTablesOfEveryDatabase(): void
    {
        $user = User::define();
        $plain = Plain::define();
        $r = new Registry($this->dbal);
        $r->register($user)->linkTable($user, 'default', 'user');
        $r->register($plain)->linkTable($plain, 'secondary', 'plain');
        (new RenderTables())->run($r);

        (new SyncTables())->run($r);

        Assert::true($this->dbal->database('default')->hasTable('user'));
        Assert::true($this->dbal->database('secondary')->hasTable('plain'));
    }

    public function skipsEntitiesWithoutATableAndReadonlyTables(): void
    {
        $user = User::define();
        $author = Author::define();
        $author->getOptions()->set(SyncTables::READONLY_SCHEMA, true);
        $r = new Registry($this->dbal);
        $r->register(Plain::define());
        $r->register($user)->linkTable($user, 'default', 'user');
        $r->register($author)->linkTable($author, 'default', 'author');
        (new RenderTables())->run($r);

        (new SyncTables())->run($r);

        Assert::true($this->database->hasTable('user'));
        Assert::false($this->database->hasTable('author'));
        Assert::false($this->database->hasTable('plain'));
    }

    public function wrapsAFailureOfTheDatabase(): never
    {
        $plain = Plain::define();
        $r = new Registry($this->dbal);
        $r->register($plain)->linkTable($plain, 'default', 'plain');
        (new RenderTables())->run($r);
        $concurrent = $this->schema('plain');
        $concurrent->primary('id');
        $concurrent->save();

        Expect::exception(SyncException::class)->withMessageContaining('plain');

        (new SyncTables())->run($r);
    }
}
