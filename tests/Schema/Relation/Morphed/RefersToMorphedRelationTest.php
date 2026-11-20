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

abstract class RefersToMorphedRelationTest extends BaseTest
{
    #[\Testo\Test]
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

        \Testo\Assert::instanceOf($r->getRelation($e, 'parent'), RefersToMorphed::class);
    }

    #[\Testo\Test]
    public function testGenerateInconsistentName(): void
    {
        $e = RefersToMorphedTo::define();
        $a = Author::define();
        $p = In2::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'in2');

        \Testo\Expect::exception(SchemaException::class);

        (new GenerateRelations(['refersToMorphed' => new RefersToMorphed()]))->run($r);
    }

    #[\Testo\Test]
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

        \Testo\Assert::array($schema)->hasKeys('morphed');
        \Testo\Assert::same($schema['morphed'][Schema::RELATIONS]['parent'][Relation::TYPE], Relation::REFERS_TO_MORPHED);

        \Testo\Assert::array($schema)->hasKeys('morphed');
        \Testo\Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_p_id');
        \Testo\Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_role');
    }

    #[\Testo\Test]
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
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasColumn('parent_p_id'));
        \Testo\Assert::true($table->hasColumn('parent_role'));

        \Testo\Assert::true($table->hasColumn('parent_p_id'));
        \Testo\Assert::true($table->column('parent_role')->getType() == 'string');
        \Testo\Assert::true($table->column('parent_role')->getSize() == 32);

        \Testo\Assert::true($table->hasIndex(['parent_p_id', 'parent_role']));
    }
}
