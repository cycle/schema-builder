<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\MySQL;

use Cycle\Database\Schema\AbstractTable;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Table\Column;
use Cycle\Schema\Tests\Feature\BaseTest;
use Testo\Assert;
use Testo\Test;

/**
 * MySQL-only column attributes: unsigned and zerofill.
 */
final class ColumnTest extends BaseTest
{
    #[Test]
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
        Assert::true($table->hasColumn('name'));
        Assert::array($table->column('name')->getAttributes())->hasKeys('unsigned');
        Assert::true($table->column('name')->getAttributes()['unsigned']);
        Assert::true($table->column('name')->isUnsigned());
    }

    #[Test]
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
        Assert::true($table->hasColumn('foo'));
        Assert::array($table->column('foo')->getAttributes())->hasKeys('comment');
        Assert::array($table->column('foo')->getAttributes())->hasKeys('unsigned');
        Assert::same($table->column('foo')->getAttributes()['comment'], $comment);
        Assert::same($table->column('foo')->getComment(), $comment);
        Assert::true($table->column('foo')->getAttributes()['unsigned']);
        Assert::true($table->column('foo')->isUnsigned());
    }

    #[Test]
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
        Assert::true($table->hasColumn('foo'));
        Assert::array($table->column('foo')->getAttributes())->hasKeys('comment');
        Assert::array($table->column('foo')->getAttributes())->hasKeys('unsigned');
        Assert::same($table->column('foo')->getDefaultValue(), 0);
        Assert::same($table->column('foo')->getAttributes()['comment'], $comment);
        Assert::same($table->column('foo')->getComment(), $comment);
        Assert::true($table->column('foo')->getAttributes()['unsigned']);
        Assert::true($table->column('foo')->isUnsigned());
    }

    private function getStub(): AbstractTable
    {
        return $this->dbal->database('default')->table('sample')->getSchema();
    }
}
