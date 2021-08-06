<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\Postgres;

use Cycle\Schema\Tests\Relation\ManyToManyRelationTest as BaseTest;

class ManyToManyRelationTest extends BaseTest
{
    public const DRIVER = 'postgres';
}
