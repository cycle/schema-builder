<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Generator;

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation as RelationDefinition;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Composite;
use Cycle\Schema\Tests\Fixtures\EmbeddedEntity;
use Cycle\Schema\Tests\Fixtures\MorphedTo;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\RefersToMorphedTo;
use Cycle\Schema\Tests\Fixtures\Tag;
use Cycle\Schema\Tests\Fixtures\TagContext;
use Cycle\Schema\Tests\Fixtures\User;
use Cycle\Testing\Database\Memory;
use Testo\Assert;
use Testo\Assert\Api\ExpectedException;
use Testo\Data\DataProvider;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Test;

#[Test]
final class GenerateRelationsTest
{
    public static function relations(): iterable
    {
        foreach ([
            'belongsTo' => Relation::BELONGS_TO,
            'hasOne' => Relation::HAS_ONE,
            'hasMany' => Relation::HAS_MANY,
            'refersTo' => Relation::REFERS_TO,
            'morphedHasOne' => Relation::MORPHED_HAS_ONE,
            'morphedHasMany' => Relation::MORPHED_HAS_MANY,
        ] as $type => $expected) {
            yield $type => [
                static function (Registry $registry) use ($type): void {
                    $user = User::define();
                    $user->getRelations()->get('plain')->setType($type);
                    self::link($registry, Plain::define(), $user);
                },
                'user',
                'plain',
                $expected,
            ];
        }

        yield 'manyToMany' => [
            static function (Registry $registry): void {
                $post = Post::define();
                $post->getRelations()->remove('author');
                $post->getRelations()->set('tags', (new RelationDefinition())->setType('manyToMany')->setTarget('tag'));
                $post->getRelations()->get('tags')->getOptions()->set('though', 'tagContext');
                self::link($registry, $post, Tag::define(), TagContext::define());
            },
            'post',
            'tags',
            Relation::MANY_TO_MANY,
        ];

        foreach ([
            'belongsToMorphed' => [MorphedTo::define(...), Relation::BELONGS_TO_MORPHED],
            'refersToMorphed' => [RefersToMorphedTo::define(...), Relation::REFERS_TO_MORPHED],
        ] as $type => [$define, $expected]) {
            yield $type => [
                static function (Registry $registry) use ($define): void {
                    $post = Post::define();
                    $post->getRelations()->remove('author');
                    self::link($registry, $define(), Author::define(), $post);
                },
                'morphed',
                'parent',
                $expected,
            ];
        }

        yield 'embedded' => [
            static function (Registry $registry): void {
                $composite = Composite::define();
                $composite->getRelations()->set(
                    'embedded',
                    (new RelationDefinition())->setTarget('embedded')->setType('embedded'),
                );
                self::link($registry, $composite);
                $registry->register(EmbeddedEntity::define());
            },
            'composite',
            'embedded',
            Relation::EMBEDDED,
        ];
    }

    /**
     * @param \Closure(Registry): void $define
     */
    #[DataProvider('relations')]
    public function generatesEveryDefaultRelationType(\Closure $define, string $role, string $relation, int $type): void
    {
        $registry = new Registry(Memory::manager());
        $define($registry);

        $schema = (new Compiler())->compile($registry, [new GenerateRelations()]);

        Assert::same($schema[$role][Schema::RELATIONS][$relation][Relation::TYPE], $type);
    }

    public function generatesTheInverseRelation(): void
    {
        $user = User::define();
        $user->getRelations()->get('plain')->setInverse('owner', 'belongsTo');
        $registry = new Registry(Memory::manager());
        self::link($registry, Plain::define(), $user);

        $schema = (new Compiler())->compile($registry, [new GenerateRelations()]);

        Assert::same($schema['plain'][Schema::RELATIONS]['owner'][Relation::TYPE], Relation::BELONGS_TO);
        Assert::same($schema['plain'][Schema::RELATIONS]['owner'][Relation::TARGET], 'user');
    }

