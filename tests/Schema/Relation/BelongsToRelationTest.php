<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Relation;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Generator\ResolveInterfaces;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Relation\HasMany;
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Relation\ManyToMany;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Post;

abstract class BelongsToRelationTest extends BaseTest
{
    #[\Testo\Test]
    public function testGenerate(): void
    {
        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['belongsTo' => new BelongsTo()]))->run($r);

        \Testo\Assert::instanceOf($r->getRelation($e, 'author'), BelongsTo::class);
    }

    #[\Testo\Test]
    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Entity `post` must have defined primary key');

        $e = Post::defineWithoutPK();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['belongsTo' => new BelongsTo()]))->run($r);
    }

    #[\Testo\Test]
    public function testThrowAnExceptionWhenPkNotDefinedInTarget(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Entity `author` must have defined primary key');

        $e = Post::define();
        $u = Author::defineWithoutPK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['belongsTo' => new BelongsTo()]))->run($r);
    }

    #[\Testo\Test]
    public function testPackSchema(): void
    {
        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);

        \Testo\Assert::array($schema)->hasKeys('post');
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['author'][Relation::TYPE], Relation::BELONGS_TO);

        \Testo\Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        \Testo\Assert::array($schema)->hasKeys('author');
        \Testo\Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('author_p_id');
    }

    #[\Testo\Test]
    public function testCustomKey(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->getOptions()->set('innerKey', 'parent_id');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);

        \Testo\Assert::array($schema)->hasKeys('post');
        \Testo\Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        \Testo\Assert::array($schema)->hasKeys('author');
        \Testo\Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('parent_id');
    }

    #[\Testo\Test]
    public function testRenderTable(): void
    {
        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasForeignKey(['author_p_id']));
    }

    #[\Testo\Test]
    public function testRenderTableRedefined(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->getOptions()->set('innerKey', 'parent_id');
        $e->getRelations()->get('author')->getOptions()->set('fkCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasColumn('parent_id'));
        \Testo\Assert::false($table->hasForeignKey(['parent_id']));
    }

    #[\Testo\Test]
    public function testInverseUnknownType(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setInverse('posts', 'hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        \Testo\Expect::exception(RegistryException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);
    }

    #[\Testo\Test]
    public function testInverseInvalidType(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setInverse('posts', 'manyToMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        \Testo\Expect::exception(\Cycle\Schema\Exception\SchemaException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsTo' => new BelongsTo(),
                'manyToMany' => new ManyToMany(),
            ]),
        ]);
    }

    #[\Testo\Test]
    public function testInverseToHasOne(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setInverse('post', 'hasOne');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsTo' => new BelongsTo(),
                'hasOne' => new HasOne(),
            ]),
        ]);

        \Testo\Assert::array($schema['author'][Schema::RELATIONS])->hasKeys('post');
        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::TYPE], Relation::HAS_ONE);

        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::TARGET], 'post');

        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::OUTER_KEY], 'author_p_id');

        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);
    }

    #[\Testo\Test]
    public function testInverseToHasMany(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setInverse('post', 'hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsTo' => new BelongsTo(),
                'hasMany' => new HasMany(),
            ]),
        ]);

        \Testo\Assert::array($schema['author'][Schema::RELATIONS])->hasKeys('post');
        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::TYPE], Relation::HAS_MANY);

        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::TARGET], 'post');

        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::OUTER_KEY], 'author_p_id');

        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);
    }

    #[\Testo\Test]
    public function testRenderWithIndex(): void
    {
        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        \Testo\Assert::true($table->hasIndex(['author_p_id']));
    }
}
