<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Map\FieldMap;
use Cycle\Schema\Exception\FieldException;
use PHPUnit\Framework\TestCase;

class FieldsTest
{
    #[\Testo\Test]
    public function testNoField(): void
    {
        $m = new FieldMap();

        \Testo\Expect::exception(FieldException::class);

        $m->get('id');
    }

    #[\Testo\Test]
    public function testSetGet(): void
    {
        $m = new FieldMap();

        \Testo\Assert::false($m->has('id'));
        $m->set('id', $f = new Field());
        \Testo\Assert::true($m->has('id'));
        \Testo\Assert::same($m->get('id'), $f);

        \Testo\Assert::same(iterator_to_array($m->getIterator()), ['id' => $f]);
    }

    #[\Testo\Test]
    public function testSetTwice(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        \Testo\Expect::exception(FieldException::class);

        $m->set('id', $f = new Field());
    }

    #[\Testo\Test]
    public function testNoType(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        \Testo\Expect::exception(FieldException::class);

        $m->get('id')->getType();
    }

    #[\Testo\Test]
    public function testNoColumn(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        \Testo\Expect::exception(FieldException::class);

        $m->get('id')->getColumn();
    }

    #[\Testo\Test]
    public function testCount(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        \Testo\Assert::same($m->count(), 2);
    }

    #[\Testo\Test]
    public function testGetColumnNames(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        \Testo\Assert::same($m->getColumnNames(), ['id', 'name']);
    }

    #[\Testo\Test]
    public function testGeEntityClass(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id')->setEntityClass('test'));

        \Testo\Assert::same($m->get('p_id')->getEntityClass(), 'test');
    }

    #[\Testo\Test]
    public function testGetNames(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        \Testo\Assert::same($m->getNames(), ['p_id', 'p_name']);
    }

    #[\Testo\Test]
    public function testHasColumn(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));

        \Testo\Assert::true($m->hasColumn('id'));
        \Testo\Assert::false($m->hasColumn('p_id'));
    }

    #[\Testo\Test]
    public function testGetKeyByColumnName(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_slug', (new Field())->setColumn('slug'));

        \Testo\Assert::same($m->getKeyByColumnName('id'), 'p_id');
        \Testo\Assert::same($m->getKeyByColumnName('slug'), 'p_slug');
    }

    #[\Testo\Test]
    public function testGetKeyByColumnNameShouldThrowAnExceptionWhenFieldNotFound(): void
    {
        \Testo\Expect::exception(FieldException::class)->withMessageContaining('Undefined field with column name `slug`.');

        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));

        $m->getKeyByColumnName('slug');
    }

    #[\Testo\Test]
    public function testGetByColumnName(): void
    {
        $m = new FieldMap();
        $m->set('p_id', $id = (new Field())->setColumn('id'));
        $m->set('p_slug', $slug = (new Field())->setColumn('slug'));

        \Testo\Assert::same($m->getByColumnName('id'), $id);
        \Testo\Assert::same($m->getByColumnName('slug'), $slug);
    }

    #[\Testo\Test]
    public function testGetByColumnNameShouldThrowAnExceptionWhenFieldNotFound(): void
    {
        \Testo\Expect::exception(FieldException::class)->withMessageContaining('Undefined field with column name `slug`.');

        $m = new FieldMap();
        $m->set('p_id', $id = (new Field())->setColumn('id'));

        $m->getByColumnName('slug');
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('dataIsPrimary')]
    public function testIsPrimary(string $type, bool $expected = true): void
    {
        $field = new Field();
        $field->setType($type);
        \Testo\Assert::same($field->isPrimary(), $expected);
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
