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
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Relation\Morphed\BelongsToMorphed;
use Cycle\Schema\Relation\Morphed\MorphedHasMany;
use Cycle\Schema\Relation\Morphed\MorphedHasOne;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\In2;
use Cycle\Schema\Tests\Fixtures\MorphedTo;
use Cycle\Schema\Tests\Fixtures\Post;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class BelongsToMorphedRelationTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
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

        Assert::instanceOf($r->getRelation($e, 'parent'), BelongsToMorphed::class);
    }

    #[Test]
    public function testGenerateInconsistentName(): void
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = In2::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'in2');

        Expect::exception(SchemaException::class);

        (new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]))->run($r);
    }

    #[Test]
    public function testPackSchema(): void
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
            new GenerateRelations(['belongsToMorphed' => new BelongsToMorphed()]),
        ]);

        Assert::array($schema)->hasKeys('morphed');
        Assert::same($schema['morphed'][Schema::RELATIONS]['parent'][Relation::TYPE], Relation::BELONGS_TO_MORPHED);

        Assert::array($schema)->hasKeys('morphed');
        Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_p_id');
        Assert::array($schema['morphed'][Schema::COLUMNS])->hasKeys('parent_role');
    }

    #[Test]
    public function testRenderTable(): void
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

    #[Test]
    public function testInverseToInvalidType(): void
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = Post::define();

        $p->getRelations()->remove('author');
        $e->getRelations()->get('parent')->setInverse('morphed', 'hasOne');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'morphed');

        $r->register($a)->linkTable($a, 'default', 'author');
        $r->register($p)->linkTable($p, 'default', 'post');

        Expect::exception(SchemaException::class);

        (new Compiler())->compile($r, [
            new GenerateRelations([
                'belongsToMorphed' => new BelongsToMorphed(),
                'hasOne' => new HasOne(),
            ]),
        ]);
    }

    #[Test]
    public function testInverseHasOne(): void
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = Post::define();

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

        Assert::array($schema['author'][Schema::RELATIONS])->hasKeys('morphed');
        Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::TYPE], Relation::MORPHED_HAS_ONE);
        Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);
        Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY], 'parent_role');

        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('morphed');
        Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::TYPE], Relation::MORPHED_HAS_ONE);
        Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);
        Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY], 'parent_role');
    }

    #[Test]
    public function testInverseHasMany(): void
    {
        $e = MorphedTo::define();
        $a = Author::define();
        $p = Post::define();

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

        Assert::array($schema['author'][Schema::RELATIONS])->hasKeys('morphed');
        Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::TYPE], Relation::MORPHED_HAS_MANY);
        Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);
        Assert::same($schema['author'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY], 'parent_role');

        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('morphed');
        Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::TYPE], Relation::MORPHED_HAS_MANY);
        Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);
        Assert::same($schema['post'][Schema::RELATIONS]['morphed'][Relation::SCHEMA][Relation::MORPH_KEY], 'parent_role');
    }
}
