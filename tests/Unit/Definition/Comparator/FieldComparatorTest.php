<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Definition\Comparator;

use Cycle\Schema\Definition\Comparator\FieldComparator;
use Cycle\Schema\Definition\Field;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class FieldComparatorTest
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
        Assert::true(true);
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
        Assert::true(true);
    }

    public function testCompareWithoutFields(): void
    {
        $this->createComparator()->compare();

        // No exception
        Assert::true(true);
    }

    public function testDifferentTypes(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $bar = $this->createField('foo', 'bigInteger', ['nullable' => true, 'default' => null]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar);

        Expect::exception(\Exception::class)->withMessageMatchingRegex(self::EXCEPTION_MESSAGE_PREFIX);

        $comparator->compare();
    }

    public function testDifferentOptions(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $bar = $this->createField('foo', 'integer', ['nullable' => true, 'default' => 42]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar);

        Expect::exception(\Exception::class)->withMessageMatchingRegex(self::EXCEPTION_MESSAGE_PREFIX);

        $comparator->compare();
    }

    public function testDifferentOptionsItemsCount(): void
    {
        $foo = $this->createField('foo', 'integer', ['default' => null]);
        $bar = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar);

        Expect::exception(\Exception::class)->withMessageMatchingRegex(self::EXCEPTION_MESSAGE_PREFIX);

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
        Assert::true(true);
    }

    public function testDifferentColumnName(): void
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => 42]);
        $bar = $this->createField('bar', 'integer', ['nullable' => true, 'default' => 42]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo);

        Expect::exception(\InvalidArgumentException::class)->withMessageContaining('The field comparator only accepts fields with the same column name.');

        $comparator->addField('bar', $bar);
    }

    public function describesEveryConflictingField(): never
    {
        $foo = $this->createField('foo', 'integer', ['nullable' => true, 'default' => null])->setPrimary(true);
        $bar = $this->createField('foo', 'bigInteger', ['nullable' => false, 'default' => 42]);
        $comparator = $this->createComparator()
            ->addField('foo', $foo)
            ->addField('bar', $bar);

        Expect::exception(\Exception::class)->withMessage(
            "Different definitions are specified for the `foo` column:\n\n"
            . "foo:\n  type=integer primary nullable=true default=NULL\n\n"
            . "bar:\n  type=bigInteger nullable=false default=42",
        );

        $comparator->compare();
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
