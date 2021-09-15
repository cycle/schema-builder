<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;

abstract class RenderRelationsTest extends BaseTest
{
    public function fkActionDataProvider(): array
    {
        return [
            'Default' => [false, false, 'CASCADE', 'CASCADE'],
            'Only fkAction' => ['SET NULL', false, 'SET NULL', 'SET NULL'],
            'Only onDelete' => [false, 'SET NULL', 'CASCADE', 'SET NULL'],
            'Both' => ['NO ACTION', 'SET NULL', 'NO ACTION', 'SET NULL'],
        ];
    }

    /**
     * @dataProvider fkActionDataProvider
     */
    public function testFkActionAndFkOnDelete(
        null|false|string $fkActionOption,
        null|false|string $onDeleteOption,
        string $onUpdateExpected,
        string $onDeleteExpected
    ): void {
        $plain = Plain::define();
        $user = User::define();

        $options = $user->getRelations()->get('plain')->getOptions();
        $options->set('nullable', true);

        if ($fkActionOption !== false) {
            $options->set('fkAction', $fkActionOption);
        }
        if ($onDeleteOption !== false) {
            $options->set('fkOnDelete', $onDeleteOption);
        }

        $r = new Registry($this->dbal);
        $r->register($plain)->linkTable($plain, 'default', 'plain');
        $r->register($user)->linkTable($user, 'default', 'user');

        (new GenerateRelations())->run($r);
        (new RenderTables())->run($r);
        (new RenderRelations())->run($r);

        $table = $r->getTableSchema($plain);

        $fks = $table->getForeignKeys();
        $this->assertCount(1, $fks);
        $fk = reset($fks);
        $this->assertSame($onUpdateExpected, $fk->getUpdateRule());
        $this->assertSame($onDeleteExpected, $fk->getDeleteRule());
    }
}
