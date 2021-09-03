<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Tests\Relation\Morphed;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Relation\Morphed\BelongsToMorphed;
use Cycle\Schema\Relation\Morphed\MorphedHasMany;
use Cycle\Schema\Relation\Morphed\MorphedHasOne;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\In2;
use Cycle\Schema\Tests\Fixtures\MorphedTo;
use Cycle\Schema\Tests\Fixtures\Post;

abstract class BelongsToMorphedRelationCompositePKTest extends BaseTest
{
    public function testGenerate(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]))->run($r);

        $this->assertInstanceOf(BelongsToMorphed::class, $r->getRelation($e, 'parent'));
    }

    public function testGenerateInconsistentName(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = In2::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'in2');

        $this->expectException(SchemaException::class);

        (new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]))->run($r);
    }

    public function testPackSchema(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]),
        ]);

        $this->assertArrayHasKey('morphed', $schema);
        $this->assertSame(
            Relation::BELONGS_TO_MORPHED,
            $schema['morphed'][Schema::RELATIONS]['parent'][Relation::TYPE]
        );

        $this->assertArrayHasKey('morphed', $schema);
        $this->assertArrayHasKey('parent_slug', $schema['morphed'][Schema::COLUMNS]);
        $this->assertArrayHasKey('parent_id', $schema['morphed'][Schema::COLUMNS]);
        $this->assertArrayHasKey('parent_role', $schema['morphed'][Schema::COLUMNS]);
    }

    public function testRenderTable(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('morphed');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertTrue($table->hasColumn('parent_slug'));
        $this->assertTrue($table->hasColumn('parent_role'));

        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertTrue($table->column('parent_role')->getType() == 'string');
        $this->assertTrue($table->column('parent_role')->getSize() == 32);

        $this->assertTrue($table->hasIndex(['parent_id', 'parent_slug', 'parent_role']));
    }

    public function testInverseToInvalidType(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');
        $e->getRelations()->get('parent')->setInverse('morphed', 'hasOne');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $this->expectException(SchemaException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsToMorphed' => new BelongsToMorphed(),
                'hasOne' => new HasOne(),
            ]),
        ]);
    }

    public function testInverseHasOne(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');
        $e->getRelations()->get('parent')->setInverse('morphed', 'morphedHasOne');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsToMorphed' => new BelongsToMorphed(),
                'morphedHasOne' => new MorphedHasOne(),
            ]),
        ]);

        $this->assertArrayHasKey('morphed', $schema['author'][Schema::RELATIONS]);
        $this->assertSame(
            Relation::MORPHED_HAS_ONE,
            $schema['author'][Schema::RELATIONS]['morphed'][Relation::TYPE]
        );
        $this->assertSame(
            ['id', 'slug'],
            $schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY]
        );
        $this->assertSame(
            'parent_role',
            $schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY]
        );

        $this->assertArrayHasKey('morphed', $schema['post'][Schema::RELATIONS]);
        $this->assertSame(
            Relation::MORPHED_HAS_ONE,
            $schema['post'][Schema::RELATIONS]['morphed'][Relation::TYPE]
        );
        $this->assertSame(
            ['id', 'slug'],
            $schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY]
        );
        $this->assertSame(
            ['parent_id', 'parent_slug'],
            $schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::OUTER_KEY]
        );
        $this->assertSame(
            'parent_role',
            $schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY]
        );
    }

    public function testInverseHasMany(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');
        $e->getRelations()->get('parent')->setInverse('morphed', 'morphedHasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsToMorphed' => new BelongsToMorphed(),
                'morphedHasMany' => new MorphedHasMany(),
            ]),
        ]);

        $this->assertArrayHasKey('morphed', $schema['author'][Schema::RELATIONS]);
        $this->assertSame(
            Relation::MORPHED_HAS_MANY,
            $schema['author'][Schema::RELATIONS]['morphed'][Relation::TYPE]
        );
        $this->assertSame(
            ['id', 'slug'],
            $schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY]
        );
        $this->assertSame(
            'parent_role',
            $schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY]
        );

        $this->assertArrayHasKey('morphed', $schema['post'][Schema::RELATIONS]);
        $this->assertSame(
            Relation::MORPHED_HAS_MANY,
            $schema['post'][Schema::RELATIONS]['morphed'][Relation::TYPE]
        );
        $this->assertSame(
            ['id', 'slug'],
            $schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY]
        );
        $this->assertSame(
            'parent_role',
            $schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY]
        );
    }
}
