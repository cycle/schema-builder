<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common;

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Inheritance\JoinedTable;
use Cycle\Schema\Definition\Inheritance\SingleTable;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Exception\SchemaModifierException;
use Cycle\Schema\Exception\TableInheritance\DiscriminatorColumnNotPresentException;
use Cycle\Schema\Generator\GenerateModifiers;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderModifiers;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Generator\SyncTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class CompilerTest extends BaseTest
{
    public function appliesARelationAddedAsSchemaModifier(): void
    {
        $user = User::define();
        $user->getRelations()->remove('plain');
        $plain = Plain::define();
        $registry = new Registry($this->dbal);
        $registry->register($user)->linkTable($user, 'default', 'users');
        $registry->register($plain)->linkTable($plain, 'default', 'plains');

        $options = new OptionSchema(GenerateRelations::OPTION_MAP);
        $user->addSchemaModifier((new HasOne())->withContext('plain', 'user', 'plain', $options));

        $schema = (new Compiler())->compile($registry, [
            new GenerateModifiers(),
            new RenderTables(),
            new RenderModifiers(),
            new SyncTables(),
        ]);

        Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE], Relation::HAS_ONE);
        Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::SCHEMA][Relation::OUTER_KEY], 'user_p_id');
        Assert::true($this->dbal->database('default')->table('plains')->hasColumn('user_p_id'));
    }

    public function wrapsAFailureOfASchemaModifier(): never
    {
        $user = User::define();
        $user->getRelations()->remove('plain');
        $user->addSchemaModifier(new BrokenSchemaModifier(BrokenSchemaModifier::class . '::modifySchema'));
        $registry = new Registry($this->dbal);
        $registry->register($user)->linkTable($user, 'default', 'users');

        Expect::exception(SchemaModifierException::class)
            ->withMessage(\sprintf(
                'Unable to apply schema modifier `%s` for the `user` role. Something went wrong',
                BrokenSchemaModifier::class,
            ));

        (new Compiler())->compile($registry);
    }

    public function wrapsAFailureOfASchemaModifierWhileComputing(): never
    {
        $user = User::define();
        $user->getRelations()->remove('plain');
        $user->addSchemaModifier(new BrokenSchemaModifier(BrokenSchemaModifier::class . '::compute'));
        $registry = new Registry($this->dbal);
        $registry->register($user)->linkTable($user, 'default', 'users');

        Expect::exception(SchemaException::class)
            ->withMessage(\sprintf('Unable to compute modifier `%s` for the `user` role.', BrokenSchemaModifier::class))
            ->withPrevious(SchemaModifierException::class);

        (new Compiler())->compile($registry, [new GenerateModifiers()]);
    }

    public function pointsAJoinedTableChildAtItsParent(): void
    {
        $user = User::define();
        $user->getRelations()->remove('plain');
        $author = Author::define();
        $author->setInheritance(new JoinedTable($user));
        $registry = new Registry($this->dbal);
        $registry->register($user)->linkTable($user, 'default', 'users');
        $registry->register($author)->linkTable($author, 'default', 'authors');

        $schema = (new Compiler())->compile($registry, [new RenderTables(), new SyncTables()]);

        Assert::same($schema['author'][Schema::PARENT], 'user');
        Assert::true($this->dbal->database('default')->hasTable('authors'));
    }

    public function requiresTheDiscriminatorOfASingleTable(): void
    {
        $user = new Entity();
        $user->setRole('user')->setClass(User::class)->setInheritance(new SingleTable());
        $user->getFields()->set('id', (new Field())->setType('primary')->setColumn('id'));
        $user->getFields()->set('name', (new Field())->setType('string')->setColumn('name'));
        $registry = new Registry($this->dbal);
        $registry->register($user)->linkTable($user, 'default', 'users');

        try {
            (new Compiler())->compile($registry, [new RenderTables(), new SyncTables()]);
            Assert::fail('The single table without a discriminator was compiled.');
        } catch (DiscriminatorColumnNotPresentException $e) {
            Assert::string($e->getSolution())->contains('one of the defined fields of the `user` role: `id`, `name`');
        }
    }
}
