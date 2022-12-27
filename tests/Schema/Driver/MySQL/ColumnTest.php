<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\MySQL;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Table\Column;
use Cycle\Schema\Tests\ColumnTest as BaseTest;

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
}
