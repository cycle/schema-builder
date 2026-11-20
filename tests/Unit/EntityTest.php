<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit;

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
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class EntityTest
{
    public function testRole(): void
    {
        $e = new Entity();
        $e->setRole('role');
        Assert::same($e->getRole(), 'role');
    }

    public function testTypecast(): void
    {
        $e = new Entity();
        $e->setTypecast('FooTypecaster');
        Assert::same($e->getTypecast(), 'FooTypecaster');
    }

    public function testTypecastAsArray(): void
    {
        $e = new Entity();
        $e->setTypecast(['FooTypecaster', 'BarTypecaster']);
        Assert::same($e->getTypecast(), ['FooTypecaster', 'BarTypecaster']);
    }

    public function testTypecastAsNull(): void
    {
        $e = new Entity();
        $e->setTypecast(null);
        Assert::null($e->getTypecast());
    }

    public function testFields(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', new Field());

        Assert::true($e->getFields()->has('id'));
        $e->getFields()->remove('id');
        Assert::false($e->getFields()->has('id'));
    }

    public function testForeignKeys(): void
    {
        $entity = new Entity();

        $key = new ForeignKey();
        $key->setTarget('foo');
        $key->setInnerColumns(['field']);
        $key->setOuterColumns(['field']);

        $entity->getForeignKeys()->set($key);

        Assert::true($entity->getForeignKeys()->has($key));
        $entity->getForeignKeys()->remove($key);
        Assert::false($entity->getForeignKeys()->has($key));
    }

    public function testPrimaryFields(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', (new Field())->setPrimary(true));
        $e->getFields()->set('name', new Field());
        $e->getFields()->set('alternate_primary', (new Field())->setType('primary'));
        $e->getFields()->set('another_primary', (new Field())->setType('bigPrimary'));

        Assert::same($e->getPrimaryFields()->getNames(), ['id', 'alternate_primary', 'another_primary']);
        Assert::true($e->hasPrimaryKey());
    }

    public function testSetPrimaryKeys(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id'));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryColumns(['id', 'slug']);

        Assert::same($e->getPrimaryFields()->getNames(), ['p_id', 'p_slug']);

        Assert::true($e->hasPrimaryKey());
    }

    public function testSetPrimaryKeysShouldThrowAnExceptionWhenUsedNonExistsColumn(): void
    {
        Expect::exception(FieldException::class)->withMessageContaining('Undefined field with column name `test`.');

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

        Assert::same($e->getPrimaryFields()->getNames(), []);
        Assert::false($e->hasPrimaryKey());
    }

    public function testPrimaryKeysShouldThrowAnExceptionWhenNumberOfPKsNotMatches(): void
    {
        Expect::exception(EntityException::class)->withMessageContaining('Ambiguous primary key definition for `role`.');

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
        Assert::same($e->getFields()->get('id')->getOptions()->get('name'), 'value');
    }

    public function testGetUndefinedOption(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $e->getFields()->set('id', new Field());

        Expect::exception(\Cycle\Schema\Exception\OptionException::class);

        $e->getFields()->get('id')->getOptions()->get('name');
    }

    public function testSetRelation(): void
    {
        $e = new Entity();
        $e->setRole('role');
        Assert::same($e->getRole(), 'role');

        $e->getRelations()->set('test', new Relation());

        Assert::true($e->getRelations()->has('test'));
    }

    public function testGetUndefined(): void
    {
        $e = new Entity();
        $e->setRole('role');
        Assert::same($e->getRole(), 'role');

        Expect::exception(RelationException::class);

        $e->getRelations()->get('test');
    }

    public function testSetRelationDouble(): void
    {
        $e = new Entity();
        $e->setRole('role');
        Assert::same($e->getRole(), 'role');
        $e->getRelations()->set('test', new Relation());

        Expect::exception(RelationException::class);

        $e->getRelations()->set('test', new Relation());
    }

    public function testRelationNoTarget(): void
    {
        $e = new Entity();
        $e->setRole('role');
        Assert::same($e->getRole(), 'role');
        $e->getRelations()->set('test', new Relation());

        Expect::exception(RelationException::class);

        $e->getRelations()->get('test')->getTarget();
    }

    public function testRelationNoType(): void
    {
        $e = new Entity();
        $e->setRole('role');
        Assert::same($e->getRole(), 'role');
        $e->getRelations()->set('test', new Relation());

        Expect::exception(RelationException::class);

        $e->getRelations()->get('test')->getType();
    }

    public function testMapper(): void
    {
        $e = new Entity();
        $e->setMapper('mapper');

        Assert::same($e->getMapper(), 'mapper');
    }

    public function testSource(): void
    {
        $e = new Entity();
        $e->setSource('source');

        Assert::same($e->getSource(), 'source');
    }

    public function testScope(): void
    {
        $e = new Entity();
        $e->setScope('constrain');

        Assert::same($e->getScope(), 'constrain');
    }

    public function testRepository(): void
    {
        $e = new Entity();
        $e->setRepository('repository');

        Assert::same($e->getRepository(), 'repository');
    }

    public function testDatabase(): void
    {
        $e = new Entity();

        Assert::null($e->getDatabase());

        $e->setDatabase('database');

        Assert::same($e->getDatabase(), 'database');
    }

    public function testTableName(): void
    {
        $e = new Entity();

        Assert::null($e->getTableName());

        $e->setTableName('table_name');

        Assert::same($e->getTableName(), 'table_name');
    }

    public function testSchema(): void
    {
        $e = new Entity();
        $e->setSchema(['schema']);

        Assert::same($e->getSchema(), ['schema']);
    }

    public function testSingleTableInheritance(): void
    {
        $e = new Entity();
        $e->setInheritance($inheritance = new SingleTable());

        Assert::same($e->getInheritance(), $inheritance);
    }

    public function testJoinedTableInheritance(): void
    {
        $e = new Entity();
        $parent = new Entity();
        $e->setInheritance($inheritance = new JoinedTable($parent));

        Assert::same($e->getInheritance(), $inheritance);
    }

    public function testSchemaModifierWithRole(): void
    {
        $modifier = \Mockery::mock(SchemaModifierInterface::class)->shouldIgnoreMissing();
        $modifier->shouldReceive('withRole')->once()->with('my-entity', \Mockery::andAnyOtherArgs())->andReturnSelf();

        $e = new Entity();
        $e->setRole('my-entity');
        $e->addSchemaModifier($modifier);

        Assert::same(iterator_to_array($e->getSchemaModifiers()), [$modifier]);
    }

    public function testSchemaModifier(): void
    {
        $modifier = \Mockery::mock(SchemaModifierInterface::class)->shouldIgnoreMissing();
        $e = new Entity();

        Expect::exception(EntityException::class)->withMessageContaining('Entity must have a `role` to be able to add a modifier.');

        $e->addSchemaModifier($modifier);
    }

    public function testMergeAddsForeignKeys(): void
    {
        $key = (new ForeignKey())->setTarget('foo')->setInnerColumns(['a'])->setOuterColumns(['b']);
        $e = new Entity();
        $e2 = new Entity();
        $e2->getForeignKeys()->set($key);

        $e->merge($e2);

        Assert::same(\iterator_to_array($e->getForeignKeys()), \iterator_to_array($e2->getForeignKeys()));
    }

    public function testPrimaryColumnsMatchingThePrimaryFields(): void
    {
        $e = new Entity();
        $e->getFields()->set('p_id', $id = (new Field())->setColumn('id')->setPrimary(true));

        $e->setPrimaryColumns(['id']);

        Assert::same(\iterator_to_array($e->getPrimaryFields()), ['p_id' => $id]);
    }

    public function testPrimaryColumnsDifferingFromThePrimaryFields(): never
    {
        $e = new Entity();
        $e->setRole('role');
        $e->getFields()->set('p_id', (new Field())->setColumn('id')->setPrimary(true));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryColumns(['slug']);

        Expect::exception(EntityException::class)->withMessage('Ambiguous primary key definition for `role`.');

        $e->getPrimaryFields();
    }

    public function testCloneIsIndependentOfTheOriginal(): void
    {
        $e = new Entity();
        $e->getOptions()->set('option', 1);
        $e->getFields()->set('p_id', (new Field())->setColumn('id'));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));
        $e->setPrimaryColumns(['id']);
        $e->getRelations()->set('author', (new Relation())->setTarget('author')->setType('belongsTo'));
        $e->getForeignKeys()->set((new ForeignKey())->setTarget('foo')->setInnerColumns(['a'])->setOuterColumns(['b']));

        $clone = clone $e;
        $clone->getOptions()->set('option', 2);
        $clone->getFields()->get('p_id')->getOptions()->set('nullable', true);
        $clone->getFields()->get('p_id')->getAttributes()->set('unsigned', true);
        $clone->getFields()->remove('p_slug');
        $clone->getPrimaryFields()->remove('p_id');
        $clone->getRelations()->get('author')->getOptions()->set('nullable', true);
        foreach ($clone->getForeignKeys() as $foreignKey) {
            $foreignKey->setTarget('bar');
        }
        $clone->getForeignKeys()->set((new ForeignKey())->setTarget('baz')->setInnerColumns(['c'])->setOuterColumns(['d']));

        Assert::same($e->getOptions()->get('option'), 1);
        Assert::false($e->getFields()->get('p_id')->getOptions()->has('nullable'));
        Assert::false($e->getFields()->get('p_id')->getAttributes()->has('unsigned'));
        Assert::true($e->getFields()->has('p_slug'));
        Assert::same($e->getPrimaryFields()->getNames(), ['p_id']);
        Assert::false($e->getRelations()->get('author')->getOptions()->has('nullable'));
        Assert::same(\array_map(
            static fn(ForeignKey $key): string => $key->getTarget(),
            \array_values(\iterator_to_array($e->getForeignKeys())),
        ), ['foo']);
    }

    public function testRelatedClassNamesLoseTheLeadingBackslash(): void
    {
        $e = new Entity();

        $e->setMapper('\App\Mapper')->setSource('\App\Source')->setScope('\App\Scope')->setRepository('\App\Repository');

        Assert::same(
            [$e->getMapper(), $e->getSource(), $e->getScope(), $e->getRepository()],
            ['App\Mapper', 'App\Source', 'App\Scope', 'App\Repository'],
        );
    }

    public function testMergeTwoEntities(): void
    {
        $e = new Entity();
        $e->getRelations()->set('test', new Relation());
        $e->getFields()->set('id', $idField = (new Field())->setType('int')->setColumn('id'));
        $e->getFields()->set('name', $nameField = (new Field())->setType('string')->setColumn('name'));

        Assert::same($e->getFields()->getNames(), ['id', 'name']);

        $e2 = new Entity();
        $e2->getRelations()->set('test2', new Relation());
        $e2->getFields()->set('id', $idField2 = (new Field())->setType('string')->setColumn('id'));
        $e2->getFields()->set('type', $typeField = (new Field())->setType('string')->setColumn('type'));

        $e->merge($e2);

        Assert::same($e->getFields()->get('id'), $idField);
        Assert::notSame($e->getFields()->get('id'), $idField2);
        Assert::same($e->getFields()->get('name'), $nameField);
        Assert::same($e->getFields()->get('type'), $typeField);

        Assert::same($e2->getFields()->get('id'), $idField2);

        Assert::true($e->getRelations()->has('test'));
        Assert::true($e->getRelations()->has('test2'));
    }
}
