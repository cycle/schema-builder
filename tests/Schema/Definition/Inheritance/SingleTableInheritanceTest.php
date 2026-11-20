<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Definition\Inheritance;

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
use PHPUnit\Framework\TestCase;

class SingleTableInheritanceTest
{
    #[\Testo\Test]
    public function testSingleTableShouldBeAddedToSchema()
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

        \Testo\Assert::same($schema['user'][SchemaInterface::CHILDREN], ['foo' => 'bar', 'author' => 'author']);
        \Testo\Assert::same($schema['user'][SchemaInterface::DISCRIMINATOR], 'type');

        \Testo\Assert::same($schema['author'], [SchemaInterface::ENTITY => Author::class]);
    }

    #[\Testo\Test]
    public function testSingleTableWithExplicitPkShouldBeAddedToSchema()
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

        \Testo\Assert::same($schema['user'][SchemaInterface::CHILDREN], ['foo' => 'bar', 'author' => 'author']);
        \Testo\Assert::same($schema['user'][SchemaInterface::DISCRIMINATOR], 'type');

        \Testo\Assert::same($schema['author'], [SchemaInterface::ENTITY => Author::class]);
    }

    #[\Testo\Test]
    public function testSingleTableWithoutDiscriminatorColumnShouldThrowAnException()
    {
        \Testo\Expect::exception(DiscriminatorColumnNotPresentException::class)->withMessageContaining('Discriminator column for the `user` role should be defined.');

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

    #[\Testo\Test]
    public function testSingleTableWithNonExistsDiscriminatorColumnShouldThrowAnException()
    {
        \Testo\Expect::exception(WrongDiscriminatorColumnException::class)->withMessageContaining('Discriminator column `type` is not found among fields of the `user` role.');

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
