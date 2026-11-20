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
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Relation\HasMany;
use Cycle\Schema\Relation\ManyToMany;
use Cycle\Schema\Relation\RefersTo;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;

abstract class HasManyRelationCompositePKTest extends BaseTest
{
    #[\Testo\Test]
    public function testGenerate(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new HasMany()]))->run($r);

        \Testo\Assert::instanceOf($r->getRelation($u, 'plain'), HasMany::class);
    }

    #[\Testo\Test]
    public function testPackSchema(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new HasMany()]))->run($r);
        $schema = $c->compile($r);

        \Testo\Assert::array($schema)->hasKeys('user');
        \Testo\Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE], Relation::HAS_MANY);

        \Testo\Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        \Testo\Assert::array($schema)->hasKeys('plain');
        \Testo\Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('user_p_id');
    }

    #[\Testo\Test]
    public function testInconsistentAmountOfPKsShouldThrowAndException(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Inconsistent amount of related fields. '
            . 'Source entity: `user`; keys: `id`, `slug`. Target entity: `plain`; keys: `parent_id`.');

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany');

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', ['parent_id']);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new HasMany()]),
        ]);
    }

    #[\Testo\Test]
    public function testCustomKey(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany');

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', ['parent_id', 'parent_slug']);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new HasMany()]),
        ]);

        \Testo\Assert::array($schema)->hasKeys('user');
        \Testo\Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        \Testo\Assert::array($schema)->hasKeys('plain');
        \Testo\Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('parent_id');
        \Testo\Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('parent_slug');
    }

    #[\Testo\Test]
    public function testRenderTable(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

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
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasForeignKey(['user_p_id', 'user_p_slug']));
    }

    #[\Testo\Test]
    public function testRenderTableRedefined(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany');

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', ['parent_id', 'parent_slug']);
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
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasColumn('parent_id'));
        \Testo\Assert::true($table->hasColumn('parent_slug'));
        \Testo\Assert::false($table->hasForeignKey(['parent_id', 'parent_slug']));
    }

    #[\Testo\Test]
    public function testInverseInvalidType(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany')->setInverse('user', 'manyToMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        \Testo\Expect::exception(SchemaException::class);

        (new GenerateRelations([
            'hasMany' => new HasMany(),
            'manyToMany' => new ManyToMany(),
        ]))->run($r);
    }

    #[\Testo\Test]
    public function testInverseToBelongsTo(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany')->setInverse('user', 'belongsTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasMany' => new HasMany(),
            'belongsTo' => new BelongsTo(),
        ]))->run($r);
        $schema = $c->compile($r);

        \Testo\Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::BELONGS_TO);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id', 'p_slug']);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], ['user_p_id', 'user_p_slug']);
    }

    #[\Testo\Test]
    public function testInverseToRefersTo(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany')->setInverse('user', 'refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasMany' => new HasMany(),
            'refersTo' => new RefersTo(),
        ]))->run($r);
        $schema = $c->compile($r);

        \Testo\Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::REFERS_TO);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id', 'p_slug']);

        \Testo\Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], ['user_p_id', 'user_p_slug']);
    }
}
