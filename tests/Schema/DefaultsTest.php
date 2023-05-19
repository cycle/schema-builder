<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Defaults;
use PHPUnit\Framework\TestCase;

final class DefaultsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $defaults = new Defaults();

        $this->assertSame(Mapper::class, $defaults[SchemaInterface::MAPPER]);
        $this->assertSame(Repository::class, $defaults[SchemaInterface::REPOSITORY]);
        $this->assertSame(Source::class, $defaults[SchemaInterface::SOURCE]);
        $this->assertNull($defaults[SchemaInterface::SCOPE]);
        $this->assertNull($defaults[SchemaInterface::TYPECAST_HANDLER]);
    }

    /**
     * @dataProvider mergeDataProvider
     */
    public function testMerge(array $expected, array $values): void
    {
        $defaults = new Defaults();
        $defaults->merge($values);

        $ref = new \ReflectionProperty($defaults, 'defaults');
        $ref->setAccessible(true);

        $this->assertEquals($expected, $ref->getValue($defaults));
    }

    public function testOffsetExists(): void
    {
        $defaults = new Defaults();

        $this->assertTrue($defaults->offsetExists(SchemaInterface::MAPPER));
        $this->assertFalse($defaults->offsetExists('foo'));
    }

    public function testOffsetGet(): void
    {
        $defaults = new Defaults();

        $this->assertSame(Mapper::class, $defaults->offsetGet(SchemaInterface::MAPPER));
    }

    public function testOffsetSet(): void
    {
        $defaults = new Defaults();
        $defaults->offsetSet('foo', 'bar');

        $this->assertSame('bar', $defaults->offsetGet('foo'));
    }

    public function testOffsetUnset(): void
    {
        $defaults = new Defaults();

        $this->assertTrue($defaults->offsetExists(SchemaInterface::MAPPER));
        $defaults->offsetUnset(SchemaInterface::MAPPER);
        $this->assertFalse($defaults->offsetExists(SchemaInterface::MAPPER));
    }

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
}
