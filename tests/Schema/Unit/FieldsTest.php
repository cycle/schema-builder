<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Map\FieldMap;
use Cycle\Schema\Exception\FieldException;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
final class FieldsTest
{
    public function testNoField(): void
    {
        $m = new FieldMap();

        Expect::exception(FieldException::class);

        $m->get('id');
    }

    public function testSetGet(): void
    {
        $m = new FieldMap();

        Assert::false($m->has('id'));
        $m->set('id', $f = new Field());
        Assert::true($m->has('id'));
        Assert::same($m->get('id'), $f);

        Assert::same(iterator_to_array($m->getIterator()), ['id' => $f]);
    }

    public function testSetTwice(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        Expect::exception(FieldException::class);

        $m->set('id', $f = new Field());
    }

    public function testNoType(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        Expect::exception(FieldException::class);

        $m->get('id')->getType();
    }

    public function testNoColumn(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        Expect::exception(FieldException::class);

        $m->get('id')->getColumn();
    }

    public function testCount(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        Assert::same($m->count(), 2);
    }

    public function testGetColumnNames(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        Assert::same($m->getColumnNames(), ['id', 'name']);
    }

    public function testGeEntityClass(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id')->setEntityClass('test'));

        Assert::same($m->get('p_id')->getEntityClass(), 'test');
    }

    public function testGetNames(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        Assert::same($m->getNames(), ['p_id', 'p_name']);
    }

    public function testHasColumn(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));

        Assert::true($m->hasColumn('id'));
        Assert::false($m->hasColumn('p_id'));
    }

    public function testGetKeyByColumnName(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_slug', (new Field())->setColumn('slug'));

        Assert::same($m->getKeyByColumnName('id'), 'p_id');
        Assert::same($m->getKeyByColumnName('slug'), 'p_slug');
    }

    public function testGetKeyByColumnNameShouldThrowAnExceptionWhenFieldNotFound(): void
    {
        Expect::exception(FieldException::class)->withMessageContaining('Undefined field with column name `slug`.');

        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));

        $m->getKeyByColumnName('slug');
    }

    public function testGetByColumnName(): void
    {
        $m = new FieldMap();
        $m->set('p_id', $id = (new Field())->setColumn('id'));
        $m->set('p_slug', $slug = (new Field())->setColumn('slug'));

        Assert::same($m->getByColumnName('id'), $id);
        Assert::same($m->getByColumnName('slug'), $slug);
    }

    public function testGetByColumnNameShouldThrowAnExceptionWhenFieldNotFound(): void
    {
        Expect::exception(FieldException::class)->withMessageContaining('Undefined field with column name `slug`.');

        $m = new FieldMap();
        $m->set('p_id', $id = (new Field())->setColumn('id'));

        $m->getByColumnName('slug');
    }

    #[DataProvider('dataIsPrimary')]
    public function testIsPrimary(string $type, bool $expected = true): void
    {
        $field = new Field();
        $field->setType($type);
        Assert::same($field->isPrimary(), $expected);
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
}
