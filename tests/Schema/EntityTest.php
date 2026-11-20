<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\ForeignKey;
use Cycle\Schema\Definition\Inheritance\JoinedTable;
use Cycle\Schema\Definition\Inheritance\SingleTable;
use Cycle\Schema\Definition\Relation;
use Cycle\Schema\Exception\EntityException;
use Cycle\Schema\Exception\FieldException;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\SchemaModifierInterface;
use PHPUnit\Framework\TestCase;

class EntityTest
{
    #[\Testo\Test]
    public function testRole(): void
    {
        $e = new Entity();
        $e->setRole('role');
        \Testo\Assert::same($e->getRole(), 'role');
    }

    #[\Testo\Test]
    public function testTypecast(): void
    {
        $e = new Entity();
        $e->setTypecast('FooTypecaster');
        \Testo\Assert::same($e->getTypecast(), 'FooTypecaster');
    }

    #[\Testo\Test]
    public function testTypecastAsArray(): void
    {
        $e = new Entity();
        $e->setTypecast(['FooTypecaster', 'BarTypecaster']);
        \Testo\Assert::same($e->getTypecast(), ['FooTypecaster', 'BarTypecaster']);
    }

    #[\Testo\Test]
    public function testTypecastAsNull(): void
    {
        $e = new Entity();
        $e->setTypecast(null);
        \Testo\Assert::null($e->getTypecast());
    }

    #[\Testo\Test]
    public function testFields(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', new Field());

        \Testo\Assert::true($e->getFields()->has('id'));
        $e->getFields()->remove('id');
        \Testo\Assert::false($e->getFields()->has('id'));
    }

    #[\Testo\Test]
    public function testForeignKeys(): void
    {
        $entity = new Entity();

        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $entity->getForeignKeys()->set($key);

        \Testo\Assert::true($entity->getForeignKeys()->has($key));
        $entity->getForeignKeys()->remove($key);
        \Testo\Assert::false($entity->getForeignKeys()->has($key));
    }

    #[\Testo\Test]
    public function testPrimaryFields(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', (new Field())->setPrimary(true));
        $e->getFields()->set('name', new Field());
        $e->getFields()->set('alternate_primary', (new Field())->setType('primary'));
        $e->getFields()->set('another_primary', (new Field())->setType('bigPrimary'));

        \Testo\Assert::same($e->getPrimaryFields()->getNames(), ['id', 'alternate_primary', 'another_primary']);
        \Testo\Assert::true($e->hasPrimaryKey());
    }

    #[\Testo\Test]
    public function testSetPrimaryKeys(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id'));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryColumns(['id', 'slug']);

        \Testo\Assert::same($e->getPrimaryFields()->getNames(), ['p_id', 'p_slug']);

        \Testo\Assert::true($e->hasPrimaryKey());
    }

    #[\Testo\Test]
    public function testSetPrimaryKeysShouldThrowAnExceptionWhenUsedNonExistsColumn(): void
    {
        \Testo\Expect::exception(FieldException::class)->withMessageContaining('Undefined field with column name `test`.');

        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id'));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryColumns(['test', 'test1', 'slug']);
    }

    #[\Testo\Test]
    public function testPrimaryKeysShouldReturnEmptyArrayWithoutPK(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', new Field());

        \Testo\Assert::same($e->getPrimaryFields()->getNames(), []);
        \Testo\Assert::false($e->hasPrimaryKey());
    }

    #[\Testo\Test]
    public function testPrimaryKeysShouldThrowAnExceptionWhenNumberOfPKsNotMatches(): void
    {
        \Testo\Expect::exception(EntityException::class)->withMessageContaining('Ambiguous primary key definition for `role`.');

        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id')->setPrimary(true));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryColumns(['id', 'slug']);

        $e->getPrimaryFields();
    }

    #[\Testo\Test]
    public function testFieldOptions(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', new Field());

        $e->getFields()->get('id')->getOptions()->set('name', 'value');
        \Testo\Assert::same($e->getFields()->get('id')->getOptions()->get('name'), 'value');
    }

    #[\Testo\Test]
    public function testGetUndefinedOption(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $e->getFields()->set('id', new Field());

        \Testo\Expect::exception(\Cycle\Schema\Exception\OptionException::class);

        $e->getFields()->get('id')->getOptions()->get('name');
    }

    #[\Testo\Test]
    public function testSetRelation(): void
    {
        $e = new Entity();
        $e->setRole('role');
        \Testo\Assert::same($e->getRole(), 'role');

        $e->getRelations()->set('test', new Relation());

        \Testo\Assert::true($e->getRelations()->has('test'));
    }

    #[\Testo\Test]
    public function testGetUndefined(): void
    {
        $e = new Entity();
        $e->setRole('role');
        \Testo\Assert::same($e->getRole(), 'role');

        \Testo\Expect::exception(RelationException::class);

        $e->getRelations()->get('test');
    }

    #[\Testo\Test]
    public function testSetRelationDouble(): void
    {
        $e = new Entity();
        $e->setRole('role');
        \Testo\Assert::same($e->getRole(), 'role');
        $e->getRelations()->set('test', new Relation());

        \Testo\Expect::exception(RelationException::class);

        $e->getRelations()->set('test', new Relation());
    }

