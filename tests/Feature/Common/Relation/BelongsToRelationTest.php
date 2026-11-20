<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation;

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
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class BelongsToRelationTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
    {
        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['belongsTo' => new BelongsTo()]))->run($r);

        Assert::instanceOf($r->getRelation($e, 'author'), BelongsTo::class);
    }

    #[Test]
    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Entity `post` must have defined primary key');

        $e = Post::defineWithoutPK();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['belongsTo' => new BelongsTo()]))->run($r);
    }

    #[Test]
    public function testThrowAnExceptionWhenPkNotDefinedInTarget(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Entity `author` must have defined primary key');

        $e = Post::define();
        $u = Author::defineWithoutPK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['belongsTo' => new BelongsTo()]))->run($r);
    }

    #[Test]
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

        Assert::array($schema)->hasKeys('post');
        Assert::same($schema['post'][Schema::RELATIONS]['author'][Relation::TYPE], Relation::BELONGS_TO);

        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        Assert::array($schema)->hasKeys('author');
        Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('author_p_id');
    }

    #[Test]
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

        Assert::array($schema)->hasKeys('post');
        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        Assert::array($schema)->hasKeys('author');
        Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('parent_id');
    }

    #[Test]
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
        Assert::true($table->exists());
        Assert::true($table->hasForeignKey(['author_p_id']));
    }

    #[Test]
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
        Assert::true($table->exists());
        Assert::true($table->hasColumn('parent_id'));
        Assert::false($table->hasForeignKey(['parent_id']));
    }

    #[Test]
    public function testInverseUnknownType(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setInverse('posts', 'hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        Expect::exception(RegistryException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);
    }

    #[Test]
    public function testInverseInvalidType(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setInverse('posts', 'manyToMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        Expect::exception(\Cycle\Schema\Exception\SchemaException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsTo' => new BelongsTo(),
                'manyToMany' => new ManyToMany(),
            ]),
        ]);
    }

    #[Test]
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

        Assert::array($schema['author'][Schema::RELATIONS])->hasKeys('post');
        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::TYPE], Relation::HAS_ONE);

        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::TARGET], 'post');

        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::OUTER_KEY], 'author_p_id');

        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);
    }

    #[Test]
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

        Assert::array($schema['author'][Schema::RELATIONS])->hasKeys('post');
        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::TYPE], Relation::HAS_MANY);

        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::TARGET], 'post');

        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::OUTER_KEY], 'author_p_id');

        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);
    }

    #[Test]
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
        Assert::true($table->hasIndex(['author_p_id']));
    }

    #[Test]
    #[ExceptDriver(Driver::MySQL)]
    public function testRenderWithoutIndex(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->getOptions()->set('indexCreate', false);

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
        Assert::false($table->hasIndex(['author_p_id']));
    }
}
