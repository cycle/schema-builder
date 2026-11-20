<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Definition;

use Cycle\Schema\Definition\ForeignKey;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ForeignKeyTest
{
    public function testTarget(): void
    {
        $key = new ForeignKey();

        $key->setTarget('foo');
        Assert::same($key->getTarget(), 'foo');
    }

    public function testInnerColumns(): void
    {
        $key = new ForeignKey();

        $key->setInnerColumns(['field']);
        Assert::same($key->getInnerColumns(), ['field']);
    }

    public function testOuterColumns(): void
    {
        $key = new ForeignKey();

        $key->setOuterColumns(['field']);
        Assert::same($key->getOuterColumns(), ['field']);
    }

    public function testIndex(): void
    {
        $key = new ForeignKey();

        $key->createIndex(true);
        Assert::true($key->isCreateIndex());
        $key->createIndex(false);
        Assert::false($key->isCreateIndex());
    }

    public function testAction(): void
    {
        $key = new ForeignKey();

        $key->setAction('CASCADE');
        Assert::same($key->getAction(), 'CASCADE');
    }
}
