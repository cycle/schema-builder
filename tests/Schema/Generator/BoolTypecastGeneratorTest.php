<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Generator\BoolTypecastGenerator;
use Cycle\Schema\Registry;
use PHPUnit\Framework\TestCase;

class BoolTypecastGeneratorTest extends TestCase
{
    /**
     * @depends dataSetTypecast
     */
    public function testSetTypecast(Field $field, ?string $expectedTypecast)
    {
        $registry = $this->createMock(Registry::class);
        $registry
            ->expects(self::atLeastOnce())
            ->method('getFields')
            ->willReturn($field);
        $generator = new BoolTypecastGenerator();
        $generator->run($registry);
        self::assertSame($expectedTypecast, $field->getTypecast());
    }

    public function dataSetTypecast(): iterable
    {
        yield [
            (new Field())->setType('boolean'),
            'bool',
        ];

        yield [
            (new Field())->setType('boolean')->setTypecast('foo'),
            'foo',
        ];

        yield [
            (new Field())->setType('bool'),
            null,
        ];

        yield [
            (new Field())->setType('int'),
            null,
        ];
    }
}
