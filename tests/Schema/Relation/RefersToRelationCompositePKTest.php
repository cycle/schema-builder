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
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\RefersTo;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Post;

abstract class RefersToRelationCompositePKTest extends BaseTest
{
    public function testGenerate(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['refersTo' => new RefersTo()]))->run($r);

        $this->assertInstanceOf(RefersTo::class, $r->getRelation($e, 'author'));
    }

    public function testPackSchema(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
        ]);

        $this->assertArrayHasKey('post', $schema);
        $this->assertSame(Relation::REFERS_TO, $schema['post'][Schema::RELATIONS]['author'][Relation::TYPE]);

        $this->assertArrayHasKey('author', $schema['post'][Schema::RELATIONS]);

        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('author_p_id', $schema['post'][Schema::COLUMNS]);
        $this->assertArrayHasKey('author_p_slug', $schema['post'][Schema::COLUMNS]);
    }

    public function testInconsistentAmountOfPKsShouldThrowAndException(): void
    {
        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage('Inconsistent amount of related fields. '
            . 'Source entity: `author`; keys: `id`, `slug`. Target entity: `post`; keys: `parent_id`.');

        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setType('refersTo');

        $e->getRelations()->get('author')->getOptions()->set('innerKey', ['parent_id']);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
        ]);
    }

    public function testCustomKey(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setType('refersTo');

        $e->getRelations()->get('author')->getOptions()->set('innerKey', ['parent_id', 'parent_slug']);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
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

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasForeignKey(['author_p_id', 'author_p_slug']));
    }

    public function testRenderTableRedefined(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setType('refersTo');

        $e->getRelations()->get('author')->getOptions()->set('innerKey', ['parent_id', 'parent_slug']);
        $e->getRelations()->get('author')->getOptions()->set('fkCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertTrue($table->hasColumn('parent_slug'));
        $this->assertFalse($table->hasForeignKey(['parent_id', 'parent_slug']));
    }
}
