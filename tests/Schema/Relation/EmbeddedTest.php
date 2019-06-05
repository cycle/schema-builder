<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\Schema\Tests\Relation;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Relation;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Embedded;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Composite;
use Cycle\Schema\Tests\Fixtures\EmbeddedEntity;

abstract class EmbeddedTest extends BaseTest
{
    public function testGenerate()
    {
        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded')
        );

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new GenerateRelations(['embedded' => new Embedded()]))->run($r);

        $this->assertInstanceOf(Embedded::class, $r->getRelation($c, 'embedded'));
    }

    public function testPackSchema()
    {
        $cc = new Compiler();

        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded')
        );

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new GenerateRelations(['embedded' => new Embedded()]))->run($r);
        $schema = $cc->compile($r);

        $this->assertArrayHasKey('composite', $schema);
        $this->assertArrayHasKey('embedded', $schema['composite'][Schema::RELATIONS]);
        $this->assertSame(
            \Cycle\ORM\Relation::EMBEDDED,
            $schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::TYPE]
        );

        $this->assertSame(
            \Cycle\ORM\Relation::LOAD_EAGER,
            $schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::LOAD]
        );

        $this->assertArrayHasKey('composite.embedded', $schema);
        $this->assertSame('id', $schema['composite.embedded'][Schema::PRIMARY_KEY]);
        $this->assertSame('default', $schema['composite.embedded'][Schema::DATABASE]);
        $this->assertSame('composite', $schema['composite.embedded'][Schema::TABLE]);

        $this->assertSame([
            'embedded' => 'embedded_column',
            'id'       => 'id'
        ], $schema['composite.embedded'][Schema::COLUMNS]);
    }

    public function testPackSchemaLazyLoad()
    {
        $cc = new Compiler();

        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded')
        );

        $c->getRelations()->get('embedded')->getOptions()->set('load', 'lazy');

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new GenerateRelations(['embedded' => new Embedded()]))->run($r);
        $schema = $cc->compile($r);

        $this->assertArrayHasKey('composite', $schema);
        $this->assertArrayHasKey('embedded', $schema['composite'][Schema::RELATIONS]);
        $this->assertSame(
            \Cycle\ORM\Relation::EMBEDDED,
            $schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::TYPE]
        );

        $this->assertSame(
            \Cycle\ORM\Relation::LOAD_PROMISE,
            $schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::LOAD]
        );

        $this->assertArrayHasKey('composite.embedded', $schema);
        $this->assertSame('id', $schema['composite.embedded'][Schema::PRIMARY_KEY]);
        $this->assertSame('default', $schema['composite.embedded'][Schema::DATABASE]);
        $this->assertSame('composite', $schema['composite.embedded'][Schema::TABLE]);

        $this->assertSame([
            'embedded' => 'embedded_column',
            'id'       => 'id'
        ], $schema['composite.embedded'][Schema::COLUMNS]);
    }

    public function testRenderTable()
    {
        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded')
        );

        $c->getRelations()->get('embedded')->getOptions()->set('load', 'lazy');

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new Compiler())->compile($r, [
            new GenerateRelations(['embedded' => new Embedded()]),
            $t = new RenderTables(),
            new RenderRelations()
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('composite');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('embedded_column'));
    }
}