    #[\Testo\Test]
    public function testRelationNoTarget(): void
    {
        $e = new Entity();
        $e->setRole('role');
        \Testo\Assert::same($e->getRole(), 'role');
        $e->getRelations()->set('test', new Relation());

        \Testo\Expect::exception(RelationException::class);

        $e->getRelations()->get('test')->getTarget();
    }

    #[\Testo\Test]
    public function testRelationNoType(): void
    {
        $e = new Entity();
        $e->setRole('role');
        \Testo\Assert::same($e->getRole(), 'role');
        $e->getRelations()->set('test', new Relation());

        \Testo\Expect::exception(RelationException::class);

        $e->getRelations()->get('test')->getType();
    }

    #[\Testo\Test]
    public function testMapper(): void
    {
        $e = new Entity();
        $e->setMapper('mapper');

        \Testo\Assert::same($e->getMapper(), 'mapper');
    }

    #[\Testo\Test]
    public function testSource(): void
    {
        $e = new Entity();
        $e->setSource('source');

        \Testo\Assert::same($e->getSource(), 'source');
    }

    #[\Testo\Test]
    public function testScope(): void
    {
        $e = new Entity();
        $e->setScope('constrain');

        \Testo\Assert::same($e->getScope(), 'constrain');
    }

    #[\Testo\Test]
    public function testRepository(): void
    {
        $e = new Entity();
        $e->setRepository('repository');

        \Testo\Assert::same($e->getRepository(), 'repository');
    }

    #[\Testo\Test]
    public function testDatabase(): void
    {
        $e = new Entity();

        \Testo\Assert::null($e->getDatabase());

        $e->setDatabase('database');

        \Testo\Assert::same($e->getDatabase(), 'database');
    }

    #[\Testo\Test]
    public function testTableName(): void
    {
        $e = new Entity();

        \Testo\Assert::null($e->getTableName());

        $e->setTableName('table_name');

        \Testo\Assert::same($e->getTableName(), 'table_name');
    }

    #[\Testo\Test]
    public function testSchema(): void
    {
        $e = new Entity();
        $e->setSchema(['schema']);

        \Testo\Assert::same($e->getSchema(), ['schema']);
    }

    #[\Testo\Test]
    public function testSingleTableInheritance(): void
    {
        $e = new Entity();
        $e->setInheritance($inheritance = new SingleTable());

        \Testo\Assert::same($e->getInheritance(), $inheritance);
    }

    #[\Testo\Test]
    public function testJoinedTableInheritance(): void
    {
        $e = new Entity();
        $parent = new Entity();
        $e->setInheritance($inheritance = new JoinedTable($parent));

        \Testo\Assert::same($e->getInheritance(), $inheritance);
    }

    #[\Testo\Test]
    public function testSchemaModifierWithRole(): void
    {
        $modifier = \Mockery::mock(SchemaModifierInterface::class)->shouldIgnoreMissing();
        $modifier->shouldReceive('withRole')->once()->with('my-entity', \Mockery::andAnyOtherArgs())->andReturnSelf();

        $e = new Entity();
        $e->setRole('my-entity');
        $e->addSchemaModifier($modifier);

        \Testo\Assert::same(iterator_to_array($e->getSchemaModifiers()), [$modifier]);
    }

    #[\Testo\Test]
    public function testSchemaModifier(): void
    {
        $modifier = \Mockery::mock(SchemaModifierInterface::class)->shouldIgnoreMissing();
        $e = new Entity();

        \Testo\Expect::exception(EntityException::class)->withMessageContaining('Entity must have a `role` to be able to add a modifier.');

        $e->addSchemaModifier($modifier);
    }

    #[\Testo\Test]
    public function testMergeTwoEntities(): void
    {
        $e = new Entity();
        $e->getRelations()->set('test', new Relation());
        $e->getFields()->set('id', $idField = (new Field())->setType('int')->setColumn('id'));
        $e->getFields()->set('name', $nameField = (new Field())->setType('string')->setColumn('name'));

        \Testo\Assert::same($e->getFields()->getNames(), ['id', 'name']);

        $e2 = new Entity();
        $e2->getRelations()->set('test2', new Relation());
        $e2->getFields()->set('id', $idField2 = (new Field())->setType('string')->setColumn('id'));
        $e2->getFields()->set('type', $typeField = (new Field())->setType('string')->setColumn('type'));

        $e->merge($e2);

        \Testo\Assert::same($e->getFields()->get('id'), $idField);
        \Testo\Assert::notSame($e->getFields()->get('id'), $idField2);
        \Testo\Assert::same($e->getFields()->get('name'), $nameField);
        \Testo\Assert::same($e->getFields()->get('type'), $typeField);

        \Testo\Assert::same($e2->getFields()->get('id'), $idField2);

        \Testo\Assert::true($e->getRelations()->has('test'));
        \Testo\Assert::true($e->getRelations()->has('test2'));
    }
}
