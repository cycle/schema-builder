<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\ORM\Parser\TypecastInterface;

class Typecaster implements TypecastInterface
{
    public function cast(array $values): array {}

    public function setRules(array $rules): array {}
}
