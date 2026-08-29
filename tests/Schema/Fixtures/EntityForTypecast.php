<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

class EntityForTypecast
{
    public $id;

    public int $int_integer;

    public int $int_tinyInteger;

    public int $int_bigInteger;

    public bool $bool_boolean;

    public string $string_string;

    public $_integer;

    public $_boolean;

    public $_string;

    public int $int_boolean;
    public int $int_string;

    public bool $bool_integer;
    public bool $bool_string;

    public string $string_boolean;
    public string $string_integer;
}
