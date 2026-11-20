<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\Database\Schema\AbstractTable;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\ColumnException;
use Cycle\Schema\Table\Column;

abstract class ColumnTest extends BaseTest
{
    #[\Testo\Test]
    public function testColumn(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');

        $column = Column::parse($field);

        \Testo\Assert::same($column->getName(), 'name');
        \Testo\Assert::same($column->getType(), 'string');

        \Testo\Assert::false($column->hasDefault());
        \Testo\Assert::false($column->isNullable());
    }

    #[\Testo\Test]
    public function testInvalidDeclaration(): void
    {
        $field = new Field();
        $field->setType('7');
        $field->setColumn('name');

        \Testo\Expect::exception(ColumnException::class);

        Column::parse($field);
    }

    #[\Testo\Test]
    public function testColumnNullableStrict(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_NULLABLE, true);

        $column = Column::parse($field);

        \Testo\Assert::same($column->getName(), 'name');
        \Testo\Assert::same($column->getType(), 'string');

        \Testo\Assert::false($column->hasDefault());
        \Testo\Assert::true($column->isNullable());
    }

    #[\Testo\Test]
    public function testNoDefaultValue(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $column = Column::parse($field);

        \Testo\Assert::false($column->hasDefault());
        \Testo\Expect::exception(ColumnException::class);

        $column->getDefault();
    }

    #[\Testo\Test]
    public function testColumnNullableThoughtDefault(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_DEFAULT, null);

        $column = Column::parse($field);

        \Testo\Assert::same($column->getName(), 'name');
        \Testo\Assert::same($column->getType(), 'string');

        \Testo\Assert::true($column->isNullable());
        \Testo\Assert::true($column->hasDefault());
        \Testo\Assert::same($column->getDefault(), null);
    }

    #[\Testo\Test]
    public function testRenderSimple(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getType(), 'string');

        \Testo\Assert::false($table->column('name')->isNullable());
    }

    #[\Testo\Test]
    public function testNullable(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_NULLABLE, true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getType(), 'string');

        \Testo\Assert::true($table->column('name')->isNullable());
    }

    #[\Testo\Test]
    public function testNullableAndDefault(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_NULLABLE, true);
        $field->getOptions()->set(Column::OPT_DEFAULT, 'value');

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getType(), 'string');

        \Testo\Assert::true($table->column('name')->isNullable());
        \Testo\Assert::same($table->column('name')->getDefaultValue(), 'value');
    }

    #[\Testo\Test]
    public function testRenderWithOption(): void
    {
        $field = new Field();
        $field->setType('string(32)');
        $field->setColumn('name');

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getType(), 'string');
        \Testo\Assert::same($table->column('name')->getSize(), 32);
    }

    #[\Testo\Test]
    public function testDecimal(): void
    {
        $field = new Field();
        $field->setType('decimal(10,5)');
        $field->setColumn('name');

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getAbstractType(), 'decimal');
        \Testo\Assert::same($table->column('name')->getPrecision(), 10);
        \Testo\Assert::same($table->column('name')->getScale(), 5);
    }

    #[\Testo\Test]
    public function testAttributes(): void
    {
        $field = new Field();
        $field->setType('integer');
        $field->setColumn('name');
        $field->getAttributes()->set('nullable', true);
        $field->getAttributes()->set('defaultValue', 5);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getAbstractType(), 'integer');
        \Testo\Assert::true($table->column('name')->isNullable());
        \Testo\Assert::true($table->column('name')->getAttributes()['nullable']);
        \Testo\Assert::same($table->column('name')->getDefaultValue(), 5);
    }

    #[\Testo\Test]
    public function testCastDefaultString(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_CAST_DEFAULT, true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getType(), 'string');

        \Testo\Assert::false($table->column('name')->isNullable());
        \Testo\Assert::same($table->column('name')->getDefaultValue(), '');
    }

    #[\Testo\Test]
    public function testCastDefaultInteger(): void
    {
        $field = new Field();
        $field->setType('int');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_CAST_DEFAULT, true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getType(), 'int');

        \Testo\Assert::false($table->column('name')->isNullable());
        \Testo\Assert::same($table->column('name')->getDefaultValue(), 0);
    }

    #[\Testo\Test]
    public function testCastDefaultFloat(): void
    {
        $field = new Field();
        $field->setType('float');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_CAST_DEFAULT, true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getType(), 'float');

        \Testo\Assert::false($table->column('name')->isNullable());
        \Testo\Assert::same($table->column('name')->getDefaultValue(), 0.0);
    }

    #[\Testo\Test]
    public function testCastDefaultBool(): void
    {
        $field = new Field();
        $field->setType('bool');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_CAST_DEFAULT, true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));

        \Testo\Assert::false($table->column('name')->isNullable());
        \Testo\Assert::equals($table->column('name')->getDefaultValue(), false);
    }

    #[\Testo\Test]
    public function testCastDefaultEnum(): void
    {
        $field = new Field();
        $field->setType('enum(a,b,c)');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_CAST_DEFAULT, true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));

        \Testo\Assert::false($table->column('name')->isNullable());
        \Testo\Assert::equals($table->column('name')->getDefaultValue(), 'a');
    }

    #[\Testo\Test]
    public function testCastDefaultDatetime(): void
    {
        $field = new Field();
        $field->setType('datetime');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_CAST_DEFAULT, true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        \Testo\Assert::true($table->hasColumn('name'));

        \Testo\Assert::false($table->column('name')->isNullable());
        \Testo\Assert::instanceOf($table->column('name')->getDefaultValue(), \DateTimeInterface::class);
    }

    #[\Testo\Test]
    public function testRenderWithCustomType(): void
    {
        $field = new Field();
        $field->setType('ltree');
        $field->setColumn('name');

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::same($table->column('name')->getDeclaredType(), 'ltree');
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('dataIsPrimary')]
    public function testIsPrimary(string $type, bool $expected = true): void
    {
        $field = new Field();
        $field->setType($type);
        $field->setColumn('id');

        $column = Column::parse($field);
        \Testo\Assert::same($column->isPrimary(), $expected);
    }

    public function dataIsPrimary(): iterable
    {
        yield 'primary' => [
            'primary',
            true,
        ];
        yield 'smallPrimary' => [
            'smallPrimary',
            true,
        ];
        yield 'bigPrimary' => [
            'bigPrimary',
            true,
        ];
        yield 'foo' => [
            'foo',
            false,
        ];
    }

    protected function getStub(): AbstractTable
    {
        return $this->dbal->database('default')->table('sample')->getSchema();
    }
}
