<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Relation;

use Cycle\ORM\Relation;
use Cycle\Schema\Exception\OptionException;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\RelationSchema;
use PHPUnit\Framework\TestCase;

class OptionSchemaTest
{
    #[\Testo\Test]
    public function testAliases(): void
    {
        $options = new OptionSchema([
            'alias' => Relation::TYPE,
        ]);

        $options = $options->withTemplate([
            Relation::TYPE => 200,
        ]);

        \Testo\Assert::same($options->get(Relation::TYPE), 200);

        $options = $options->withOptions([
            'alias' => 100,
        ]);

        \Testo\Assert::same($options->get(Relation::TYPE), 100);
    }

    #[\Testo\Test]
    public function testInvalidAlias(): void
    {
        $options = new OptionSchema([
            'alias' => Relation::TYPE,
        ]);

        $options = $options->withTemplate([
            Relation::TYPE => 200,
        ]);

        \Testo\Expect::exception(OptionException::class);

        $options->withOptions([
            'unknown' => 100,
        ]);
    }

    #[\Testo\Test]
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

        \Testo\Expect::exception(OptionException::class);

        $options->get(RelationSchema::FK_ACTION);
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($options->__debugInfo(), [
            'alias' => 100,
        ]);
    }
}
