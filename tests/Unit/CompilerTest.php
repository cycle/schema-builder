<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Mapper\Mapper;
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

    public function entityClassesOverrideTheDefaults(): void
    {
        $entity = $this->author();
        $entity->setSource('EntitySource')->setMapper('EntityMapper')
            ->setRepository('EntityRepository')->setScope('EntityScope');
        $r = new Registry(\Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing());
        $r->register($entity);

        $schema = (new Compiler())->compile($r, [], [SchemaInterface::SCOPE => 'DefaultScope']);

        Assert::same($schema['author'][SchemaInterface::SOURCE], 'EntitySource');
        Assert::same($schema['author'][SchemaInterface::MAPPER], 'EntityMapper');
        Assert::same($schema['author'][SchemaInterface::REPOSITORY], 'EntityRepository');
        Assert::same($schema['author'][SchemaInterface::SCOPE], 'EntityScope');
    }

    public function defaultsFillTheClassesAnEntityLeavesUnset(): void
    {
        $r = new Registry(\Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing());
        $r->register($this->author());

        $schema = (new Compiler())->compile($r, [], [SchemaInterface::SCOPE => 'DefaultScope']);

        Assert::same($schema['author'][SchemaInterface::SCOPE], 'DefaultScope');
        Assert::same($schema['author'][SchemaInterface::MAPPER], Mapper::class);
    }

    public function rendersColumnsAndReferencedKeys(): void
    {
        $entity = $this->author();
        $entity->getFields()->get('id')->setReferenced(true);
        $entity->getFields()->set('name', (new Field())->setType('string')->setColumn('full_name'));
        $entity->getFields()->set('slug', (new Field())->setType('string')->setColumn('slug')->setReferenced(true));
        $r = new Registry(\Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing());
        $r->register($entity);

        $schema = (new Compiler())->compile($r);

        Assert::same($schema['author'][SchemaInterface::COLUMNS], ['id' => 'id', 'name' => 'full_name', 'slug' => 'slug']);
        Assert::same($schema['author'][SchemaInterface::FIND_BY_KEYS], [0 => 'id', 2 => 'slug']);
    }

    public function allowsFieldsSharingAColumnWithTheSameDefinition(): void
    {
        $entity = $this->author();
        $entity->getFields()->set('name', (new Field())->setType('string')->setColumn('name'));
        $entity->getFields()->set('alias', (new Field())->setType('string')->setColumn('name'));
        $r = new Registry(\Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing());
        $r->register($entity);

        $schema = (new Compiler())->compile($r);

        Assert::same($schema['author'][SchemaInterface::COLUMNS], ['id' => 'id', 'name' => 'name', 'alias' => 'name']);
    }

    public function rejectsFieldsSharingAColumnWithDifferentDefinitions(): never
    {
        $entity = $this->author();
        $entity->getFields()->set('name', (new Field())->setType('string')->setColumn('name'));
        $entity->getFields()->set('alias', (new Field())->setType('text')->setColumn('name'));
        $r = new Registry(\Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing());
        $r->register($entity);

        Expect::exception(CompilerException::class)->withMessage(
            "Error compiling the `author` role.\n\n"
            . "Different definitions are specified for the `name` column:\n\n"
            . "name:\n  type=string\n\nalias:\n  type=text",
        );

        (new Compiler())->compile($r);
    }

    private function author(): Entity
    {
        $entity = new Entity();
        $entity->setRole('author')->setClass(Author::class);
        $entity->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));

        return $entity;
    }
}
