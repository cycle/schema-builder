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
    #[\Testo\Test]
    public function testGenerate(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['refersTo' => new RefersTo()]))->run($r);

        \Testo\Assert::instanceOf($r->getRelation($e, 'author'), RefersTo::class);
    }

    #[\Testo\Test]
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

        \Testo\Assert::array($schema)->hasKeys('post');
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['author'][Relation::TYPE], Relation::REFERS_TO);

        \Testo\Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        \Testo\Assert::array($schema)->hasKeys('author');
        \Testo\Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('author_p_id');
        \Testo\Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('author_p_slug');
    }

    #[\Testo\Test]
    public function testInconsistentAmountOfPKsShouldThrowAndException(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Inconsistent amount of related fields. '
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

    #[\Testo\Test]
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

        \Testo\Assert::array($schema)->hasKeys('post');
        \Testo\Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        \Testo\Assert::array($schema)->hasKeys('author');
        \Testo\Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('parent_id');
        \Testo\Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('parent_slug');
    }

    #[\Testo\Test]
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
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasForeignKey(['author_p_id', 'author_p_slug']));
    }

    #[\Testo\Test]
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
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasColumn('parent_id'));
        \Testo\Assert::true($table->hasColumn('parent_slug'));
        \Testo\Assert::false($table->hasForeignKey(['parent_id', 'parent_slug']));
    }
}
