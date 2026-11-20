<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Definition\Inheritance;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Inheritance\JoinedTable;
use Cycle\Schema\Exception\TableInheritance\WrongParentKeyColumnException;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class JoinedTableInheritanceTest
{
    public function testJoinedTableShouldBeAddedToSchema(): void
    {
        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->getFields()->set('foo_bar', (new Field())->setType('primary')->setColumn('id'));

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->setInheritance($inheritance = new JoinedTable($user, 'foo_bar'));
        $author->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $r->register($user);
        $r->register($author);

        $schema = (new Compiler())->compile($r);

        Assert::same($schema['author'][SchemaInterface::PARENT], 'user');
        Assert::same($schema['author'][SchemaInterface::PARENT_KEY] ?? null, 'foo_bar');
    }

    public function testJoinedTableWithoutOuterKeyShouldBeAddedToSchema(): void
    {
        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->setInheritance($inheritance = new JoinedTable($user));
        $author->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $r->register($user);
        $r->register($author);

        $schema = (new Compiler())->compile($r);

        Assert::same($schema['author'][SchemaInterface::PARENT], 'user');
        Assert::blank($schema['author'][SchemaInterface::PARENT_KEY] ?? null);
    }

    public function testJoinedTableWithNonExistsOuterKeyShouldThrowAnException(): void
    {
        Expect::exception(WrongParentKeyColumnException::class)->withMessageContaining('Outer key column `foo_bar` is not found among fields of the `user` role.');

        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->setInheritance($inheritance = new JoinedTable($user, 'foo_bar'));
        $author->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $r->register($user);
        $r->register($author);

        (new Compiler())->compile($r);
    }
}
