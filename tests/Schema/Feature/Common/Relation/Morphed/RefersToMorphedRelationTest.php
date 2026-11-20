<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation\Morphed;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Morphed\RefersToMorphed;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\In2;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\RefersToMorphedTo;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class RefersToMorphedRelationTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::define();
        $p = Post::define();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new GenerateRelations(['refersToMorphed' => new RefersToMorphed()]))->run($r);

        Assert::instanceOf($r->getRelation($e, 'parent'), RefersToMorphed::class);
    }

    #[Test]
    public function testGenerateInconsistentName(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::define();
        $p = In2::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'in2');

        Expect::exception(SchemaException::class);

        (new GenerateRelations(['refersToMorphed' => new RefersToMorphed()]))->run($r);
    }

    #[Test]
    public function testPackSchema(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::define();
        $p = Post::define();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['refersToMorphed' => new RefersToMorphed()]),
        ]);

        Assert::array($schema)->hasKeys('morphed');
        Assert::same($schema['morphed'][Schema::RELATIONS]['parent'][Relation::TYPE], Relation::REFERS_TO_MORPHED);

        Assert::array($schema)->hasKeys('morphed');
        Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_p_id');
        Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_role');
    }

    #[Test]
    public function testRenderTable(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::define();
        $p = Post::define();

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
        Assert::true($table->exists());
        Assert::true($table->hasColumn('parent_p_id'));
        Assert::true($table->hasColumn('parent_role'));

        Assert::true($table->hasColumn('parent_p_id'));
        Assert::true($table->column('parent_role')->getType() == 'string');
        Assert::true($table->column('parent_role')->getSize() == 32);

        Assert::true($table->hasIndex(['parent_p_id', 'parent_role']));
    }
}
