<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Table\Column;
use Spiral\Database\Schema\AbstractTable;

abstract class ColumnTest extends BaseTest
{
    public function testColumn(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');

        $column = Column::parse($field);

        $this->assertSame('name', $column->getName());
        $this->assertSame('string', $column->getType());

        $this->assertFalse($column->hasDefault());
        $this->assertFalse($column->isNullable());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\ColumnException
     */
    public function testInvalidDeclaration(): void
    {
        $field = new Field();
        $field->setType('7');
        $field->setColumn('name');

        Column::parse($field);
    }

    public function testColumnNullableStrict(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_NULLABLE, true);

        $column = Column::parse($field);

        $this->assertSame('name', $column->getName());
        $this->assertSame('string', $column->getType());

        $this->assertFalse($column->hasDefault());
        $this->assertTrue($column->isNullable());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\ColumnException
     */
    public function testNoDefaultValue(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');

        $column = Column::parse($field);

        $this->assertFalse($column->hasDefault());
        $column->getDefault();
    }

    public function testColumnNullableThoughtDefault(): void
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_DEFAULT, null);

        $column = Column::parse($field);

        $this->assertSame('name', $column->getName());
        $this->assertSame('string', $column->getType());

        $this->assertTrue($column->isNullable());
        $this->assertTrue($column->hasDefault());
        $this->assertSame(null, $column->getDefault());
    }

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
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('string', $table->column('name')->getType());

        $this->assertFalse($table->column('name')->isNullable());
    }

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
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('string', $table->column('name')->getType());

        $this->assertTrue($table->column('name')->isNullable());
    }

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
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('string', $table->column('name')->getType());

        $this->assertTrue($table->column('name')->isNullable());
        $this->assertSame('value', $table->column('name')->getDefaultValue());
    }

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
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('string', $table->column('name')->getType());
        $this->assertSame(32, $table->column('name')->getSize());
    }

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
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('decimal', $table->column('name')->getAbstractType());
        $this->assertSame(10, $table->column('name')->getPrecision());
        $this->assertSame(5, $table->column('name')->getScale());
    }

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
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('string', $table->column('name')->getType());

        $this->assertFalse($table->column('name')->isNullable());
        $this->assertSame('', $table->column('name')->getDefaultValue());
    }

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
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('int', $table->column('name')->getType());

        $this->assertFalse($table->column('name')->isNullable());
        $this->assertSame(0, $table->column('name')->getDefaultValue());
    }

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
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('float', $table->column('name')->getType());

        $this->assertFalse($table->column('name')->isNullable());
        $this->assertSame(0.0, $table->column('name')->getDefaultValue());
    }

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
        $this->assertTrue($table->hasColumn('name'));

        $this->assertFalse($table->column('name')->isNullable());
        $this->assertEquals(false, $table->column('name')->getDefaultValue());
    }

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
        $this->assertTrue($table->hasColumn('name'));

        $this->assertFalse($table->column('name')->isNullable());
        $this->assertEquals('a', $table->column('name')->getDefaultValue());
    }

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
        $this->assertTrue($table->hasColumn('name'));

        $this->assertFalse($table->column('name')->isNullable());
        $this->assertInstanceOf(\DateTimeInterface::class, $table->column('name')->getDefaultValue());
    }

    /**
     * @return AbstractTable
     */
    protected function getStub(): AbstractTable
    {
        return $this->dbal->database('default')->table('sample')->getSchema();
    }
}
