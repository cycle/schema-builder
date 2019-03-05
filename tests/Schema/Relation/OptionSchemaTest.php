<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Relation;

use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\RelationSchema;
use PHPUnit\Framework\TestCase;

class OptionSchemaTest extends TestCase
{
    public function testAliases()
    {
        $options = new OptionSchema([
            'alias' => RelationSchema::BIND_INTERFACE
        ]);

        $options = $options->withTemplate([
            RelationSchema::BIND_INTERFACE => 200
        ]);

        $this->assertSame(200, $options->get(RelationSchema::BIND_INTERFACE));

        $options = $options->withOptions([
            'alias' => 100
        ]);

        $this->assertSame(100, $options->get(RelationSchema::BIND_INTERFACE));
    }

    /**
     * @expectedException \Cycle\Schema\Exception\OptionException
     */
    public function testInvalidAlias()
    {
        $options = new OptionSchema([
            'alias' => RelationSchema::BIND_INTERFACE
        ]);

        $options = $options->withTemplate([
            RelationSchema::BIND_INTERFACE => 200
        ]);

        $options->withOptions([
            'unknown' => 100
        ]);
    }

    /**
     * @expectedException \Cycle\Schema\Exception\OptionException
     */
    public function testInvalidAlias2()
    {
        $options = new OptionSchema([
            'alias' => RelationSchema::BIND_INTERFACE
        ]);

        $options = $options->withTemplate([
            RelationSchema::BIND_INTERFACE => 200
        ])->withOptions([
            'alias' => 100
        ]);

        $options->get(RelationSchema::FK_ACTION);
    }

    public function testDebugInfo()
    {
        $options = new OptionSchema([
            'alias' => RelationSchema::BIND_INTERFACE
        ]);

        $options = $options->withTemplate([
            RelationSchema::BIND_INTERFACE => 200
        ])->withOptions([
            'alias' => 100
        ]);

        $this->assertSame([
            'alias' => 100
        ], $options->__debugInfo());
    }
}