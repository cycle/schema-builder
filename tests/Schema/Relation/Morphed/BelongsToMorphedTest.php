<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Relation\Morphed;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Morphed\BelongsToMorphed;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\In2;
use Cycle\Schema\Tests\Fixtures\MorphedTo;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\Tag;

abstract class BelongsToMorphedTest extends BaseTest
{
    public function testGenerate()
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = Post::define();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]))->run($r);

        $this->assertInstanceOf(BelongsToMorphed::class, $r->getRelation($e, 'parent'));
    }

    /**
     * @expectedException \Cycle\ORM\Exception\SchemaException
     */
    public function testGenerateInconsistentType()
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = Tag::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'tag');

        (new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]))->run($r);

        $this->assertInstanceOf(BelongsToMorphed::class, $r->getRelation($e, 'parent'));
    }

    /**
     * @expectedException \Cycle\ORM\Exception\SchemaException
     */
    public function testGenerateInconsistentName()
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = In2::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'in2');

        (new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]))->run($r);

        $this->assertInstanceOf(BelongsToMorphed::class, $r->getRelation($e, 'parent'));
    }

    public function testPackSchema()
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = Post::define();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()])
        ]);

        $this->assertArrayHasKey('morphed', $schema);
        $this->assertSame(
            Relation::BELONGS_TO_MORPHED,
            $schema['morphed'][Schema::RELATIONS]['parent'][Relation::TYPE]
        );

        $this->assertArrayHasKey('morphed', $schema);
        $this->assertArrayHasKey('parent_id', $schema['morphed'][Schema::COLUMNS]);
        $this->assertArrayHasKey('parent_role', $schema['morphed'][Schema::COLUMNS]);
    }

    public function testRenderTable()
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = Post::define();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]),
            $t = new RenderTables(),
            new RenderRelations()
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('morphed');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertTrue($table->hasColumn('parent_role'));

        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertTrue($table->column('parent_role')->getType() == "string");
        $this->assertTrue($table->column('parent_role')->getSize() == 32);

        $this->assertTrue($table->hasIndex(['parent_id', 'parent_role']));
    }
}