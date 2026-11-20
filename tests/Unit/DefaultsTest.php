<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Defaults;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
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

    public function testDefaultValues(): void
    {
        $defaults = new Defaults();

        Assert::same($defaults[SchemaInterface::MAPPER], Mapper::class);
        Assert::same($defaults[SchemaInterface::REPOSITORY], Repository::class);
        Assert::same($defaults[SchemaInterface::SOURCE], Source::class);
        Assert::null($defaults[SchemaInterface::SCOPE]);
        Assert::null($defaults[SchemaInterface::TYPECAST_HANDLER]);
    }

    #[DataProvider('mergeDataProvider')]
    public function testMerge(array $expected, array $values): void
    {
        $defaults = new Defaults();
        $defaults->merge($values);

        $ref = new \ReflectionProperty($defaults, 'defaults');
        $ref->setAccessible(true);

        Assert::equals($ref->getValue($defaults), $expected);
    }

    public function testOffsetExists(): void
    {
        $defaults = new Defaults();

        Assert::true($defaults->offsetExists(SchemaInterface::MAPPER));
        Assert::false($defaults->offsetExists('foo'));
    }

    public function testOffsetGet(): void
    {
        $defaults = new Defaults();

        Assert::same($defaults->offsetGet(SchemaInterface::MAPPER), Mapper::class);
    }

    public function testOffsetSet(): void
    {
        $defaults = new Defaults();
        $defaults->offsetSet('foo', 'bar');

        Assert::same($defaults->offsetGet('foo'), 'bar');
    }

    public function testOffsetUnset(): void
    {
        $defaults = new Defaults();

        Assert::true($defaults->offsetExists(SchemaInterface::MAPPER));
        $defaults->offsetUnset(SchemaInterface::MAPPER);
        Assert::false($defaults->offsetExists(SchemaInterface::MAPPER));
    }
}
