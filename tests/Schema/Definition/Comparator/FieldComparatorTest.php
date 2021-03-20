<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Definition\Comparator;

use Cycle\Schema\Definition\Comparator\FieldComparator;
use Cycle\Schema\Definition\Field;
use PHPUnit\Framework\TestCase;

final class FieldComparatorTest extends TestCase
{
    protected const EXCEPTION_MESSAGE_PREFIX = '/Different definitions are specified.*/';

    public function testCompareSameObject(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $bar = $foo;

        $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar)
            ->compare();

        // No exception
        $this->assertTrue(true);
    }

    public function testCompareMoreObjects(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $bar = $foo;
        $baz = clone $foo;
        $zoo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $bravo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);

        $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar)
            ->addField('baz', $baz)
            ->addField('zoo', $zoo)
            ->addField('bravo', $bravo)
            ->compare();

        // No exception
        $this->assertTrue(true);
    }

    public function testCompareWithoutFields(): void
    {
        $this->createComparator()->compare();

        // No exception
        $this->assertTrue(true);
    }

    public function testDifferentTypes(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $bar = $this->createField('foo', 'bigInteger', ['nullable' => true, 'default' => null]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(self::EXCEPTION_MESSAGE_PREFIX);

        $comparator->compare();
    }

    public function testDifferentOptions(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $bar = $this->createField('foo', 'integer', ['nullable' => true, 'default' => 42]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(self::EXCEPTION_MESSAGE_PREFIX);

        $comparator->compare();
    }

    public function testDifferentOptionsItemsCount(): void
    {
        $foo = $this->createField('foo', 'integer', ['default' => null]);
        $bar = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(self::EXCEPTION_MESSAGE_PREFIX);

        $comparator->compare();
    }

    public function testDifferentPrimaryFlag(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => 42])->setPrimary(true);
        $bar = $this->createField('foo', 'integer', ['nullable' => true, 'default' => 42])->setPrimary(false);
        $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar)
            ->compare();

        // No exception
        $this->assertTrue(true);
    }

    public function testDifferentColumnName(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => 42]);
        $bar = $this->createField('bar', 'integer', ['nullable' => true, 'default' => 42]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The field comparator only accepts fields with the same column name.');

        $comparator->addField('bar', $bar);
    }

    private function createComparator(): FieldComparator
    {
        return new FieldComparator();
    }

    private function createField(string $column, string $type = 'integer', array $options = []): Field
    {
        $field = (new Field())
            ->setType($type)
            ->setColumn($column);
        // Apply options
        $optionsMap = $field->getOptions();
        foreach ($options as $key => $value) {
            $optionsMap->set($key, $value);
        }
        return $field;
    }
}
