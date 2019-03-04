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
use Cycle\Schema\Generator\RelationGenerator;
use Cycle\Schema\Generator\RelationReflector;
use Cycle\Schema\Generator\TableGenerator;
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

        $r->iterate(new RelationGenerator(['belongsTo' => new BelongsTo()]));

        $this->assertInstanceOf(BelongsTo::class, $r->getRelation($e, 'author'));
    }

    public function testPackSchema()
    {
        $c = new Compiler();

        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $r->iterate(new RelationGenerator(['belongsTo' => new BelongsTo()]));
        $r->iterate($c);

        $schema = $c->getSchema();

        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('author', $schema['post'][Schema::RELATIONS]);

        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('author_id', $schema['post'][Schema::COLUMNS]);
    }

    public function testCustomKey()
    {
        $c = new Compiler();

        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->getOptions()->set('innerKey', 'parent_id');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $r->iterate(new RelationGenerator(['belongsTo' => new BelongsTo()]));
        $r->iterate($c);

        $schema = $c->getSchema();

        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('author', $schema['post'][Schema::RELATIONS]);

        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('parent_id', $schema['post'][Schema::COLUMNS]);
    }

    public function testRenderTable()
    {
        $t = new TableGenerator();
        $l = new RelationReflector();

        $e = Post::define();
        $u = Author::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $r->iterate(new RelationGenerator(['belongsTo' => new BelongsTo()]));
        $r->iterate($t);
        $r->iterate($l);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasForeignKey('author_id'));
    }

    public function testRenderTableRedefined()
    {
        $t = new TableGenerator();
        $l = new RelationReflector();

        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->getOptions()->set('innerKey', 'parent_id');
        $e->getRelations()->get('author')->getOptions()->set('fkCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $r->iterate(new RelationGenerator(['belongsTo' => new BelongsTo()]));
        $r->iterate($t);
        $r->iterate($l);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertFalse($table->hasForeignKey('parent_id'));
    }
}