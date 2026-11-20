<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation;

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
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Composite;
use Cycle\Schema\Tests\Fixtures\EmbeddedEntity;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class EmbeddedTest extends BaseTest
{
    #[Test]
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

        Assert::instanceOf($r->getRelation($c, 'embedded'), Embedded::class);
    }

    #[Test]
    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        Expect::exception(RegistryException::class)->withMessageContaining('Entity `composite` must have defined primary key');

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

    #[Test]
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

        Assert::array($schema)->hasKeys('composite');
        Assert::array($schema['composite'][Schema::RELATIONS])->hasKeys('embedded');
        Assert::same($schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::TYPE], \Cycle\ORM\Relation::EMBEDDED);

        Assert::same($schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::LOAD], \Cycle\ORM\Relation::LOAD_EAGER);

        Assert::array($schema)->hasKeys('composite:embedded:embedded');
        Assert::same($schema['composite:embedded:embedded'][Schema::PRIMARY_KEY], ['p_id']);
        Assert::same($schema['composite:embedded:embedded'][Schema::DATABASE], 'default');
        Assert::same($schema['composite:embedded:embedded'][Schema::TABLE], 'composite');

        Assert::same($schema['composite:embedded:embedded'][Schema::COLUMNS], [
            'p_embedded' => 'embedded_column',
            'p_id' => 'id',
        ]);
    }

    #[Test]
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

        Assert::array($schema)->hasKeys('composite');
        Assert::array($schema['composite'][Schema::RELATIONS])->hasKeys('embedded');
        Assert::same($schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::TYPE], \Cycle\ORM\Relation::EMBEDDED);

        Assert::same($schema['composite'][Schema::RELATIONS]['embedded'][\Cycle\ORM\Relation::LOAD], \Cycle\ORM\Relation::LOAD_PROMISE);

        Assert::array($schema)->hasKeys('composite:embedded:embedded');
        Assert::same($schema['composite:embedded:embedded'][Schema::PRIMARY_KEY], ['p_id']);
        Assert::same($schema['composite:embedded:embedded'][Schema::DATABASE], 'default');
        Assert::same($schema['composite:embedded:embedded'][Schema::TABLE], 'composite');

        Assert::same($schema['composite:embedded:embedded'][Schema::COLUMNS], [
            'p_embedded' => 'embedded_column',
            'p_id' => 'id',
        ]);
    }

    #[Test]
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
        Assert::true($table->exists());
        Assert::true($table->hasColumn('id'));
        Assert::true($table->hasColumn('embedded_column'));
    }

    #[Test]
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

        Expect::exception(EmbeddedPrimaryKeyException::class)->withMessageContaining('Entity `composite:embedded:embedded` has conflicted field `p_id`.');

        (new Compiler())->compile(
            $r,
            [
                new GenerateRelations(['embedded' => new Embedded()]),
                $t = new RenderTables(),
                new RenderRelations(),
            ],
        );
    }

    #[Test]
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

        Assert::true($table->hasColumn('id'));
        Assert::true($table->hasColumn('prefix_embedded_column'));
    }
}
