<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Relation;


use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTable;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Post;

abstract class BelongsToRelationTest extends BaseTest
{
    public function testGenerate()
    {
        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['belongsTo' => new BelongsTo()]))->run($r);

        $this->assertInstanceOf(BelongsTo::class, $r->getRelation($e, 'author'));
    }

    public function testPackSchema()
    {
        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);

        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('author', $schema['post'][Schema::RELATIONS]);

        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('author_id', $schema['post'][Schema::COLUMNS]);
    }

    public function testCustomKey()
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->getOptions()->set('innerKey', 'parent_id');

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
    }

    public function testRenderTable()
    {
        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
            $t = new RenderTable(),
            new RenderRelations()
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasForeignKey('author_id'));
    }

    public function testRenderTableRedefined()
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
            $t = new RenderTable(),
            new RenderRelations()
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertFalse($table->hasForeignKey('parent_id'));
    }
}