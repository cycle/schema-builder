<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Definition;

use Cycle\Schema\Definition\ForeignKey;
use PHPUnit\Framework\TestCase;

final class ForeignKeyTest extends TestCase
{
    public function testTarget(): void
    {
        $key = new ForeignKey();

        $key->setTarget('foo');
        $this->assertSame('foo', $key->getTarget());
    }

    public function testInnerColumns(): void
    {
        $key = new ForeignKey();

        $key->setInnerColumns(['field']);
        $this->assertSame(['field'], $key->getInnerColumns());
    }

    public function testOuterColumns(): void
    {
        $key = new ForeignKey();

        $key->setOuterColumns(['field']);
        $this->assertSame(['field'], $key->getOuterColumns());
    }

    public function testIndex(): void
    {
        $key = new ForeignKey();

        $key->createIndex(true);
        $this->assertTrue($key->isCreateIndex());
        $key->createIndex(false);
        $this->assertFalse($key->isCreateIndex());
    }

    public function testAction(): void
    {
        $key = new ForeignKey();

        $key->setAction('CASCADE');
        $this->assertSame('CASCADE', $key->getAction());
    }
}
