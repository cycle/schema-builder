<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\CompilerException;
use Cycle\Schema\Exception\SchemaModifierException;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier;
use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase
{
    public function testWrongGeneratorShouldThrowAnException()
    {
        $this->expectException(CompilerException::class);
        $this->expectErrorMessage(
            'Invalid generator `\'Cycle\\\\Schema\\\\Tests\\\\Fixtures\\\\Author\'`. '
            . 'It should implement `Cycle\Schema\GeneratorInterface` interface.'
        );

        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
        );

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $r->register($author);

        (new Compiler())->compile($r, [
            Author::class,
        ]);
    }

    public function testWrongEntitySchemaModifierShouldThrowAnException()
    {
        $this->expectException(SchemaModifierException::class);
        $this->expectErrorMessage(
            'Unable to apply schema modifier `Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier` '
            . 'for the `author` role. Something went wrong'
        );

        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
        );

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $author->addSchemaModifier(new BrokenSchemaModifier(BrokenSchemaModifier::class . '::modifySchema'));

        $r->register($author);

        (new Compiler())->compile($r);
    }
}
