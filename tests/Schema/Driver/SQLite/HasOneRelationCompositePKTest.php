<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLite;

use Cycle\Schema\Tests\Relation\HasOneRelationCompositePKTest as BaseTest;

class HasOneRelationCompositePKTest extends BaseTest
{
    public const DRIVER = 'sqlite';
}
