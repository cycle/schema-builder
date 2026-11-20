<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation\Morphed;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Morphed\MorphedHasMany;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Test;

final class MorphedHasManyRelationTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new MorphedHasMany()]))->run($r);

        Assert::instanceOf($r->getRelation($u, 'plain'), MorphedHasMany::class);
    }

    #[Test]
    public function testPackSchema(): void
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()
            ->get('plain')
            ->setType('hasMany')
            ->getOptions()
            ->set('collection', ArrayCollectionFactory::class);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasMany' => new MorphedHasMany()]))->run($r);
        $schema = $c->compile($r);

        Assert::array($schema)->hasKeys('user');
        Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::TYPE], Relation::MORPHED_HAS_MANY);

        Assert::array($schema['user'][Schema::RELATIONS])->hasKeys('plain');

        Assert::array($schema)->hasKeys('plain');
        Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('plain_p_id');
        Assert::array($schema['plain'][Schema::COLUMNS])->hasKeys('plain_role');

        Assert::same($schema['user'][Schema::RELATIONS]['plain'][Relation::SCHEMA][Relation::COLLECTION_TYPE], ArrayCollectionFactory::class);
    }

    #[Test]
    public function testRenderTable(): void
    {
        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->setType('hasMany');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new Compiler())->compile($r, [
            new GenerateRelations(['hasMany' => new MorphedHasMany()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        Assert::true($table->exists());
        Assert::true($table->hasColumn('plain_p_id'));
        Assert::true($table->hasColumn('plain_role'));
        Assert::true($table->hasIndex(['plain_p_id', 'plain_role']));
    }
}
