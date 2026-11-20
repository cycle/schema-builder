<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation;

use Cycle\Database\Schema\AbstractTable;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\RelationSchema;
use Cycle\Schema\Tests\Feature\BaseTest;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

final class RelationSchemaTest extends BaseTest
{
    public static function indexLookups(): iterable
    {
        yield 'same order' => [['a', 'b'], [], true];
        yield 'other order' => [['b', 'a'], [], false];
        yield 'other order, any order allowed' => [['b', 'a'], ['strictOrder' => false], true];
        yield 'only unique' => [['a', 'b'], ['unique' => true], false];
        yield 'only unique, any order allowed' => [['a', 'b'], ['strictOrder' => false, 'unique' => true], false];
        yield 'only non-unique' => [['a', 'b'], ['unique' => false], true];
        yield 'sorted column without sorting' => [['c', 'd'], ['withSorting' => false], true];
        yield 'sorted column with sorting' => [['c', 'd'], [], false];
        yield 'sorted column, any order, without sorting' => [['d', 'c'], ['strictOrder' => false, 'withSorting' => false], true];
        yield 'unique index after a non-unique one' => [['e'], ['unique' => true], true];
        yield 'subset of the columns' => [['a'], ['strictOrder' => false], false];
    }

    #[Test]
    #[DataProvider('indexLookups')]
    public function findsAnIndex(array $columns, array $arguments, bool $expected): void
    {
        $table = $this->database->table('sample')->getSchema();
        foreach (['a', 'b', 'c', 'd', 'e'] as $column) {
            $table->integer($column);
        }
        $table->index(['a', 'b']);
        $table->index(['c', 'd'])->sort(['c' => 'DESC']);
        $table->index(['e'])->unique();

        Assert::same($this->relation()->hasIndexOn($table, $columns, ...$arguments), $expected);
    }

    #[Test]
    public function packsOnlyTheSchemaOptions(): void
    {
        $relation = $this->relation()->withContext('author', 'post', 'user', new OptionSchema([]));
        $schema = [];

        $relation->modifySchema($schema);

        Assert::same($schema[SchemaInterface::RELATIONS]['author'][Relation::SCHEMA], [
            Relation::NULLABLE => false,
            Relation::CASCADE => true,
        ]);
    }

    private function relation(): RelationSchema
    {
        return new class extends RelationSchema {
            protected const RELATION_SCHEMA = [
                self::INDEX_CREATE => true,
                Relation::NULLABLE => false,
                self::FK_CREATE => true,
                Relation::CASCADE => true,
            ];

            public function render(Registry $registry): void {}

            public function hasIndexOn(AbstractTable $table, array $columns, mixed ...$arguments): bool
            {
                return $this->hasIndex($table, $columns, ...$arguments);
            }
        };
    }
}
