<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\ForeignKey;
use Cycle\Schema\Generator\ForeignKeys;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;

abstract class ForeignKeysTest extends BaseTest
{
    public function testTableSchemaShouldBeModified(): void
    {
        $author = Author::define();
        $user = User::define();
        $plain = Plain::define();

        $registry = new Registry($this->dbal);
        $registry->register($author)->linkTable($author, 'default', 'authors');
        $registry->register($user)->linkTable($user, 'default', 'users');
        $registry->register($plain)->linkTable($plain, 'default', 'plain');

        $this->assertSame([], $registry->getTableSchema($author)->getForeignKeys());

        $fk = new ForeignKey();
        $fk->setTarget('user');
        $fk->setInnerColumns(['id']);
        $fk->setOuterColumns(['id']);
        $fk->setAction('CASCADE');
        $fk->createIndex(true);

        $author->getForeignKeys()->set($fk);

        $compiler = new Compiler();
        $compiler->compile($registry, [new RenderTables(), new ForeignKeys()]);

        $foreignKeys = $registry->getTableSchema($author)->getForeignKeys();
        $expectedFk = array_shift($foreignKeys);

        $this->assertStringContainsString('authors', $expectedFk->getTable());
        $this->assertStringContainsString('users', $expectedFk->getForeignTable());
        $this->assertSame(['id'], $expectedFk->getColumns());
        $this->assertSame(['id'], $expectedFk->getForeignKeys());
        $this->assertSame('CASCADE', $expectedFk->getDeleteRule());
        $this->assertSame('CASCADE', $expectedFk->getUpdateRule());
        $this->assertTrue($expectedFk->hasIndex());
    }
}
