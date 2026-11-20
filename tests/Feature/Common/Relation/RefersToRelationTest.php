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
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\RefersTo;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class RefersToRelationTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['refersTo' => new RefersTo()]))->run($r);

        Assert::instanceOf($r->getRelation($e, 'author'), RefersTo::class);
    }

    #[Test]
    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Entity `post` must have defined primary key');

        $e = Post::defineWithoutPK();
        $u = Author::define();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['refersTo' => new RefersTo()]))->run($r);
    }

    #[Test]
    public function testThrowAnExceptionWhenPkNotDefinedInTarget(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Entity `author` must have defined primary key');

        $e = Post::define();
        $u = Author::defineWithoutPK();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['refersTo' => new RefersTo()]))->run($r);
    }

    #[Test]
    public function testPackSchema(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
        ]);

        Assert::array($schema)->hasKeys('post');
        Assert::same($schema['post'][Schema::RELATIONS]['author'][Relation::TYPE], Relation::REFERS_TO);

        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        Assert::array($schema)->hasKeys('author');
        Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('author_p_id');
    }

    #[Test]
    public function testCustomKey(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setType('refersTo');

        $e->getRelations()->get('author')->getOptions()->set('innerKey', 'parent_id');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
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
        Assert::true($table->exists());
        Assert::true($table->hasForeignKey(['author_p_id']));
    }

    #[Test]
    public function testRenderTableRedefined(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setType('refersTo');

        $e->getRelations()->get('author')->getOptions()->set('innerKey', 'parent_id');
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
        Assert::true($table->exists());
        Assert::true($table->hasColumn('parent_id'));
        Assert::false($table->hasForeignKey(['parent_id']));
    }

    #[Test]
    public function testRenderWithIndex(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
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

        $e->getRelations()->get('author')->setType('refersTo');
        $e->getRelations()->get('author')->getOptions()->set('indexCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new Compiler())->compile($r, [
            new GenerateRelations(['refersTo' => new RefersTo()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('post');
        Assert::false($table->hasIndex(['author_p_id']));
    }
}
