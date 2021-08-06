<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLServer;

use Cycle\Schema\Tests\Relation\Morphed\MorphedHasManyRelationCompositePKTest as BaseTest;

class MorphedHasManyRelationCompositePKTest extends BaseTest
{
    public const DRIVER = 'sqlserver';
}
