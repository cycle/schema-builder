# Cycle ORM - Schema Builder

[![Latest Stable Version](https://poser.pugx.org/cycle/schema-builder/version)](https://packagist.org/packages/cycle/schema-builder)
[![Build Status](https://github.com/cycle/schema-builder/workflows/build/badge.svg)](https://github.com/cycle/schema-builder/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/schema-builder/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/cycle/schema-builder/?branch=2.x)
[![Codecov](https://codecov.io/gh/cycle/schema-builder/graph/badge.svg)](https://codecov.io/gh/cycle/schema-builder)

Schema Builder package provides a convenient way to configure your ORM and Database schema via 
[annotations (attributes)](https://github.com/cycle/annotated) or custom generators.

## Installation

```bash
composer require cycle/schema-builder
```

## Configuration

```php
use Cycle\Migrations;
use Cycle\Database;
use Cycle\Database\Config;

$dbal = new Database\DatabaseManager(new Config\DatabaseConfig([
    'default' => 'default',
    'databases' => [
        'default' => [
            'connection' => 'sqlite'
        ]
    ],
    'connections' => [
        'sqlite' => new Config\SQLiteDriverConfig(
            connection: new Config\SQLite\MemoryConnectionConfig(),
            queryCache: true,
        ),
    ]
]));

$registry = new \Cycle\Schema\Registry($dbal);
```

We can now register our first entity, add its columns and link to a specific table:

```php
use Cycle\Schema\Definition;

$entity = new Definition\Entity();

$entity
    ->setRole('user')
    ->setClass(User::class);

// add fields
$entity->getFields()
    ->set('id', (new Definition\Field())->setType('primary')->setColumn('id')->setPrimary(true))
    ->set('name', (new Definition\Field())->setType('string(32)')->setColumn('user_name'));

// register entity
$r->register($entity);

// associate table
$r->linkTable($entity, 'default', 'users');
```
You can generate ORM schema immediately using `Cycle\Schema\Compiler`:

```php
use Cycle\Schema\Compiler;
$schema = (new Compiler())->compile($r);

$orm = $orm->with(schema: new \Cycle\ORM\Schema($schema));
```

You can find more information about Schema builder package [here](https://cycle-orm.dev/docs/advanced-schema-builder).

License:
--------
The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained
by [Spiral Scout](https://spiralscout.com).

