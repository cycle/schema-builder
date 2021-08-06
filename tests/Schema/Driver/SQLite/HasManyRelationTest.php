<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLite;

use Cycle\Schema\Tests\Relation\HasManyRelationTest as BaseTest;

class HasManyRelationTest extends BaseTest
{
    public const DRIVER = 'sqlite';
}
