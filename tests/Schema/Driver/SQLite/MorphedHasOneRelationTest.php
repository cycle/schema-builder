<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLite;

use Cycle\Schema\Tests\Relation\Morphed\MorphedHasOneRelationTest as BaseTest;

class MorphedHasOneRelationTest extends BaseTest
{
    public const DRIVER = 'sqlite';
}
