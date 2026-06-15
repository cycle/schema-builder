<?php

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
use Cycle\Schema\Relation\Morphed\RefersToMorphed;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\In2;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\RefersToMorphedTo;

abstract class RefersToMorphedRelationCompositePKTest extends BaseTest
{
    public function testGenerate(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new GenerateRelations(['refersToMorphed' => new RefersToMorphed()]))->run($r);

        $this->assertInstanceOf(RefersToMorphed::class, $r->getRelation($e, 'parent'));
    }

    public function testGenerateInconsistentName(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::defineCompositePK();
        $p = In2::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');
        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'in2');

        $this->expectException(SchemaException::class);

        (new GenerateRelations(['refersToMorphed' => new RefersToMorphed()]))->run($r);
    }

    public function testPackSchema(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['refersToMorphed' => new RefersToMorphed()]),
        ]);

        $this->assertArrayHasKey('morphed', $schema);
        $this->assertSame(
            Relation::REFERS_TO_MORPHED,
            $schema['morphed'][Schema::RELATIONS]['parent'][Relation::TYPE],
        );

        $this->assertArrayHasKey('morphed', $schema);
        $this->assertArrayHasKey('parent_p_slug', $schema['morphed'][Schema::COLUMNS]);
        $this->assertArrayHasKey('parent_p_id', $schema['morphed'][Schema::COLUMNS]);
        $this->assertArrayHasKey('parent_role', $schema['morphed'][Schema::COLUMNS]);
    }

    public function testRenderTable(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new Compiler())->compile($r, [
            new GenerateRelations(['refersToMorphed' => new RefersToMorphed()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('morphed');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_p_id'));
        $this->assertTrue($table->hasColumn('parent_p_slug'));
        $this->assertTrue($table->hasColumn('parent_role'));

        $this->assertTrue($table->hasColumn('parent_p_id'));
        $this->assertTrue($table->column('parent_role')->getType() == 'string');
        $this->assertTrue($table->column('parent_role')->getSize() == 32);

        $this->assertTrue($table->hasIndex(['parent_p_id', 'parent_p_slug', 'parent_role']));
    }
}
