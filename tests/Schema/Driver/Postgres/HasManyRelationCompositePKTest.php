<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\Postgres;

use Cycle\Schema\Tests\Relation\HasManyRelationCompositePKTest as BaseTest;

class HasManyRelationCompositePKTest extends BaseTest
{
    public const DRIVER = 'postgres';
}
