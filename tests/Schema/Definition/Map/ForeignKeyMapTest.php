<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Definition\Map;

use Cycle\Schema\Definition\ForeignKey;
use Cycle\Schema\Definition\Map\ForeignKeyMap;
use PHPUnit\Framework\TestCase;

final class ForeignKeyMapTest
{
    #[\Testo\Test]
    public function testEmptyForeignKeys(): void
    {
        $map = new ForeignKeyMap();

        \Testo\Assert::same(\iterator_to_array($map->getIterator()), []);
    }

    #[\Testo\Test]
    public function testHas(): void
    {
        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        \Testo\Assert::false($map->has($key));

        $map->set($key);
        \Testo\Assert::true($map->has($key));
    }

    #[\Testo\Test]
    public function testSet(): void
    {
        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        \Testo\Assert::false($map->has($key));

        $map->set($key);
        \Testo\Assert::same(\iterator_to_array($map->getIterator()), ['foo:field:field' => $key]);
    }

    #[\Testo\Test]
    public function testRemove(): void
    {
        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        \Testo\Assert::false($map->has($key));

        $map->set($key);
        \Testo\Assert::true($map->has($key));

        $map->remove($key);
        \Testo\Assert::false($map->has($key));
    }

    #[\Testo\Test]
    public function testCount(): void
    {
        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $map = new ForeignKeyMap();

        \Testo\Assert::same($map->count(), 0);

        $map->set($key);
        \Testo\Assert::same($map->count(), 1);
    }
}
