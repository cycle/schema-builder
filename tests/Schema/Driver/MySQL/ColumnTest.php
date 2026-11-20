<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\MySQL;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Table\Column;
use Cycle\Schema\Tests\ColumnTest as BaseTest;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class ColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
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
        \Testo\Assert::true($table->hasColumn('name'));
        \Testo\Assert::array($table->column('name')->getAttributes())->hasKeys('unsigned');
        \Testo\Assert::true($table->column('name')->getAttributes()['unsigned']);
        \Testo\Assert::true($table->column('name')->isUnsigned());
    }

    #[\Testo\Test]
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
        \Testo\Assert::true($table->hasColumn('foo'));
        \Testo\Assert::array($table->column('foo')->getAttributes())->hasKeys('comment');
        \Testo\Assert::array($table->column('foo')->getAttributes())->hasKeys('unsigned');
        \Testo\Assert::same($table->column('foo')->getAttributes()['comment'], $comment);
        \Testo\Assert::same($table->column('foo')->getComment(), $comment);
        \Testo\Assert::true($table->column('foo')->getAttributes()['unsigned']);
        \Testo\Assert::true($table->column('foo')->isUnsigned());
    }

    #[\Testo\Test]
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
        \Testo\Assert::true($table->hasColumn('foo'));
        \Testo\Assert::array($table->column('foo')->getAttributes())->hasKeys('comment');
        \Testo\Assert::array($table->column('foo')->getAttributes())->hasKeys('unsigned');
        \Testo\Assert::same($table->column('foo')->getDefaultValue(), 0);
        \Testo\Assert::same($table->column('foo')->getAttributes()['comment'], $comment);
        \Testo\Assert::same($table->column('foo')->getComment(), $comment);
        \Testo\Assert::true($table->column('foo')->getAttributes()['unsigned']);
        \Testo\Assert::true($table->column('foo')->isUnsigned());
    }
}
