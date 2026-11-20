<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Field;
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
    #[\Testo\Test]
    public function testTableSchemaShouldBeModified(): void
    {
        $author = Author::defineWithUser();
        $user = User::define();
        $plain = Plain::define();

        $registry = new Registry($this->dbal);
        $registry->register($author)->linkTable($author, 'default', 'authors');
        $registry->register($user)->linkTable($user, 'default', 'users');
        $registry->register($plain)->linkTable($plain, 'default', 'plain');

        \Testo\Assert::same($registry->getTableSchema($author)->getForeignKeys(), []);

        $fk = new ForeignKey();
        $fk->setTarget('user');
        $fk->setInnerColumns(['user_id']);
        $fk->setOuterColumns(['id']);
        $fk->setAction('CASCADE');
        $fk->createIndex(true);

        $author->getForeignKeys()->set($fk);

        $compiler = new Compiler();
        $compiler->compile($registry, [new RenderTables(), new ForeignKeys()]);

        $foreignKeys = $registry->getTableSchema($author)->getForeignKeys();
        $expectedFk = array_shift($foreignKeys);

        \Testo\Assert::string($expectedFk->getTable())->contains('authors');
        \Testo\Assert::string($expectedFk->getForeignTable())->contains('users');
        \Testo\Assert::same($expectedFk->getColumns(), ['user_id']);
        \Testo\Assert::same($expectedFk->getForeignKeys(), ['id']);
        \Testo\Assert::same($expectedFk->getDeleteRule(), 'CASCADE');
        \Testo\Assert::same($expectedFk->getUpdateRule(), 'CASCADE');
        \Testo\Assert::true($expectedFk->hasIndex());
    }

    #[\Testo\Test]
    public function testCreateIndex(): void
    {
        $author = Author::defineWithUser();
        $user = User::define();
        $user->getFields()->set('u_other_id', (new Field())->setType('integer')->setColumn('other_id'));
        $plain = Plain::define();

        $registry = new Registry($this->dbal);
        $registry->register($author)->linkTable($author, 'default', 'authors');
        $registry->register($user)->linkTable($user, 'default', 'users');
        $registry->register($plain)->linkTable($plain, 'default', 'plain');

        \Testo\Assert::same($registry->getTableSchema($author)->getForeignKeys(), []);

        $fk = new ForeignKey();
        $fk->setTarget('user');
        $fk->setInnerColumns(['user_id']);
        $fk->setOuterColumns(['other_id']);
        $fk->setAction('CASCADE');
        $fk->createIndex(true);

        $author->getForeignKeys()->set($fk);

        $compiler = new Compiler();
        $compiler->compile($registry, [new RenderTables(), new ForeignKeys()]);

        \Testo\Assert::count($registry->getTableSchema($user)->getIndexes(), 1);
    }

    #[\Testo\Test]
    public function testShouldNotCreateIndexOnPk(): void
    {
        $author = Author::defineWithUser();
        $user = User::define();
        $plain = Plain::define();

        $registry = new Registry($this->dbal);
        $registry->register($author)->linkTable($author, 'default', 'authors');
        $registry->register($user)->linkTable($user, 'default', 'users');
        $registry->register($plain)->linkTable($plain, 'default', 'plain');

        \Testo\Assert::same($registry->getTableSchema($author)->getForeignKeys(), []);

        $fk = new ForeignKey();
        $fk->setTarget('user');
        $fk->setInnerColumns(['user_id']);
        $fk->setOuterColumns(['id']);
        $fk->setAction('CASCADE');
        $fk->createIndex(true);

        $author->getForeignKeys()->set($fk);

        $compiler = new Compiler();
        $compiler->compile($registry, [new RenderTables(), new ForeignKeys()]);

        \Testo\Assert::blank($registry->getTableSchema($user)->getIndexes());
    }
}
