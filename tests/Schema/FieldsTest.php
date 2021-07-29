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

    public function testGetKeys(): void
    {
        $m = new FieldMap();
        $m->set('id', $f = new Field());
        $m->set('name', $f = new Field());

        $this->assertSame(['id', 'name'], $m->getKeys());
    }
}
