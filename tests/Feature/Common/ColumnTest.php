<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common;

use Cycle\Database\Schema\AbstractTable;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\ColumnException;
use Cycle\Schema\Table\Column;
use Cycle\Schema\Tests\Feature\BaseTest;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

final class ColumnTest extends BaseTest
{
    #[Test]
    public function testColumn(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');

        $column = Column::parse($field);

        Assert::same($column->getName(), 'name');
        Assert::same($column->getType(), 'string');

        Assert::false($column->hasDefault());
        Assert::false($column->isNullable());
    }

    #[Test]
    public function testInvalidDeclaration(): void
    {
        $field = new Field();
        $field->setType('7');
        $field->setColumn('name');

        Expect::exception(ColumnException::class);

        Column::parse($field);
    }

    #[Test]
    public function testColumnNullableStrict(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_NULLABLE, true);

        $column = Column::parse($field);

        Assert::same($column->getName(), 'name');
        Assert::same($column->getType(), 'string');

        Assert::false($column->hasDefault());
        Assert::true($column->isNullable());
    }

    #[Test]
    public function testNoDefaultValue(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $column = Column::parse($field);

        Assert::false($column->hasDefault());
        Expect::exception(ColumnException::class);

        $column->getDefault();
    }

    #[Test]
    public function testColumnNullableThoughtDefault(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_DEFAULT, null);

        $column = Column::parse($field);

        Assert::same($column->getName(), 'name');
        Assert::same($column->getType(), 'string');

        Assert::true($column->isNullable());
        Assert::true($column->hasDefault());
        Assert::same($column->getDefault(), null);
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getType(), 'string');

        Assert::false($table->column('name')->isNullable());
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getType(), 'string');

        Assert::true($table->column('name')->isNullable());
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getType(), 'string');

        Assert::true($table->column('name')->isNullable());
        Assert::same($table->column('name')->getDefaultValue(), 'value');
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getType(), 'string');
        Assert::same($table->column('name')->getSize(), 32);
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getAbstractType(), 'decimal');
        Assert::same($table->column('name')->getPrecision(), 10);
        Assert::same($table->column('name')->getScale(), 5);
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getAbstractType(), 'integer');
        Assert::true($table->column('name')->isNullable());
        Assert::true($table->column('name')->getAttributes()['nullable']);
        Assert::same($table->column('name')->getDefaultValue(), 5);
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getType(), 'string');

        Assert::false($table->column('name')->isNullable());
        Assert::same($table->column('name')->getDefaultValue(), '');
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getType(), 'int');

        Assert::false($table->column('name')->isNullable());
        Assert::same($table->column('name')->getDefaultValue(), 0);
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getType(), 'float');

        Assert::false($table->column('name')->isNullable());
        Assert::same($table->column('name')->getDefaultValue(), 0.0);
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));

        Assert::false($table->column('name')->isNullable());
        Assert::equals($table->column('name')->getDefaultValue(), false);
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));

        Assert::false($table->column('name')->isNullable());
        Assert::equals($table->column('name')->getDefaultValue(), 'a');
    }

    #[Test]
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
        Assert::true($table->hasColumn('name'));

        Assert::false($table->column('name')->isNullable());
        Assert::instanceOf($table->column('name')->getDefaultValue(), \DateTimeInterface::class);
    }

    #[Test]
    public function testRenderWithCustomType(): void
    {
        $field = new Field();
        $field->setType('ltree');
        $field->setColumn('name');

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        Assert::true($table->hasColumn('name'));
        Assert::same($table->column('name')->getDeclaredType(), 'ltree');
    }

    #[Test]
    #[DataProvider('dataIsPrimary')]
    public function testIsPrimary(string $type, bool $expected = true): void
    {
        $field = new Field();
        $field->setType($type);
        $field->setColumn('id');

        $column = Column::parse($field);
        Assert::same($column->isPrimary(), $expected);
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

    #[Test]
    public function trimsTypeOptions(): void
    {
        $field = (new Field())->setType('enum(a, b,  c)')->setColumn('name');
        $table = $this->getStub();

        Column::parse($field)->render($table->column('name'));

        Assert::same($table->column('name')->getEnumValues(), ['a', 'b', 'c']);
    }

    #[Test]
    public function passesOptionsOnlyToKnownTypeMethods(): void
    {
        $field = (new Field())->setType('ltree(5)')->setColumn('name');
        $table = $this->getStub();

        Column::parse($field)->render($table->column('name'));

        Assert::same($table->column('name')->getDeclaredType(), 'ltree');
    }

    #[Test]
    public function wrapsAFailureOfTheColumnTypeMethod(): never
    {
        $field = (new Field())->setType('decimal(abc)')->setColumn('price');
        $table = $this->getStub();

        Expect::exception(ColumnException::class)->withMessagePattern("/^Invalid column type definition in '(public\.)?sample'\.'price'$/");

        Column::parse($field)->render($table->column('price'));
    }

    #[Test]
    public function primaryColumnHasNoDefault(): void
    {
        $field = (new Field())->setType('primary')->setColumn('id');
        $field->getOptions()->set(Column::OPT_DEFAULT, 1);

        Assert::false(Column::parse($field)->hasDefault());
    }

    #[Test]
    public function nullableColumnDropsThePreviousDefault(): void
    {
        $field = (new Field())->setType('string')->setColumn('name');
        $field->getOptions()->set(Column::OPT_NULLABLE, true);
        $table = $this->getStub();
        $table->column('name')->string()->defaultValue('old');

        Column::parse($field)->render($table->column('name'));

        Assert::null($table->column('name')->getDefaultValue());
    }

    #[Test]
    public function notNullableColumnKeepsThePreviousDefault(): void
    {
        $field = (new Field())->setType('string')->setColumn('name');
        $table = $this->getStub();
        $table->column('name')->string()->defaultValue('old');

        Column::parse($field)->render($table->column('name'));

        Assert::same($table->column('name')->getDefaultValue(), 'old');
    }

    #[Test]
    public function castsTheDefaultOfADatetimeToTheEpoch(): void
    {
        $field = (new Field())->setType('datetime')->setColumn('name');
        $field->getOptions()->set(Column::OPT_CAST_DEFAULT, true);
        $table = $this->getStub();

        Column::parse($field)->render($table->column('name'));

        Assert::same($table->column('name')->getDefaultValue()->format('Y-m-d H:i:s'), '1970-01-01 00:00:00');
    }

    protected function getStub(): AbstractTable
    {
        return $this->dbal->database('default')->table('sample')->getSchema();
    }
}
