<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\MySQL;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Table\Column;
use Cycle\Schema\Tests\ColumnTest as BaseTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';

    public function testAttributesUnsigned(): void
    {
        $field = new Field();
        $field->setType('integer');
        $field->setColumn('name');
        $field->getAttributes()->set('unsigned', true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('name'));

        $table->save();

        $table = $this->getStub();
        $this->assertTrue($table->hasColumn('name'));
        $this->assertArrayHasKey('unsigned', $table->column('name')->getAttributes());
        $this->assertTrue($table->column('name')->getAttributes()['unsigned']);
        $this->assertTrue($table->column('name')->isUnsigned());
    }

    public function testUnsignedColumnWithAdditionalAttributes(): void
    {
        $comment = 'Foo Bar Baz';

        $field = new Field();
        $field->setType('boolean');
        $field->setColumn('foo');
        $field->getAttributes()->set('comment', $comment);
        $field->getAttributes()->set('unsigned', true);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('foo'));

        $table->save();

        $table = $this->getStub();
        $this->assertTrue($table->hasColumn('foo'));
        $this->assertArrayHasKey('comment', $table->column('foo')->getAttributes());
        $this->assertArrayHasKey('unsigned', $table->column('foo')->getAttributes());
        $this->assertSame($comment, $table->column('foo')->getAttributes()['comment']);
        $this->assertSame($comment, $table->column('foo')->getComment());
        $this->assertTrue($table->column('foo')->getAttributes()['unsigned']);
        $this->assertTrue($table->column('foo')->isUnsigned());
    }

    public function testUnsignedColumnWithAdditionalAttributesAndDefault(): void
    {
        $comment = 'Foo Bar Baz';

        $field = new Field();
        $field->setType('boolean');
        $field->setColumn('foo');
        $field->getAttributes()->set('comment', $comment);
        $field->getAttributes()->set('unsigned', true);
        $field->getOptions()->set('default', false);

        $table = $this->getStub();
        $column = Column::parse($field);

        $column->render($table->column('foo'));

        $table->save();

        $table = $this->getStub();
        $this->assertTrue($table->hasColumn('foo'));
        $this->assertArrayHasKey('comment', $table->column('foo')->getAttributes());
        $this->assertArrayHasKey('unsigned', $table->column('foo')->getAttributes());
        $this->assertSame(0, $table->column('foo')->getDefaultValue());
        $this->assertSame($comment, $table->column('foo')->getAttributes()['comment']);
        $this->assertSame($comment, $table->column('foo')->getComment());
        $this->assertTrue($table->column('foo')->getAttributes()['unsigned']);
        $this->assertTrue($table->column('foo')->isUnsigned());
    }
}
