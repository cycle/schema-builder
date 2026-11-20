<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Defaults;
use PHPUnit\Framework\TestCase;

final class DefaultsTest
{
    public static function mergeDataProvider(): \Traversable
    {
        yield [
            [
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::REPOSITORY => Repository::class,
                SchemaInterface::SOURCE => Source::class,
                SchemaInterface::SCOPE => null,
                SchemaInterface::TYPECAST_HANDLER => null,
            ],
            [],
        ];
        yield [
            [
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::REPOSITORY => Repository::class,
                SchemaInterface::SOURCE => Source::class,
                SchemaInterface::SCOPE => null,
                SchemaInterface::TYPECAST_HANDLER => null,
                'foo' => 'bar',
            ],
            [
                'foo' => 'bar',
            ],
        ];
        yield [
            [
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::REPOSITORY => Repository::class,
                SchemaInterface::SOURCE => Source::class,
                SchemaInterface::SCOPE => null,
                SchemaInterface::TYPECAST_HANDLER => 'foo',
            ],
            [
                SchemaInterface::TYPECAST_HANDLER => 'foo',
            ],
        ];
        yield [
            [
                SchemaInterface::MAPPER => null,
                SchemaInterface::REPOSITORY => Repository::class,
                SchemaInterface::SOURCE => Source::class,
                SchemaInterface::SCOPE => null,
                SchemaInterface::TYPECAST_HANDLER => null,
            ],
            [
                SchemaInterface::MAPPER => null,
            ],
        ];
    }

    #[\Testo\Test]
    public function testDefaultValues(): void
    {
        $defaults = new Defaults();

        \Testo\Assert::same($defaults[SchemaInterface::MAPPER], Mapper::class);
        \Testo\Assert::same($defaults[SchemaInterface::REPOSITORY], Repository::class);
        \Testo\Assert::same($defaults[SchemaInterface::SOURCE], Source::class);
        \Testo\Assert::null($defaults[SchemaInterface::SCOPE]);
        \Testo\Assert::null($defaults[SchemaInterface::TYPECAST_HANDLER]);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('mergeDataProvider')]
    public function testMerge(array $expected, array $values): void
    {
        $defaults = new Defaults();
        $defaults->merge($values);

        $ref = new \ReflectionProperty($defaults, 'defaults');
        $ref->setAccessible(true);

        \Testo\Assert::equals($ref->getValue($defaults), $expected);
    }

    #[\Testo\Test]
    public function testOffsetExists(): void
    {
        $defaults = new Defaults();

        \Testo\Assert::true($defaults->offsetExists(SchemaInterface::MAPPER));
        \Testo\Assert::false($defaults->offsetExists('foo'));
    }

    #[\Testo\Test]
    public function testOffsetGet(): void
    {
        $defaults = new Defaults();

        \Testo\Assert::same($defaults->offsetGet(SchemaInterface::MAPPER), Mapper::class);
    }

    #[\Testo\Test]
    public function testOffsetSet(): void
    {
        $defaults = new Defaults();
        $defaults->offsetSet('foo', 'bar');

        \Testo\Assert::same($defaults->offsetGet('foo'), 'bar');
    }

    #[\Testo\Test]
    public function testOffsetUnset(): void
    {
        $defaults = new Defaults();

        \Testo\Assert::true($defaults->offsetExists(SchemaInterface::MAPPER));
        $defaults->offsetUnset(SchemaInterface::MAPPER);
        \Testo\Assert::false($defaults->offsetExists(SchemaInterface::MAPPER));
    }
}
