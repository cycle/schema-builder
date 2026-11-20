<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Schema\GeneratedField;
use Cycle\ORM\SchemaInterface;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\CompilerException;
use Cycle\Schema\Exception\SchemaModifierException;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier;
use Cycle\Schema\Tests\Fixtures\Typecaster;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
final class CompilerTest
{
    public static function renderTypecastDataProvider(): \Traversable
    {
        yield [null, []];
        yield [Typecaster::class, [], Typecaster::class];
        yield [[Typecaster::class], [SchemaInterface::TYPECAST_HANDLER => Typecaster::class]];
        yield [
            [Typecaster::class, Typecast::class],
            [SchemaInterface::TYPECAST_HANDLER => Typecast::class],
            Typecaster::class,
        ];
        yield [
            [Typecaster::class, Typecast::class],
            [SchemaInterface::TYPECAST_HANDLER => [Typecaster::class, Typecast::class]],
            Typecaster::class,
        ];
    }

    public function testWrongGeneratorShouldThrowAnException(): void
    {
        Expect::exception(CompilerException::class)->withMessageContaining('Invalid generator `\'Cycle\\\\Schema\\\\Tests\\\\Fixtures\\\\Author\'`. '
        . 'It should implement `Cycle\Schema\GeneratorInterface` interface.');

        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $r->register($author);

        (new Compiler())->compile($r, [
            Author::class,
        ]);
    }

    public function testWrongEntitySchemaModifierShouldThrowAnException(): void
    {
        Expect::exception(SchemaModifierException::class)->withMessageContaining('Unable to apply schema modifier `Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier` '
        . 'for the `author` role. Something went wrong');

        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $author = new Entity();
        $author->setRole('author')->setClass(Author::class);
        $author->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        $author->addSchemaModifier(new BrokenSchemaModifier(BrokenSchemaModifier::class . '::modifySchema'));

        $r->register($author);

        (new Compiler())->compile($r);
    }

    #[DataProvider('renderTypecastDataProvider')]
    public function testRenderTypecast(mixed $expected, array $defaults, mixed $entityTypecast = null): void
    {
        $entity = new Entity();
        $entity->setRole('author')->setClass(Author::class);
        $entity->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));
        if ($entityTypecast) {
            $entity->setTypecast($entityTypecast);
        }

        $r = new Registry(\Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing());
        $r->register($entity);

        $schema = (new Compiler())->compile($r, [], $defaults);

        Assert::same($schema['author'][SchemaInterface::TYPECAST_HANDLER], $expected);
    }

    public function testRenderGeneratedFields(): void
    {
        $entity = new Entity();
        $entity->setRole('author')->setClass(Author::class);
        $entity->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));
        $entity->getFields()->set('name', (new Field())->setType('string')->setColumn('name'));
        $entity->getFields()->set(
            'createdAt',
            (new Field())
                ->setType('datetime')
                ->setColumn('created_at')
                ->setGenerated(GeneratedField::BEFORE_INSERT),
        );
        $entity->getFields()->set(
            'updatedAt',
            (new Field())
                ->setType('datetime')
                ->setColumn('created_at')
                ->setGenerated(GeneratedField::BEFORE_INSERT | GeneratedField::BEFORE_UPDATE),
        );
        $entity->getFields()->set(
            'sequence',
            (new Field())
                ->setType('serial')
                ->setColumn('some_sequence')
                ->setGenerated(GeneratedField::ON_INSERT),
        );

        $r = new Registry(\Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing());
        $r->register($entity);

        $schema = (new Compiler())->compile($r);

        Assert::same($schema['author'][SchemaInterface::GENERATED_FIELDS], [
            'createdAt' => GeneratedField::BEFORE_INSERT,
            'updatedAt' => GeneratedField::BEFORE_INSERT | GeneratedField::BEFORE_UPDATE,
            'sequence' => GeneratedField::ON_INSERT,
        ]);
    }
}
