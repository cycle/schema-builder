<div align="center">
    <a href="https://cycle-orm.dev" target="_blank">
        <picture>
            <source media="(prefers-color-scheme: dark)" srcset="https://github.com/cycle/.github/blob/main/logo/words-vector-dark.svg?raw=true">
            <img width="50%" align="center" src="https://github.com/cycle/.github/blob/main/logo/words-vector-light.svg?raw=true" alt="CycleORM Logo">
        </picture>
    </a>
</div>

<p align="center">Cycle ORM Schema Builder</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://cycle-orm.dev/docs/schema-dynamic-schema)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/cycle)
[![Discord](https://img.shields.io/discord/538114875570913290?style=for-the-badge&logo=discord&labelColor=7289d9&logoColor=white&color=39456d)](https://discord.gg/spiralphp)

[![Psalm Level](https://shepherd.dev/github/cycle/schema-builder/level.svg)](https://shepherd.dev/github/cycle/schema-builder)
[![Type Coverage](https://shepherd.dev/github/cycle/schema-builder/coverage.svg)](https://shepherd.dev/github/cycle/schema-builder)
[![Codecov](https://codecov.io/gh/cycle/schema-builder/branch/2.x/graph/badge.svg)](https://codecov.io/gh/cycle/schema-builder)

</div>

<br />

Schema Builder configures the Cycle ORM and database schema from entity definitions, produced by
[annotations (attributes)](https://github.com/cycle/annotated) or by custom generators.

## Get Started

### Installation

```bash
composer require cycle/schema-builder
```

[![PHP](https://img.shields.io/packagist/php-v/cycle/schema-builder.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/schema-builder)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cycle/schema-builder.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/cycle/schema-builder)
[![License](https://img.shields.io/packagist/l/cycle/schema-builder.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/cycle/schema-builder.svg?style=flat-square)](https://packagist.org/packages/cycle/schema-builder/stats)

Requires PHP 8.2+.

### Configuration

```php
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

### Registering entities

Register an entity, add its columns and link it to a table:

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
$registry->register($entity);

// associate table
$registry->linkTable($entity, 'default', 'users');
```

### Compiling the schema

Generate the ORM schema with `Cycle\Schema\Compiler`:

```php
use Cycle\Schema\Compiler;

$schema = (new Compiler())->compile($registry);

$orm = $orm->with(schema: new \Cycle\ORM\Schema($schema));
```

More about the Schema Builder is in the [documentation](https://cycle-orm.dev/docs/schema-dynamic-schema#using-schema-builder).

## Testing

The driver tests need the databases from `tests/docker-compose.yml`:

```bash
cd tests
docker compose up -d
cd ..
composer test
```

The SQLite tests run without containers: `vendor/bin/phpunit --group driver-sqlite`.
