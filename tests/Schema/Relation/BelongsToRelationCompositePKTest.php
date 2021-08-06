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

abstract class BelongsToRelationCompositePKTest extends BaseTest
{
    public function testGenerate(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['belongsTo' => new BelongsTo()]))->run($r);

        $this->assertInstanceOf(BelongsTo::class, $r->getRelation($e, 'author'));
    }

    public function testPackSchema(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);

        $this->assertArrayHasKey('post', $schema);
        $this->assertSame(Relation::BELONGS_TO, $schema['post'][Schema::RELATIONS]['author'][Relation::TYPE]);

        $this->assertArrayHasKey('author', $schema['post'][Schema::RELATIONS]);

        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('author_id', $schema['post'][Schema::COLUMNS]);
    }

    public function testInconsistentAmountOfPKsShouldThrowAndException(): void
    {
        $this->expectException(RegistryException::class);
        $this->expectErrorMessage('Inconsistent amount of primary fields. '
            . 'Source entity `author` - PKs `id`, `slug`. Target entity `post` - PKs `parent_id`.');

        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->getOptions()->set('innerKey', ['parent_id']);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);
    }

    public function testCustomKey(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->getOptions()->set('innerKey', ['parent_id', 'parent_slug']);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);

        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('author', $schema['post'][Schema::RELATIONS]);

        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('parent_id', $schema['post'][Schema::COLUMNS]);
        $this->assertArrayHasKey('parent_slug', $schema['post'][Schema::COLUMNS]);
    }

    public function testRenderTable(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
            $t = new RenderTables(),
            new RenderRelations()
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasForeignKey(['author_id', 'author_slug']));
    }

    public function testRenderTableRedefined(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->getOptions()->set('innerKey', ['parent_id', 'parent_slug']);
        $e->getRelations()->get('author')->getOptions()->set('fkCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
            $t = new RenderTables(),
            new RenderRelations()
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertFalse($table->hasForeignKey(['parent_id']));
    }

    public function testInverseUnknownType(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setInverse('posts', 'hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $this->expectException(RegistryException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);
    }

    public function testInverseInvalidType(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setInverse('posts', 'manyToMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $this->expectException(\Cycle\Schema\Exception\SchemaException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsTo'  => new BelongsTo(),
                'manyToMany' => new ManyToMany()
            ])
        ]);
    }

    public function testInverseToHasOne(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setInverse('post', 'hasOne');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsTo' => new BelongsTo(),
                'hasOne'    => new HasOne()
            ])
        ]);

        $this->assertArrayHasKey('post', $schema['author'][Schema::RELATIONS]);
        $this->assertSame(Relation::HAS_ONE, $schema['author'][Schema::RELATIONS]['post'][Relation::TYPE]);

        $this->assertSame(
            'post',
            $schema['author'][Schema::RELATIONS]['post'][Relation::TARGET]
        );

        $this->assertSame(
            ['author_id', 'author_slug'],
            $schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::OUTER_KEY]
        );

        $this->assertSame(
            ['id', 'slug'],
            $schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::INNER_KEY]
        );
    }

    public function testInverseToHasMany(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setInverse('post', 'hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsTo' => new BelongsTo(),
                'hasMany'   => new HasMany()
            ])
        ]);

        $this->assertArrayHasKey('post', $schema['author'][Schema::RELATIONS]);
        $this->assertSame(Relation::HAS_MANY, $schema['author'][Schema::RELATIONS]['post'][Relation::TYPE]);

        $this->assertSame(
            'post',
            $schema['author'][Schema::RELATIONS]['post'][Relation::TARGET]
        );

        $this->assertSame(
            ['author_id', 'author_slug'],
            $schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::OUTER_KEY]
        );

        $this->assertSame(
            ['id', 'slug'],
            $schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][Relation::INNER_KEY]
        );
    }
}
