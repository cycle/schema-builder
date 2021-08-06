<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLite;

use Cycle\Schema\Tests\Relation\ManyToManyRelationTest as BaseTest;

class ManyToManyRelationTest extends BaseTest
{
    public const DRIVER = 'sqlite';
}
