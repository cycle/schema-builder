<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation;

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
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class HasManyRelationCompositePKTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new HasMany()]))->run($r);

        Assert::instanceOf($r->getRelation($u, 'plain'), HasMany::class);
    }

    #[Test]
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

        Assert::array($schema)->hasKeys('user');
        Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE], Relation::HAS_MANY);

        Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        Assert::array($schema)->hasKeys('plain');
        Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('user_p_id');
    }

    #[Test]
    public function testInconsistentAmountOfPKsShouldThrowAndException(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Inconsistent amount of related fields. '
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

    #[Test]
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

        Assert::array($schema)->hasKeys('user');
        Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        Assert::array($schema)->hasKeys('plain');
        Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('parent_id');
        Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('parent_slug');
    }

    #[Test]
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
        Assert::true($table->exists());
        Assert::true($table->hasForeignKey(['user_p_id', 'user_p_slug']));
    }

    #[Test]
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
        Assert::true($table->exists());
        Assert::true($table->hasColumn('parent_id'));
        Assert::true($table->hasColumn('parent_slug'));
        Assert::false($table->hasForeignKey(['parent_id', 'parent_slug']));
    }

    #[Test]
    public function testInverseInvalidType(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

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

        Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::BELONGS_TO);

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id', 'p_slug']);

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], ['user_p_id', 'user_p_slug']);
    }

    #[Test]
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

        Assert::array($schema['plain'][Schema::RELATIONS])->hasKeys('user');
        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE], Relation::REFERS_TO);

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET], 'user');

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id', 'p_slug']);

        Assert::same($schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY], ['user_p_id', 'user_p_slug']);
    }
}
