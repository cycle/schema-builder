<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition;

abstract class Inheritance
{
    public function __construct(private string $type)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }
}
