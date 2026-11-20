<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Relation;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation;
use Cycle\Schema\Exception\FieldException\EmbeddedPrimaryKeyException;
use Cycle\Schema\Exception\RegistryException;
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
    #[\Testo\Test]
    public function testGenerate(): void
    {
        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded'),
        );

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new GenerateRelations(['embedded' => new Embedded()]))->run($r);

        \Testo\Assert::instanceOf($r->getRelation($c, 'embedded'), Embedded::class);
    }

    #[\Testo\Test]
    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Entity `composite` must have defined primary key');

        $c = Composite::defineWithoutPk();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded'),
        );

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new GenerateRelations(['embedded' => new Embedded()]))->run($r);
    }

    #[\Testo\Test]
    public function testPackSchema(): void
    {
        $cc = new Compiler();

        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded'),
        );

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new GenerateRelations(['embedded' => new Embedded()]))->run($r);
        $schema = $cc->compile($r);

        \Testo\Assert::array($schema)->hasKeys('composite');
        \Testo\Assert::array($schema['composite'][Schema::RELATIONS])->hasKeys('embedded');
        \Testo\Assert::same($schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::TYPE], \Cycle\ORM\Relation::EMBEDDED);

        \Testo\Assert::same($schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::LOAD], \Cycle\ORM\Relation::LOAD_EAGER);

        \Testo\Assert::array($schema)->hasKeys('composite:embedded:embedded');
        \Testo\Assert::same($schema['composite:embedded:embedded'][Schema::PRIMARY_KEY], ['p_id']);
        \Testo\Assert::same($schema['composite:embedded:embedded'][Schema::DATABASE], 'default');
        \Testo\Assert::same($schema['composite:embedded:embedded'][Schema::TABLE], 'composite');

        \Testo\Assert::same($schema['composite:embedded:embedded'][Schema::COLUMNS], [
            'p_embedded' => 'embedded_column',
            'p_id' => 'id',
        ]);
    }

    #[\Testo\Test]
    public function testPackSchemaLazyLoad(): void
    {
        $cc = new Compiler();

        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded'),
        );

        $c->getRelations()->get('embedded')->getOptions()->set('load', 'lazy');

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new GenerateRelations(['embedded' => new Embedded()]))->run($r);
        $schema = $cc->compile($r);

        \Testo\Assert::array($schema)->hasKeys('composite');
        \Testo\Assert::array($schema['composite'][Schema::RELATIONS])->hasKeys('embedded');
        \Testo\Assert::same($schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::TYPE], \Cycle\ORM\Relation::EMBEDDED);

        \Testo\Assert::same($schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::LOAD], \Cycle\ORM\Relation::LOAD_PROMISE);

        \Testo\Assert::array($schema)->hasKeys('composite:embedded:embedded');
        \Testo\Assert::same($schema['composite:embedded:embedded'][Schema::PRIMARY_KEY], ['p_id']);
        \Testo\Assert::same($schema['composite:embedded:embedded'][Schema::DATABASE], 'default');
        \Testo\Assert::same($schema['composite:embedded:embedded'][Schema::TABLE], 'composite');

        \Testo\Assert::same($schema['composite:embedded:embedded'][Schema::COLUMNS], [
            'p_embedded' => 'embedded_column',
            'p_id' => 'id',
        ]);
    }

    #[\Testo\Test]
    public function testRenderTable(): void
    {
        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded'),
        );

        $c->getRelations()->get('embedded')->getOptions()->set('load', 'lazy');

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new Compiler())->compile($r, [
            new GenerateRelations(['embedded' => new Embedded()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('composite');
        \Testo\Assert::true($table->exists());
        \Testo\Assert::true($table->hasColumn('id'));
        \Testo\Assert::true($table->hasColumn('embedded_column'));
    }

    #[\Testo\Test]
    public function testEmbedIdFieldWithPrefix(): void
    {
        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $e->getFields()->set('p_id', (new Field())->setColumn('embedded_id')->setType('int'));

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded'),
        );

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        \Testo\Expect::exception(EmbeddedPrimaryKeyException::class)->withMessageContaining('Entity `composite:embedded:embedded` has conflicted field `p_id`.');

        (new Compiler())->compile(
            $r,
            [
                new GenerateRelations(['embedded' => new Embedded()]),
                $t = new RenderTables(),
                new RenderRelations(),
            ],
        );
    }

    #[\Testo\Test]
    public function testEmbeddedPrefix(): void
    {
        $c = Composite::define();
        $e = EmbeddedEntity::define();

        $c->getRelations()->set(
            'embedded',
            (new Relation())->setTarget('embedded')->setType('embedded'),
        );

        $c->getRelations()->get('embedded')->getOptions()->set('embeddedPrefix', 'prefix_');

        $r = new Registry($this->dbal);
        $r->register($c)->linkTable($c, 'default', 'composite');
        $r->register($e);

        (new Compiler())->compile($r, [
            new GenerateRelations(['embedded' => new Embedded()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('composite');

        \Testo\Assert::true($table->hasColumn('id'));
        \Testo\Assert::true($table->hasColumn('prefix_embedded_column'));
    }
}