    public function rejectsTheInverseOfARelationThatCannotBeInversed(): never
    {
        $user = User::define();
        $user->getRelations()->get('plain')->setType('refersTo')->setInverse('owner', 'hasOne');
        $registry = new Registry(Memory::manager());
        self::link($registry, Plain::define(), $user);

        Expect::exception(SchemaException::class)
            ->withMessage('Unable to inverse relation of type Cycle\Schema\Relation\RefersTo');

        (new GenerateRelations())->run($registry);
    }

    public function rejectsTheInverseOfAScopedHasMany(): never
    {
        $user = User::define();
        $user->getRelations()->get('plain')->setType('hasMany')->setInverse('owner', 'belongsTo');
        $user->getRelations()->get('plain')->getOptions()->set('where', ['active' => true]);
        $registry = new Registry(Memory::manager());
        self::link($registry, Plain::define(), $user);

        Expect::exception(SchemaException::class)
            ->withMessage('Unable to inverse relation `user`.`plain`')
            ->withPrevious(RelationException::class, static fn(ExpectedException $previous) => $previous
                ->withMessage('Unable to inverse HasMany relation with where scope.'));

        (new GenerateRelations())->run($registry);
    }

    public function rejectsTheInverseOfAScopedManyToMany(): never
    {
        $post = Post::define();
        $post->getRelations()->remove('author');
        $post->getRelations()->set('tags', (new RelationDefinition())->setType('manyToMany')->setTarget('tag'));
        $post->getRelations()->get('tags')->setInverse('posts', 'manyToMany');
        $post->getRelations()->get('tags')->getOptions()
            ->set('though', 'tagContext')
            ->set('where', ['active' => true]);
        $registry = new Registry(Memory::manager());
        self::link($registry, $post, Tag::define(), TagContext::define());

        Expect::exception(SchemaException::class)
            ->withMessage('Unable to inverse relation `post`.`tags`')
            ->withPrevious(RelationException::class, static fn(ExpectedException $previous) => $previous
                ->withMessage('Unable to inverse ManyToMany relation with where scope.'));

        (new GenerateRelations())->run($registry);
    }

    public function requiresAnEntityImplementingTheMorphedInterface(): never
    {
        $registry = new Registry(Memory::manager());
        self::link($registry, MorphedTo::define());

        Expect::exception(SchemaException::class)
            ->withMessage('Unable to compute relation `morphed`.`parent`')
            ->withPrevious(RelationException::class, static fn(ExpectedException $previous) => $previous
                ->withMessage('Unable to find morphed parent.'));

        (new GenerateRelations())->run($registry);
    }

    public function keepsAMorphFieldTheEntityDefines(): void
    {
        $morphed = MorphedTo::define();
        $morphed->getFields()->set('parent_role', (new Field())->setType('string(64)')->setColumn('parent_kind'));
        $post = Post::define();
        $post->getRelations()->remove('author');
        $registry = new Registry(Memory::manager());
        self::link($registry, $morphed, Author::define(), $post);

        $schema = (new Compiler())->compile($registry, [new GenerateRelations()]);

        Assert::same($schema['morphed'][Schema::COLUMNS]['parent_role'], 'parent_kind');
        Assert::same($morphed->getFields()->get('parent_role')->getType(), 'string(64)');
    }

    #[DataSet([new \stdClass(), 'stdClass'], 'object')]
    #[DataSet(['hasOne', 'string'], 'string')]
    public function rejectsARelationTypeThatIsNotARelation(mixed $relation, string $given): never
    {
        Expect::exception(\InvalidArgumentException::class)
            ->withMessage(\sprintf('Invalid relation type, RelationInterface excepted, `%s` given', $given));

        new GenerateRelations(['custom' => $relation]);
    }

    private static function link(Registry $registry, Entity ...$entities): void
    {
        foreach ($entities as $entity) {
            $registry->register($entity)->linkTable($entity, 'default', (string) $entity->getRole());
        }
    }
}
