<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Definition;

use Cycle\Schema\Definition\ForeignKey;
use PHPUnit\Framework\TestCase;

final class ForeignKeyTest
{
    #[\Testo\Test]
    public function testTarget(): void
    {
        $key = new ForeignKey();

        $key->setTarget('foo');
        \Testo\Assert::same($key->getTarget(), 'foo');
    }

    #[\Testo\Test]
    public function testInnerColumns(): void
    {
        $key = new ForeignKey();

        $key->setInnerColumns(['field']);
        \Testo\Assert::same($key->getInnerColumns(), ['field']);
    }

    #[\Testo\Test]
    public function testOuterColumns(): void
    {
        $key = new ForeignKey();

        $key->setOuterColumns(['field']);
        \Testo\Assert::same($key->getOuterColumns(), ['field']);
    }

    #[\Testo\Test]
    public function testIndex(): void
    {
        $key = new ForeignKey();

        $key->createIndex(true);
        \Testo\Assert::true($key->isCreateIndex());
        $key->createIndex(false);
        \Testo\Assert::false($key->isCreateIndex());
    }

    #[\Testo\Test]
    public function testAction(): void
    {
        $key = new ForeignKey();

        $key->setAction('CASCADE');
        \Testo\Assert::same($key->getAction(), 'CASCADE');
    }
}
