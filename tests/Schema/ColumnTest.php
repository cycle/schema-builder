<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Table\Column;
use Spiral\Database\Schema\AbstractTable;

abstract class ColumnTest extends BaseTest
{
    public function testColumn()
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
    public function testInvalidDeclaration()
    {
        $field = new Field();
        $field->setType('7');
        $field->setColumn('name');

        Column::parse($field);
    }

    public function testColumnNullableStrict()
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
    public function testNoDefaultValue()
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');

        $column = Column::parse($field);

        $this->assertFalse($column->hasDefault());
        $column->getDefault();
    }

    public function testColumnNullableThoughtDefault()
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

    public function testRenderSimple()
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

    public function testNullable()
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

    public function testNullableAndDefault()
    {
        $field = new Field();
        $field->setType('string');
        $field->setColumn('name');
        $field->getOptions()->set(Column::OPT_NULLABLE, true);
        $field->getOptions()->set(Column::OPT_DEFAULT, "value");

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('string', $table->column('name')->getType());

        $this->assertTrue($table->column('name')->isNullable());
        $this->assertSame("value", $table->column('name')->getDefaultValue());
    }

    public function testRenderWithOption()
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

    public function testDecimal()
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

    public function testCastDefaultString()
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
        $this->assertSame("", $table->column('name')->getDefaultValue());
    }

    public function testCastDefaultInteger()
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

    public function testCastDefaultFloat()
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

    public function testCastDefaultBool()
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

    public function testCastDefaultEnum()
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

    public function testCastDefaultDatetime()
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