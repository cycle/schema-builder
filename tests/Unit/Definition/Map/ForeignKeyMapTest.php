<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Definition\Map;

use Cycle\Schema\Definition\ForeignKey;
use Cycle\Schema\Definition\Map\ForeignKeyMap;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ForeignKeyMapTest
{
    public function testEmptyForeignKeys(): void
    {
        $map = new ForeignKeyMap();

        Assert::same(\iterator_to_array($map->getIterator()), []);
    }

    public function testHas(): void
    {
        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        Assert::false($map->has($key));

        $map->set($key);
        Assert::true($map->has($key));
    }

    public function testSet(): void
    {
        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        Assert::false($map->has($key));

        $map->set($key);
        Assert::same(\iterator_to_array($map->getIterator()), ['foo:field:field' => $key]);
    }

    public function testRemove(): void
    {
        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        Assert::false($map->has($key));

        $map->set($key);
        Assert::true($map->has($key));

        $map->remove($key);
        Assert::false($map->has($key));
    }

    public function testCount(): void
    {
        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        Assert::same($map->count(), 0);

        $map->set($key);
        Assert::same($map->count(), 1);
    }
}
