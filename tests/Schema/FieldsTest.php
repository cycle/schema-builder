<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Map\FieldMap;
use Cycle\Schema\Exception\FieldException;
use PHPUnit\Framework\TestCase;

class FieldsTest extends TestCase
{
    public function testNoField(): void
    {
        $m = new FieldMap();

        $this->expectException(FieldException::class);

        $m->get('id');
    }

    public function testSetGet(): void
    {
        $m = new FieldMap();

        $this->assertFalse($m->has('id'));
        $m->set('id', $f = new Field());
        $this->assertTrue($m->has('id'));
        $this->assertSame($f, $m->get('id'));

        $this->assertSame(['id' => $f], iterator_to_array($m->getIterator()));
    }

    public function testSetTwice(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        $this->expectException(FieldException::class);

        $m->set('id', $f = new Field());
    }

    public function testNoType(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        $this->expectException(FieldException::class);

        $m->get('id')->getType();
    }

    public function testNoColumn(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());

        $this->expectException(FieldException::class);

        $m->get('id')->getColumn();
    }

    public function testCount(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        $this->assertSame(2, $m->count());
    }

    public function testGetColumnNames(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        $this->assertSame(['id', 'name'], $m->getColumnNames());
    }

    public function testGeEntityClass(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id')->setEntityClass('test'));

        $this->assertSame('test', $m->get('p_id')->getEntityClass());
    }

    public function testGetNames(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_name', (new Field())->setColumn('name'));

        $this->assertSame(['p_id', 'p_name'], $m->getNames());
    }

    public function testHasColumn(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));

        $this->assertTrue($m->hasColumn('id'));
        $this->assertFalse($m->hasColumn('p_id'));
    }

    public function testGetKeyByColumnName(): void
    {
        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));
        $m->set('p_slug', (new Field())->setColumn('slug'));

        $this->assertSame('p_id', $m->getKeyByColumnName('id'));
        $this->assertSame('p_slug', $m->getKeyByColumnName('slug'));
    }

    public function testGetKeyByColumnNameShouldThrowAnExceptionWhenFieldNotFound(): void
    {
        $this->expectException(FieldException::class);
        $this->expectErrorMessage('Undefined field with column name `slug`.');

        $m = new FieldMap();
        $m->set('p_id', (new Field())->setColumn('id'));

        $m->getKeyByColumnName('slug');
    }

    public function testGetByColumnName(): void
    {
        $m = new FieldMap();
        $m->set('p_id', $id = (new Field())->setColumn('id'));
        $m->set('p_slug', $slug = (new Field())->setColumn('slug'));

        $this->assertSame($id, $m->getByColumnName('id'));
        $this->assertSame($slug, $m->getByColumnName('slug'));
    }

    public function testGetByColumnNameShouldThrowAnExceptionWhenFieldNotFound(): void
    {
        $this->expectException(FieldException::class);
        $this->expectErrorMessage('Undefined field with column name `slug`.');

        $m = new FieldMap();
        $m->set('p_id', $id = (new Field())->setColumn('id'));

        $m->getByColumnName('slug');
    }
}
