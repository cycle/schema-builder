<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\Postgres;

use Cycle\Schema\Tests\Relation\ManyToManyRelationCompositePKTest as BaseTest;

class ManyToManyRelationCompositePKTest extends BaseTest
{
    public const DRIVER = 'postgres';
}
