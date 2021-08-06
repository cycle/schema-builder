<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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

abstract class HasOneRelationCompositePKTest extends BaseTest
{
    public function testGenerate(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);

        $this->assertInstanceOf(HasOne::class, $r->getRelation($u, 'plain'));
    }

    public function testPackSchema(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);
        $schema = $c->compile($r);

        $this->assertArrayHasKey('user', $schema);
        $this->assertSame(Relation::HAS_ONE, $schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE]);

        $this->assertArrayHasKey('plain', $schema['user'][Schema::RELATIONS]);

        $this->assertArrayHasKey('plain', $schema);
        $this->assertArrayHasKey('user_id', $schema['plain'][Schema::COLUMNS]);
    }

    public function testPackSchemaEagerLoad(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->getOptions()->set('load', 'eager');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);

        $schema = $c->compile($r);

        $this->assertArrayHasKey('user', $schema);
        $this->assertSame(Relation::HAS_ONE, $schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE]);

        $this->assertSame(Relation::LOAD_EAGER, $schema['user'][Schema::RELATIONS]['plain'][Relation::LOAD]);

        $this->assertArrayHasKey('plain', $schema['user'][Schema::RELATIONS]);

        $this->assertArrayHasKey('plain', $schema);
        $this->assertArrayHasKey('user_id', $schema['plain'][Schema::COLUMNS]);
    }

    public function testPackSchemaLazyLoad(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->getOptions()->set('load', 'lazy');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);

        $schema = $c->compile($r);

        $this->assertArrayHasKey('user', $schema);
        $this->assertSame(Relation::HAS_ONE, $schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE]);

        $this->assertSame(Relation::LOAD_PROMISE, $schema['user'][Schema::RELATIONS]['plain'][Relation::LOAD]);
        $this->assertArrayHasKey('plain', $schema['user'][Schema::RELATIONS]);

        $this->assertArrayHasKey('plain', $schema);
        $this->assertArrayHasKey('user_id', $schema['plain'][Schema::COLUMNS]);
    }

    public function testInconsistentAmountOfPKsShouldThrowAndException(): void
    {
        $this->expectException(RegistryException::class);
        $this->expectErrorMessage('Inconsistent amount of primary fields. '
            . 'Source entity `user` - PKs `id`, `slug`. Target entity `plain` - PKs `parent_id`.');

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', ['parent_id']);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()])
        ]);
    }

    public function testCustomKey(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', ['parent_id', 'parent_slug']);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()])
        ]);

        $this->assertArrayHasKey('user', $schema);
        $this->assertArrayHasKey('plain', $schema['user'][Schema::RELATIONS]);

        $this->assertArrayHasKey('plain', $schema);
        $this->assertArrayHasKey('parent_id', $schema['plain'][Schema::COLUMNS]);
        $this->assertArrayHasKey('parent_slug', $schema['plain'][Schema::COLUMNS]);
    }

    public function testRenderTable(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()]),
            $t = new RenderTables(),
            new RenderRelations()
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasForeignKey(['user_id', 'user_slug']));
    }

    public function testGeneratorFlow(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new ValidateEntities(),
            new ResetTables(),
            new GenerateRelations(['hasOne' => new HasOne()]),
            $t = new RenderTables(),
            new RenderRelations(),
            new SyncTables()
        ]);

        $table = $this->getDriver()->getSchema('plain');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasForeignKey(['user_id', 'user_slug']));
    }

    public function testRenderTableRedefined(): void
    {
        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', ['parent_id', 'parent_slug']);
        $u->getRelations()->get('plain')->getOptions()->set('fkCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasOne' => new HasOne()]),
            $t = new RenderTables(),
            new RenderRelations()
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertTrue($table->hasColumn('parent_slug'));
        $this->assertFalse($table->hasForeignKey(['parent_id', 'parent_slug']));
    }

    public function testInverseInvalidType(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setInverse('user', 'manyToMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $this->expectException(SchemaException::class);

        (new GenerateRelations([
            'hasOne'     => new HasOne(),
            'manyToMany' => new ManyToMany()
        ]))->run($r);
    }

    public function testInverseToBelongsTo(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setInverse('user', 'belongsTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasOne'    => new HasOne(),
            'belongsTo' => new BelongsTo()
        ]))->run($r);
        $schema = $c->compile($r);

        $this->assertArrayHasKey('user', $schema['plain'][Schema::RELATIONS]);
        $this->assertSame(Relation::BELONGS_TO, $schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE]);

        $this->assertSame(
            'user',
            $schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET]
        );

        $this->assertSame(
            ['id', 'slug'],
            $schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY]
        );

        $this->assertSame(
            ['user_id', 'user_slug'],
            $schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY]
        );
    }

    public function testInverseToBelongsLoadEager(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setInverse('user', 'belongsTo', Relation::LOAD_EAGER);
        $u->getRelations()->get('plain')->getOptions()->set('load', 'lazy');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasOne'    => new HasOne(),
            'belongsTo' => new BelongsTo()
        ]))->run($r);
        $schema = $c->compile($r);

        $this->assertArrayHasKey('user', $schema['plain'][Schema::RELATIONS]);
        $this->assertSame(Relation::BELONGS_TO, $schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE]);
        $this->assertSame(Relation::LOAD_EAGER, $schema['plain'][Schema::RELATIONS]['user'][Relation::LOAD]);

        $this->assertSame(
            'user',
            $schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET]
        );

        $this->assertSame(
            ['id', 'slug'],
            $schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY]
        );

        $this->assertSame(
            ['user_id', 'user_slug'],
            $schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY]
        );
    }

    public function testInverseToRefersTo(): void
    {
        $c = new Compiler();

        $e = Plain::defineCompositePK();
        $u = User::defineCompositePK();

        $u->getRelations()->get('plain')->setInverse('user', 'refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations([
            'hasOne'   => new HasOne(),
            'refersTo' => new RefersTo()
        ]))->run($r);
        $schema = $c->compile($r);

        $this->assertArrayHasKey('user', $schema['plain'][Schema::RELATIONS]);
        $this->assertSame(Relation::REFERS_TO, $schema['plain'][Schema::RELATIONS]['user'][Relation::TYPE]);

        $this->assertSame(
            'user',
            $schema['plain'][Schema::RELATIONS]['user'][Relation::TARGET]
        );

        $this->assertSame(
            ['id', 'slug'],
            $schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::OUTER_KEY]
        );

        $this->assertSame(
            ['user_id', 'user_slug'],
            $schema['plain'][Schema::RELATIONS]['user'][Relation::SCHEMA][Relation::INNER_KEY]
        );
    }
}
