<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLServer;

use Cycle\Schema\Tests\Relation\Morphed\BelongsToMorphedRelationCompositePKTest as BaseTest;

class BelongsToMorphedRelationCompositePKTest extends BaseTest
{
    public const DRIVER = 'sqlserver';
}
