<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Relation;

use Cycle\ORM\Relation;
use Cycle\Schema\Exception\OptionException;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\RelationSchema;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class OptionSchemaTest
{
    public function testAliases(): void
    {
        $options = new OptionSchema([
            'alias' => Relation::TYPE,
        ]);

        $options = $options->withTemplate([
            Relation::TYPE => 200,
        ]);

        Assert::same($options->get(Relation::TYPE), 200);

        $options = $options->withOptions([
            'alias' => 100,
        ]);

        Assert::same($options->get(Relation::TYPE), 100);
    }

    public function testInvalidAlias(): void
    {
        $options = new OptionSchema([
            'alias' => Relation::TYPE,
        ]);

        $options = $options->withTemplate([
            Relation::TYPE => 200,
        ]);

        Expect::exception(OptionException::class);

        $options->withOptions([
            'unknown' => 100,
        ]);
    }

    public function testInvalidAlias2(): void
    {
        $options = new OptionSchema([
            'alias' => Relation::TYPE,
        ]);

        $options = $options->withTemplate([
            Relation::TYPE => 200,
        ])->withOptions([
            'alias' => 100,
        ]);

        Expect::exception(OptionException::class);

        $options->get(RelationSchema::FK_ACTION);
    }

    public function testDebugInfo(): void
    {
        $options = new OptionSchema([
            'alias' => Relation::TYPE,
        ]);

        $options = $options->withTemplate([
            Relation::TYPE => 200,
        ])->withOptions([
            'alias' => 100,
        ]);

        Assert::same($options->__debugInfo(), [
            'alias' => 100,
        ]);
    }
}
