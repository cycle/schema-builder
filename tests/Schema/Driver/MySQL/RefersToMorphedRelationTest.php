<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\MySQL;

use Cycle\Schema\Tests\Relation\Morphed\RefersToMorphedRelationTest as BaseTest;

/**
 * @group driver
 * @group driver-mysql
 */
class RefersToMorphedRelationTest extends BaseTest
{
    public const DRIVER = 'mysql';
}
