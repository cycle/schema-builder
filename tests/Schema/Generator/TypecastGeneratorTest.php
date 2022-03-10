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

        $this->assertTrue(in_array($schema['user'][Schema::TYPECAST]['p_id'], ['int', 'bool']));
    }

    /**
     * @dataProvider dataBoolTypecast
     */
    public function testBoolTypecast(Field $field, ?string $expectedTypecast): void
    {
        $entity = new Entity();
        $entity->setRole('user');
        $entity->setClass(User::class);
        $entity->getFields()->set('field', $field->setColumn('field'));

        $r = new Registry($this->dbal);
        $r->register($entity)->linkTable($entity, 'default', 'user');

        $c = new Compiler();
        $c->compile($r, [new RenderTables(), new GenerateTypecast()]);
        self::assertSame($expectedTypecast, $field->getTypecast());
    }

    public function dataBoolTypecast(): iterable
    {
        yield [
            (new Field())->setType('boolean'),
            'bool',
        ];

        yield [
            (new Field())->setType('bool'),
            'bool',
        ];

        yield [
            (new Field())->setType('int'),
            'int',
        ];

        yield [
            (new Field())->setType('integer'),
            'int',
        ];

        yield [
            (new Field())->setType('boolean')->setTypecast('foo'),
            'foo',
        ];
    }
}
