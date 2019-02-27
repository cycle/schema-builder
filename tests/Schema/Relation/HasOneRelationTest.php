<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Relation;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\RelationGenerator;
use Cycle\Schema\Generator\RelationReflector;
use Cycle\Schema\Generator\TableGenerator;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;

abstract class HasOneRelationTest extends BaseTest
{
    public function testGenerate()
    {
        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $r->iterate(new RelationGenerator([
            'hasOne' => new HasOne()
        ]));

        $this->assertInstanceOf(HasOne::class, $r->getRelation($u, 'plain'));
    }

    public function testPackSchema()
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $r->iterate(new RelationGenerator(['hasOne' => new HasOne()]));
        $r->iterate($c);

        $schema = $c->getSchema();

        $this->assertArrayHasKey('user', $schema);
        $this->assertArrayHasKey('plain', $schema['user'][Schema::RELATIONS]);

        $this->assertArrayHasKey('plain', $schema);
        $this->assertArrayHasKey('user_id', $schema['plain'][Schema::COLUMNS]);
    }

    public function testRenderTable()
    {
        $t = new TableGenerator();
        $l = new RelationReflector();

        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $r->iterate(new RelationGenerator(['hasOne' => new HasOne()]));
        $r->iterate($t);
        $r->iterate($l);

        $this->enableProfiling();

        // RENDER!
        $t->getReflector()->run();
    }
}