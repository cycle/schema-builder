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
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Relation\Morphed\BelongsToMorphed;
use Cycle\Schema\Relation\Morphed\MorphedHasMany;
use Cycle\Schema\Relation\Morphed\MorphedHasOne;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\In2;
use Cycle\Schema\Tests\Fixtures\MorphedTo;
use Cycle\Schema\Tests\Fixtures\Post;

abstract class BelongsToMorphedRelationCompositePKTest extends BaseTest
{
    #[\Testo\Test]
    public function testGenerate(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]))->run($r);

        \Testo\Assert::instanceOf($r->getRelation($e, 'parent'), BelongsToMorphed::class);
    }

    #[\Testo\Test]
    public function testGenerateInconsistentName(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = In2::defineCompositePK();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');
        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'in2');

        \Testo\Expect::exception(SchemaException::class);

        (new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]))->run($r);
    }

    #[\Testo\Test]
    public function testPackSchema(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]),
        ]);

        \Testo\Assert::array($schema)->hasKeys('morphed');
        \Testo\Assert::same($schema['morphed'][Schema::RELATIONS]['parent'][Relation::TYPE], Relation::BELONGS_TO_MORPHED);

        \Testo\Assert::array($schema)->hasKeys('morphed');
        \Testo\Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_p_slug');
        \Testo\Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_p_id');
        \Testo\Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_role');
    }

    #[\Testo\Test]
    public function testRenderTable(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        (new Compiler())->compile($r, [
            new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('morphed');
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasColumn('parent_p_id'));
        \Testo\Assert::true($table->hasColumn('parent_p_slug'));
        \Testo\Assert::true($table->hasColumn('parent_role'));

        \Testo\Assert::true($table->hasColumn('parent_p_id'));
        \Testo\Assert::true($table->column('parent_role')->getType() == 'string');
        \Testo\Assert::true($table->column('parent_role')->getSize() == 32);

        \Testo\Assert::true($table->hasIndex(['parent_p_id', 'parent_p_slug', 'parent_role']));
    }

    #[\Testo\Test]
    public function testInverseToInvalidType(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');
        $e->getRelations()->get('parent')->setInverse('morphed', 'hasOne');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        \Testo\Expect::exception(SchemaException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsToMorphed' => new BelongsToMorphed(),
                'hasOne' => new HasOne(),
            ]),
        ]);
    }

    #[\Testo\Test]
    public function testInverseHasOne(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');
        $e->getRelations()->get('parent')->setInverse('morphed', 'morphedHasOne');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsToMorphed' => new BelongsToMorphed(),
                'morphedHasOne' => new MorphedHasOne(),
            ]),
        ]);

        \Testo\Assert::array($schema['author'][Schema::RELATIONS])->hasKeys('morphed');
        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::TYPE], Relation::MORPHED_HAS_ONE);
        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id', 'p_slug']);
        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY], 'parent_role');

        \Testo\Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('morphed');
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::TYPE], Relation::MORPHED_HAS_ONE);
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id', 'p_slug']);
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::OUTER_KEY], ['parent_p_id', 'parent_p_slug']);
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY], 'parent_role');
    }

    #[\Testo\Test]
    public function testInverseHasMany(): void
    {
        $e = MorphedTo::define();
        $a = Author::defineCompositePK();
        $p = Post::defineCompositePK();

        $p->getRelations()->remove('author');
        $e->getRelations()->get('parent')->setInverse('morphed', 'morphedHasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsToMorphed' => new BelongsToMorphed(),
                'morphedHasMany' => new MorphedHasMany(),
            ]),
        ]);

        \Testo\Assert::array($schema['author'][Schema::RELATIONS])->hasKeys('morphed');
        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::TYPE], Relation::MORPHED_HAS_MANY);
        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id', 'p_slug']);
        \Testo\Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY], 'parent_role');

        \Testo\Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('morphed');
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::TYPE], Relation::MORPHED_HAS_MANY);
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id', 'p_slug']);
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY], 'parent_role');
    }
}
