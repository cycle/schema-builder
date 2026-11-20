<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Exception\FieldException;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\FieldException\EmbeddedPrimaryKeyException;
use Testo\Assert;
use Testo\Test;

#[Test]
final class EmbeddedPrimaryKeyExceptionTest
{
    public function explainsTheCollision(): void
    {
        $embed = new Entity();
        $embed->setRole('address');

        $e = new EmbeddedPrimaryKeyException($embed, 'id');

        Assert::same($e->getMessage(), 'Entity `address` has conflicted field `id`.');
        Assert::same($e->getName(), 'Embedded entity primary key collision');
        Assert::string($e->getSolution())
            ->startsWith('The primary key of the composite entity must be projected onto the embedded entity.')
            ->contains('remove its definition from the column list in the schema.');
    }
}
