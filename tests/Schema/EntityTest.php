<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation;
use Cycle\Schema\Exception\EntityException;
use Cycle\Schema\Exception\FieldException;
use Cycle\Schema\Exception\RelationException;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    public function testRole(): void
    {
        $e = new Entity();
        $e->setRole('role');
        $this->assertSame('role', $e->getRole());
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

    public function testSetPrimaryKeys()
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id'));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryKeys(['id', 'slug']);

        $this->assertSame(['p_id', 'p_slug'], $e->getPrimaryFields()->getNames());
    }

    public function testSetPrimaryKeysShouldThrowAnExceptionWhenUsedNonExistsColumn()
    {
        $this->expectException(FieldException::class);
        $this->expectErrorMessage('Undefined field with column name `test`.');

        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id'));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryKeys(['test', 'test1', 'slug']);
    }

    public function testPrimaryKeysShouldReturnEmptyArrayWithoutPK(): void
    {
        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('id', new Field());

        $this->assertSame([], $e->getPrimaryFields()->getNames());
        $this->assertFalse($e->hasPrimaryKey());
    }

    public function testPrimaryKeysShouldThrowAnExceptionWhenNumberOfPKsNotMatches()
    {
        $this->expectException(EntityException::class);
        $this->expectErrorMessage('Ambiguous primary key definition for `role`.');

        $e = new Entity();
        $e->setRole('role');

        $e->getFields()->set('p_id', (new Field())->setColumn('id')->setPrimary(true));
        $e->getFields()->set('p_slug', (new Field())->setColumn('slug'));

        $e->setPrimaryKeys(['id', 'slug']);

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

    public function testConstrain(): void
    {
        $e = new Entity();
        $e->setConstrain('constrain');

        $this->assertSame('constrain', $e->getConstrain());
    }

    public function testRepository(): void
    {
        $e = new Entity();
        $e->setRepository('repository');

        $this->assertSame('repository', $e->getRepository());
    }

    public function testSchema(): void
    {
        $e = new Entity();
        $e->setSchema(['schema']);

        $this->assertSame(['schema'], $e->getSchema());
    }
}
