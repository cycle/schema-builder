<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Definition\Map;

use Cycle\Schema\Definition\ForeignKey;
use Cycle\Schema\Definition\Map\ForeignKeyMap;
use PHPUnit\Framework\TestCase;

final class ForeignKeyMapTest extends TestCase
{
    public function testEmptyForeignKeys(): void
    {
        $map = new ForeignKeyMap();

        $this->assertSame([], \iterator_to_array($map->getIterator()));
    }

    public function testHas(): void
    {
        $key = new ForeignKey();
        $key->setTable('table');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        $this->assertFalse($map->has($key));

        $map->set($key);
        $this->assertTrue($map->has($key));
    }

    public function testSet(): void
    {
        $key = new ForeignKey();
        $key->setTable('table');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        $this->assertFalse($map->has($key));

        $map->set($key);
        $this->assertSame(['table:field:field' => $key], \iterator_to_array($map->getIterator()));
    }

    public function testRemove(): void
    {
        $key = new ForeignKey();
        $key->setTable('table');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        $this->assertFalse($map->has($key));

        $map->set($key);
        $this->assertTrue($map->has($key));

        $map->remove($key);
        $this->assertFalse($map->has($key));
    }

    public function testCount(): void
    {
        $key = new ForeignKey();
        $key->setTable('table');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        $this->assertSame(0, $map->count());

        $map->set($key);
        $this->assertSame(1, $map->count());
    }
}
