<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation;

use Cycle\Database\Schema\AbstractTable;
use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation as RelationDefinition;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Relation\HasMany;
use Cycle\Schema\Relation\ManyToMany;
use Cycle\Schema\Relation\RefersTo;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

final class HasManyRelationTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new HasMany()]))->run($r);

        Assert::instanceOf($r->getRelation($u, 'plain'), HasMany::class);
    }

    #[Test]
    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Entity `plain` must have defined primary key');

        $e = Plain::defineWithoutPK();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new HasMany()]))->run($r);
    }

    #[Test]
    public function testThrowAnExceptionWhenPkNotDefinedInTarget(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Entity `user` must have defined primary key');

        $e = Plain::define();
        $u = User::defineWithoutPK();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new HasMany()]))->run($r);
    }

    #[Test]
    public function testPackSchema(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()
            ->get('plain')
            ->setType('hasMany')
            ->getOptions()->set('collection', ArrayCollectionFactory::class);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new HasMany()]))->run($r);
        $schema = $c->compile($r);

        Assert::array($schema)->hasKeys('user');
        Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE], Relation::HAS_MANY);

        Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        Assert::array($schema)->hasKeys('plain');
        Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('user_p_id');

        Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::SCHEMA][Relation::COLLECTION_TYPE], ArrayCollectionFactory::class);
    }

    #[Test]
    public function testCustomKey(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', 'parent_id');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new HasMany()]),
        ]);

        Assert::array($schema)->hasKeys('user');
        Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        Assert::array($schema)->hasKeys('plain');
        Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('parent_id');
    }

    #[Test]
    public function testRenderTable(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new HasMany()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        Assert::true($table->exists());
        Assert::true($table->hasForeignKey(['user_p_id']));
    }

    #[Test]
    public function testRenderTableRedefined(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', 'parent_id');
        $u->getRelations()->get('plain')->getOptions()->set('fkCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new HasMany()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        Assert::true($table->exists());
        Assert::true($table->hasColumn('parent_id'));
        Assert::false($table->hasForeignKey(['parent_id']));
    }

    #[Test]
    public function testInverseInvalidType(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany')->setInverse('user', 'manyToMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        Expect::exception(SchemaException::class);

        (new GenerateRelations([
            'hasMany' => new HasMany(),
            'manyToMany' => new ManyToMany(),
        ]))->run($r);
    }

    #[Test]
    public function testInverseToBelongsTo(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany')->setInverse('user', 'belongsTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasMany' => new HasMany(),
            'belongsTo' => new BelongsTo(),
        ]))->run($r);
        $schema = $c->compile($r);

        Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::BELONGS_TO);

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id']);

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], 'user_p_id');
    }

    #[Test]
    public function testInverseToRefersTo(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany')->setInverse('user', 'refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasMany' => new HasMany(),
            'refersTo' => new RefersTo(),
        ]))->run($r);
        $schema = $c->compile($r);

        Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::REFERS_TO);

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id']);

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], 'user_p_id');
    }

    #[Test]
    public function testRenderWithIndex(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new HasMany()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        Assert::true($table->hasIndex(['user_p_id']));
    }

    public function belongsToNullableOrders(): iterable
    {
        yield 'hasMany source first' => [true];
        yield 'belongsTo source first' => [false];
    }

    #[Test]
    #[DataProvider('belongsToNullableOrders')]
    public function testNullableBelongsToMakesSharedColumnNullable(bool $userFirst): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');
        $e->getRelations()->set(
            'user',
            (new RelationDefinition())->setTarget('user')->setType('belongsTo'),
        );
        $e->getRelations()->get('user')->getOptions()->set('nullable', true);

        $table = $this->renderPlainWithUser($e, $u, $userFirst);

        Assert::true($table->column('user_p_id')->isNullable());
    }

    #[Test]
    #[DataProvider('belongsToNullableOrders')]
    public function testNullableBelongsToKeepsUserDefinedColumn(bool $userFirst): void
    {
        $e = Plain::define();
        $u = User::define();

        $e->getFields()->set('user_p_id', (new Field())->setType('int')->setColumn('user_p_id'));
        $u->getRelations()->get('plain')->setType('hasMany');
        $e->getRelations()->set(
            'user',
            (new RelationDefinition())->setTarget('user')->setType('belongsTo'),
        );
        $e->getRelations()->get('user')->getOptions()->set('nullable', true);

        $table = $this->renderPlainWithUser($e, $u, $userFirst);

        Assert::false($table->column('user_p_id')->isNullable());
    }

    #[Test]
    #[ExceptDriver(Driver::MySQL)]
    public function testRenderWithoutIndex(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');
        $u->getRelations()->get('plain')->getOptions()->set('indexCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new HasMany()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        Assert::false($table->hasIndex(['user_p_id']));
    }

    private function renderPlainWithUser(Entity $plain, Entity $user, bool $userFirst): AbstractTable
    {
        $r = new Registry($this->dbal);
        $entities = $userFirst ? [[$user, 'user'], [$plain, 'plain']] : [[$plain, 'plain'], [$user, 'user']];
        foreach ($entities as [$entity, $table]) {
            $r->register($entity)->linkTable($entity, 'default', $table);
        }

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new HasMany(), 'belongsTo' => new BelongsTo()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        $t->getReflector()->run();

        return $this->getDriver()->getSchema('plain');
    }
}
