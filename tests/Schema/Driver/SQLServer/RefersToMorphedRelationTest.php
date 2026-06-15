<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLServer;

use Cycle\Schema\Tests\Relation\Morphed\RefersToMorphedRelationTest as BaseTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class RefersToMorphedRelationTest extends BaseTest
{
    public const DRIVER = 'sqlserver';
}
