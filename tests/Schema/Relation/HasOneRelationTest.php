<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Relation;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Generator\ResetTables;
use Cycle\Schema\Generator\SyncTables;
use Cycle\Schema\Generator\ValidateEntities;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Relation\ManyToMany;
use Cycle\Schema\Relation\RefersTo;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;

abstract class HasOneRelationTest extends BaseTest
{
    #[\Testo\Test]
    public function testGenerate(): void
    {
        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);

        \Testo\Assert::instanceOf($r->getRelation($u, 'plain'), HasOne::class);
    }

    #[\Testo\Test]
    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Entity `plain` must have defined primary key');

        $e = Plain::defineWithoutPK();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);
    }

    #[\Testo\Test]
    public function testThrowAnExceptionWhenPkNotDefinedInTarget(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Entity `user` must have defined primary key');

        $e = Plain::define();
        $u = User::defineWithoutPK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);
    }

    #[\Testo\Test]
    public function testPackSchema(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);
        $schema = $c->compile($r);

        \Testo\Assert::array($schema)->hasKeys('user');
        \Testo\Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE], Relation::HAS_ONE);

        \Testo\Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        \Testo\Assert::array($schema)->hasKeys('plain');
        \Testo\Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('user_p_id');
    }

    #[\Testo\Test]
    public function testPackSchemaEagerLoad(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->getOptions()->set('load', 'eager');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);

        $schema = $c->compile($r);

        \Testo\Assert::array($schema)->hasKeys('user');
        \Testo\Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE], Relation::HAS_ONE);

        \Testo\Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::LOAD], Relation::LOAD_EAGER);

        \Testo\Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        \Testo\Assert::array($schema)->hasKeys('plain');
        \Testo\Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('user_p_id');
    }

    #[\Testo\Test]
    public function testPackSchemaLazyLoad(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->getOptions()->set('load', 'lazy');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);

        $schema = $c->compile($r);

        \Testo\Assert::array($schema)->hasKeys('user');
        \Testo\Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE], Relation::HAS_ONE);

        \Testo\Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::LOAD], Relation::LOAD_PROMISE);
        \Testo\Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        \Testo\Assert::array($schema)->hasKeys('plain');
        \Testo\Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('user_p_id');
    }

    #[\Testo\Test]
    public function testCustomKey(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', 'parent_id');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()]),
        ]);

        \Testo\Assert::array($schema)->hasKeys('user');
        \Testo\Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        \Testo\Assert::array($schema)->hasKeys('plain');
        \Testo\Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('parent_id');
    }

    #[\Testo\Test]
    public function testRenderTable(): void
    {
        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasForeignKey(['user_p_id']));
    }

    #[\Testo\Test]
    public function testGeneratorFlow(): void
    {
        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new ValidateEntities(),
            new ResetTables(),
            new GenerateRelations(['hasOne' => new HasOne()]),
            $t = new RenderTables(),
            new RenderRelations(),
            new SyncTables(),
        ]);

        $table = $this->getDriver()->getSchema('plain');
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasForeignKey(['user_p_id']));
    }

    #[\Testo\Test]
    public function testRenderTableRedefined(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', 'parent_id');
        $u->getRelations()->get('plain')->getOptions()->set('fkCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasColumn('parent_id'));
        \Testo\Assert::false($table->hasForeignKey(['parent_id']));
    }

    #[\Testo\Test]
    public function testInverseInvalidType(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setInverse('user', 'manyToMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        \Testo\Expect::exception(SchemaException::class);

        (new GenerateRelations([
            'hasOne' => new HasOne(),
            'manyToMany' => new ManyToMany(),
        ]))->run($r);
    }

    #[\Testo\Test]
    public function testInverseToBelongsTo(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setInverse('user', 'belongsTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasOne' => new HasOne(),
            'belongsTo' => new BelongsTo(),
        ]))->run($r);
        $schema = $c->compile($r);

        \Testo\Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::BELONGS_TO);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id']);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], 'user_p_id');
    }

    #[\Testo\Test]
    public function testInverseToBelongsLoadEager(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setInverse('user', 'belongsTo', Relation::LOAD_EAGER);
        $u->getRelations()->get('plain')->getOptions()->set('load', 'lazy');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasOne' => new HasOne(),
            'belongsTo' => new BelongsTo(),
        ]))->run($r);
        $schema = $c->compile($r);

        \Testo\Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::BELONGS_TO);
        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::LOAD], Relation::LOAD_EAGER);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id']);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], 'user_p_id');
    }

    #[\Testo\Test]
    public function testInverseToRefersTo(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setInverse('user', 'refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasOne' => new HasOne(),
            'refersTo' => new RefersTo(),
        ]))->run($r);
        $schema = $c->compile($r);

        \Testo\Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::REFERS_TO);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id']);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], 'user_p_id');
    }

    #[\Testo\Test]
    public function testRenderWithIndex(): void
    {
        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        \Testo\Assert::true($table->hasIndex(['user_p_id']));
    }
}
