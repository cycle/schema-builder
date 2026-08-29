<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Generator\GenerateTypecast;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\User;

abstract class TypecastGeneratorTest extends BaseTest
{
    public function testCompiledUser(): void
    {
        $e = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'user');

        $c = new Compiler();
        $schema = $c->compile($r, [new RenderTables(), new GenerateTypecast()]);

        $this->assertSame('int', $schema['user'][Schema::TYPECAST]['p_id']);
        $this->assertSame('float', $schema['user'][Schema::TYPECAST]['p_balance']);
        $this->assertSame('datetime', $schema['user'][Schema::TYPECAST]['p_created_at']);
        $this->assertSame('string', $schema['user'][Schema::TYPECAST]['p_name']);

        $this->assertTrue(in_array($schema['user'][Schema::TYPECAST]['p_id'], ['int', 'bool']));
    }

    /**
     * @dataProvider dataTypecast
     */
    public function testTypecast(Field $field, ?string $expectedTypecast): void
    {
        $entity = new Entity();
        $entity->setRole('entityForTypecast');
        $entity->setClass(\Cycle\Schema\Tests\Fixtures\EntityForTypecast::class);
        $entity->getFields()->set($field->getColumn(), $field);

        $r = new Registry($this->dbal);
        $r->register($entity)->linkTable($entity, 'default', 'entityForTypecast');

        $c = new Compiler();
        $c->compile($r, [new RenderTables(), new GenerateTypecast()]);
        self::assertSame($expectedTypecast, $field->getTypecast());
    }

    public function dataTypecast(): iterable
    {
        // based on property type
        yield 'int_integer' => [
            (new Field())->setType('integer')->setColumn('int_integer'),
            'int',
        ];

        yield 'int_tinyInteger' => [
            (new Field())->setType('tinyInteger')->setColumn('int_tinyInteger'),
            'int',
        ];

        yield 'int_bigInteger' => [
            (new Field())->setType('bigInteger')->setColumn('int_bigInteger'),
            'int',
        ];

        yield 'bool_boolean' => [
            (new Field())->setType('boolean')->setColumn('bool_boolean'),
            'bool',
        ];

        yield 'string_string' => [
            (new Field())->setType('string')->setColumn('string_string'),
            'string',
        ];

        // based on orm type
        yield '_integer' => [
            (new Field())->setType('integer')->setColumn('_integer'),
            'int',
        ];

        yield '_boolean' => [
            (new Field())->setType('boolean')->setColumn('_boolean'),
            'bool',
        ];

        yield '_string' => [
            (new Field())->setType('string')->setColumn('_string'),
            'string',
        ];

        // based on property type
        yield 'int_boolean' => [
            (new Field())->setType('boolean')->setColumn('int_boolean'),
            'int',
        ];

        yield 'int_string' => [
            (new Field())->setType('string')->setColumn('int_string'),
            'int',
        ];

        yield 'bool_integer' => [
            (new Field())->setType('integer')->setColumn('bool_integer'),
            'bool',
        ];

        yield 'bool_string' => [
            (new Field())->setType('string')->setColumn('bool_string'),
            'bool',
        ];

        yield 'string_boolean' => [
            (new Field())->setType('boolean')->setColumn('string_boolean'),
            'string',
        ];

        yield 'string_integer' => [
            (new Field())->setType('integer')->setColumn('string_integer'),
            'string',
        ];
    }
}
