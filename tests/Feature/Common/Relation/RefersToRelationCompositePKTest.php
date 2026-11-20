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
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class RefersToRelationCompositePKTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
    {
        $e = Post::defineCompositePK();
        $u = Author::defineCompositePK();

        $e->getRelations()->get('author')->setType('refersTo');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        (new GenerateRelations(['refersTo' => new RefersTo()]))->run($r);

        Assert::instanceOf($r->getRelation($e, 'author'), RefersTo::class);
    }

    #[Test]
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

        Assert::array($schema)->hasKeys('post');
        Assert::same($schema['post'][Schema::RELATIONS]['author'][Relation::TYPE], Relation::REFERS_TO);

        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        Assert::array($schema)->hasKeys('author');
        Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('author_p_id');
        Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('author_p_slug');
    }

    #[Test]
    public function testInconsistentAmountOfPKsShouldThrowAndException(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Inconsistent amount of related fields. '
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

    #[Test]
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

        Assert::array($schema)->hasKeys('post');
        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        Assert::array($schema)->hasKeys('author');
        Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('parent_id');
        Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('parent_slug');
    }

    #[Test]
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
        Assert::true($table->exists());
        Assert::true($table->hasForeignKey(['author_p_id', 'author_p_slug']));
    }

    #[Test]
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
        Assert::true($table->exists());
        Assert::true($table->hasColumn('parent_id'));
        Assert::true($table->hasColumn('parent_slug'));
        Assert::false($table->hasForeignKey(['parent_id', 'parent_slug']));
    }
}
