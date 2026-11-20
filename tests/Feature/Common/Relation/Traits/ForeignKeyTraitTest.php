<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation\Traits;

use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\RelationSchema;
use Cycle\Schema\Relation\Traits\ForeignKeyTrait;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ForeignKeyTraitTest extends BaseTest
{
    use ForeignKeyTrait;

    public function createsAForeignKeyBetweenTwoFields(): void
    {
        [$r, $user, $plain] = $this->registry(plainDatabase: 'default');

        $this->createForeignKey(
            $r,
            $user,
            $plain,
            $user->getFields()->get('p_id'),
            $plain->getFields()->get('p_id'),
        );

        $table = $r->getTableSchema($plain);
        Assert::true($table->hasForeignKey(['id']));
        $fk = $table->foreignKey(['id']);
        Assert::same($fk->getForeignTable(), 'user');
        Assert::same($fk->getForeignKeys(), ['id']);
        Assert::same($fk->getUpdateRule(), 'CASCADE');
        Assert::same($fk->getDeleteRule(), 'CASCADE');
    }

    public function skipsAForeignKeyAcrossDatabases(): void
    {
        [$r, $user, $plain] = $this->registry(plainDatabase: 'secondary');

        $this->createForeignKey(
            $r,
            $user,
            $plain,
            $user->getFields()->get('p_id'),
            $plain->getFields()->get('p_id'),
        );
        $this->createForeignCompositeKey(
            $r,
            $user,
            $plain,
            $user->getFields(),
            $plain->getFields(),
        );

        Assert::same($r->getTableSchema($plain)->getForeignKeys(), []);
    }

    protected function getOptions(): OptionSchema
    {
        return (new OptionSchema([]))->withTemplate([
            RelationSchema::FK_ACTION => 'CASCADE',
            RelationSchema::FK_ON_DELETE => null,
        ]);
    }

    private function registry(string $plainDatabase): array
    {
        $user = User::define();
        $plain = Plain::define();
        $r = new Registry($this->dbal);
        $r->register($user)->linkTable($user, 'default', 'user');
        $r->register($plain)->linkTable($plain, $plainDatabase, 'plain');
        (new RenderTables())->run($r);

        return [$r, $user, $plain];
    }
}
