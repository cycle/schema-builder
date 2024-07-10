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

class EntityTest extends TestCase
{
    public function testRole(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());
    }

    public function testTypecast(): void
    {
        $e = new Entity();
        $e->setTypecast('FooTypecaster');
        $this->assertSame('FooTypecaster', $e->getTypecast());
    }

    public function testTypecastAsArray(): void
    {
        $e = new Entity();
        $e->setTypecast(['FooTypecaster', 'BarTypecaster']);
        $this->assertSame(['FooTypecaster', 'BarTypecaster'], $e->getTypecast());
    }

    public function testTypecastAsNull(): void
    {
        $e = new Entity();
        $e->setTypecast(null);
        $this->assertNull($e->getTypecast());
    }

    public function testFields(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', new Field());

        $this->assertTrue($e->getFields()->has('id'));
        $e->getFields()->remove('id');
        $this->assertFalse($e->getFields()->has('id'));
    }

    public function testForeignKeys(): void
    {
        $entity = new Entity();

        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $entity->getForeignKeys()->set($key);

        $this->assertTrue($entity->getForeignKeys()->has($key));
        $entity->getForeignKeys()->remove($key);
        $this->assertFalse($entity->getForeignKeys()->has($key));
    }

    public function testPrimaryFields(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', (new Field())->setPrimary(true));
        $e->getFields()->set('name', new Field());
        $e->getFields()->set('alternate_primary', (new Field())->setType('primary'));
        $e->getFields()->set('another_primary', (new Field())->setType('bigPrimary'));

        $this->assertSame(['id', 'alternate_primary', 'another_primary'], $e->getPrimaryFields()->getNames());
        $this->assertTrue($e->hasPrimaryKey());
    }

    public function testSetPrimaryKeys(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id'));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryColumns(['id', 'slug']);

        $this->assertSame(['p_id', 'p_slug'], $e->getPrimaryFields()->getNames());

        $this->assertTrue($e->hasPrimaryKey());
    }

    public function testSetPrimaryKeysShouldThrowAnExceptionWhenUsedNonExistsColumn(): void
    {
        $this->expectException(FieldException::class);
        $this->expectExceptionMessage('Undefined field with column name `test`.');

        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id'));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryColumns(['test', 'test1', 'slug']);
    }

    public function testPrimaryKeysShouldReturnEmptyArrayWithoutPK(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', new Field());

        $this->assertSame([], $e->getPrimaryFields()->getNames());
        $this->assertFalse($e->hasPrimaryKey());
    }

    public function testPrimaryKeysShouldThrowAnExceptionWhenNumberOfPKsNotMatches(): void
    {
        $this->expectException(EntityException::class);
        $this->expectExceptionMessage('Ambiguous primary key definition for `role`.');

        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id')->setPrimary(true));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryColumns(['id', 'slug']);

        $e->getPrimaryFields();
    }

    public function testFieldOptions(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', new Field());

        $e->getFields()->get('id')->getOptions()->set('name', 'value');
        $this->assertSame('value', $e->getFields()->get('id')->getOptions()->get('name'));
    }

    public function testGetUndefinedOption(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $e->getFields()->set('id', new Field());

        $this->expectException(\Cycle\Schema\Exception\OptionException::class);

        $e->getFields()->get('id')->getOptions()->get('name');
    }

    public function testSetRelation(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());

        $e->getRelations()->set('test', new Relation());

        $this->assertTrue($e->getRelations()->has('test'));
    }

    public function testGetUndefined(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());

        $this->expectException(RelationException::class);

        $e->getRelations()->get('test');
    }

    public function testSetRelationDouble(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());
        $e->getRelations()->set('test', new Relation());

        $this->expectException(RelationException::class);

        $e->getRelations()->set('test', new Relation());
    }

    public function testRelationNoTarget(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());
        $e->getRelations()->set('test', new Relation());

        $this->expectException(RelationException::class);

        $e->getRelations()->get('test')->getTarget();
    }

    public function testRelationNoType(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());
        $e->getRelations()->set('test', new Relation());

        $this->expectException(RelationException::class);

        $e->getRelations()->get('test')->getType();
    }

    public function testMapper(): void
    {
        $e = new Entity();
        $e->setMapper('mapper');

        $this->assertSame('mapper', $e->getMapper());
    }

    public function testSource(): void
    {
        $e = new Entity();
        $e->setSource('source');

        $this->assertSame('source', $e->getSource());
    }

    public function testScope(): void
    {
        $e = new Entity();
        $e->setScope('constrain');

        $this->assertSame('constrain', $e->getScope());
    }

    public function testRepository(): void
    {
        $e = new Entity();
        $e->setRepository('repository');

        $this->assertSame('repository', $e->getRepository());
    }

    public function testDatabase(): void
    {
        $e = new Entity();

        $this->assertNull($e->getDatabase());

        $e->setDatabase('database');

        $this->assertSame('database', $e->getDatabase());
    }

    public function testTableName(): void
    {
        $e = new Entity();

        $this->assertNull($e->getTableName());

        $e->setTableName('table_name');

        $this->assertSame('table_name', $e->getTableName());
    }

    public function testSchema(): void
    {
        $e = new Entity();
        $e->setSchema(['schema']);

        $this->assertSame(['schema'], $e->getSchema());
    }

    public function testSingleTableInheritance(): void
    {
        $e = new Entity();
        $e->setInheritance($inheritance = new SingleTable());

        $this->assertSame($inheritance, $e->getInheritance());
    }

    public function testJoinedTableInheritance(): void
    {
        $e = new Entity();
        $parent = new Entity();
        $e->setInheritance($inheritance = new JoinedTable($parent));

        $this->assertSame($inheritance, $e->getInheritance());
    }

    public function testSchemaModifierWithRole(): void
    {
        $mock = $this->createMock(SchemaModifierInterface::class);
        $mock->expects($this->once())->method('withRole')->with('my-entity')->willReturnSelf();

        $e = new Entity();
        $e->setRole('my-entity');
        $e->addSchemaModifier($modifier = $mock);

        $this->assertSame([$modifier], iterator_to_array($e->getSchemaModifiers()));
    }

    public function testSchemaModifier(): void
    {
        $mock = $this->createMock(SchemaModifierInterface::class);

        $e = new Entity();
        $e->addSchemaModifier($modifier = $mock);

        $this->assertSame([$modifier], iterator_to_array($e->getSchemaModifiers()));
    }

    public function testMergeTwoEntities(): void
    {
        $e = new Entity();
        $e->getRelations()->set('test', new Relation());
        $e->getFields()->set('id', $idField = (new Field())->setType('int')->setColumn('id'));
        $e->getFields()->set('name', $nameField = (new Field())->setType('string')->setColumn('name'));

        $this->assertSame(['id', 'name'], $e->getFields()->getNames());

        $e2 = new Entity();
        $e2->getRelations()->set('test2', new Relation());
        $e2->getFields()->set('id', $idField2 = (new Field())->setType('string')->setColumn('id'));
        $e2->getFields()->set('type', $typeField = (new Field())->setType('string')->setColumn('type'));

        $e->merge($e2);

        $this->assertSame($idField, $e->getFields()->get('id'));
        $this->assertNotSame($idField2, $e->getFields()->get('id'));
        $this->assertSame($nameField, $e->getFields()->get('name'));
        $this->assertSame($typeField, $e->getFields()->get('type'));

        $this->assertSame($idField2, $e2->getFields()->get('id'));

        $this->assertTrue($e->getRelations()->has('test'));
        $this->assertTrue($e->getRelations()->has('test2'));
    }
}
