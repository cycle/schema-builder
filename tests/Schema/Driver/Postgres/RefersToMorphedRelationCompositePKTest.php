<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\Postgres;

use Cycle\Schema\Tests\Relation\Morphed\RefersToMorphedRelationCompositePKTest as BaseTest;

/**
 * @group driver
 * @group driver-postgres
 */
class RefersToMorphedRelationCompositePKTest extends BaseTest
{
    public const DRIVER = 'postgres';
}
