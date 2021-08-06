<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\Postgres;

use Cycle\Schema\Tests\Relation\HasOneRelationCompositePKTest as BaseTest;

class HasOneRelationCompositePKTest extends BaseTest
{
    public const DRIVER = 'postgres';
}
