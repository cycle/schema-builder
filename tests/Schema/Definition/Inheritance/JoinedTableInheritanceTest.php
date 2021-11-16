<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Definition\Inheritance;

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
use PHPUnit\Framework\TestCase;

class JoinedTableInheritanceTest extends TestCase
{
    public function testJoinedTableShouldBeAddedToSchema()
    {
        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
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

        $this->assertSame('user', $schema['author'][SchemaInterface::PARENT]);
        $this->assertSame('foo_bar', $schema['author'][SchemaInterface::PARENT_KEY] ?? null);
    }

    public function testJoinedTableWithoutOuterKeyShouldBeAddedToSchema()
    {
        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
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

        $this->assertSame('user', $schema['author'][SchemaInterface::PARENT]);
        $this->assertEmpty($schema['author'][SchemaInterface::PARENT_KEY] ?? null);
    }

    public function testJoinedTableWithNonExistsOuterKeyShouldThrowAnException()
    {
        $this->expectException(WrongParentKeyColumnException::class);
        $this->expectErrorMessage('Outer key column `foo_bar` not found among fields of the `user` role.');

        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
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
