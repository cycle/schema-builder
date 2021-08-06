<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLite;

use Cycle\Schema\Tests\Relation\Morphed\BelongsToMorphedRelationTest as BaseTest;

class BelongsToMorphedRelationTest extends BaseTest
{
    public const DRIVER = 'sqlite';
}
