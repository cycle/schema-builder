<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Definition\Inheritance;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Inheritance\SingleTable;
use Cycle\Schema\Exception\TableInheritance\DiscriminatorColumnNotPresentException;
use Cycle\Schema\Exception\TableInheritance\WrongDiscriminatorColumnException;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SingleTableInheritanceTest
{
    public function testSingleTableShouldBeAddedToSchema(): void
    {
        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->markAsChildOfSingleTableInheritance(User::class);

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->setInheritance($inheritance = new SingleTable());

        $inheritance->setDiscriminator('type');
        $inheritance->addChild('foo', 'bar');
        $inheritance->addChild('author', 'author');

        $user->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));
        $user->getFields()->set('type', (new Field())->setType('string')->setColumn('type'));

        $r->register($user);
        $r->register($author);

        $schema = (new Compiler())->compile($r);

        Assert::same($schema['user'][SchemaInterface::CHILDREN], ['foo' => 'bar', 'author' => 'author']);
        Assert::same($schema['user'][SchemaInterface::DISCRIMINATOR], 'type');

        Assert::same($schema['author'], [SchemaInterface::ENTITY => Author::class]);
    }

    public function testSingleTableWithExplicitPkShouldBeAddedToSchema(): void
    {
        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));
        $author->markAsChildOfSingleTableInheritance(User::class);

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->setInheritance($inheritance = new SingleTable());

        $inheritance->setDiscriminator('type');
        $inheritance->addChild('foo', 'bar');
        $inheritance->addChild('author', 'author');

        $user->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));
        $user->getFields()->set('type', (new Field())->setType('string')->setColumn('type'));

        $r->register($user);
        $r->register($author);

        $schema = (new Compiler())->compile($r);

        Assert::same($schema['user'][SchemaInterface::CHILDREN], ['foo' => 'bar', 'author' => 'author']);
        Assert::same($schema['user'][SchemaInterface::DISCRIMINATOR], 'type');

        Assert::same($schema['author'], [SchemaInterface::ENTITY => Author::class]);
    }

    public function testSingleTableWithoutDiscriminatorColumnShouldThrowAnException(): void
    {
        Expect::exception(DiscriminatorColumnNotPresentException::class)->withMessageContaining('Discriminator column for the `user` role should be defined.');

        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->setInheritance(new SingleTable());

        $user->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $r->register($user);

        (new Compiler())->compile($r);
    }

    public function testSingleTableWithNonExistsDiscriminatorColumnShouldThrowAnException(): void
    {
        Expect::exception(WrongDiscriminatorColumnException::class)->withMessageContaining('Discriminator column `type` is not found among fields of the `user` role.');

        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->setInheritance($inheritance = new SingleTable());
        $inheritance->setDiscriminator('type');
        $user->getFields()->set(
            'id',
            (new Field())->setType('primary')->setColumn('id'),
        );

        $r->register($user);

        (new Compiler())->compile($r);
    }
}
