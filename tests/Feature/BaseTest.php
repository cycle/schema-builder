<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Testing\Database\DatabaseTestCase;

abstract class BaseTest extends DatabaseTestCase
{
    protected function getDriver(): DriverInterface
    {
        return $this->database->getDriver();
    }
}
