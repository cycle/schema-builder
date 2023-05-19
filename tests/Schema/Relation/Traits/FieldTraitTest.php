<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Relation\Traits;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\Traits\FieldTrait;
use Cycle\Schema\Table\Column;
use PHPUnit\Framework\TestCase;

class FieldTraitTest extends TestCase
{
    use FieldTrait;

    /** @var OptionSchema */
    private $options;
    /** @var string */
    private $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->options = (new OptionSchema([]))->withTemplate([
            123 => 'id',
            234 => ['id', 'slug'],
        ]);

        $this->source = 'test';
    }

    public function testGetsFieldShouldReturnFieldIfItExists(): void
    {
        $entity = new Entity();
        $entity->getFields()->set('id', $field = new Field());

        $this->assertSame($field, $this->getField($entity, 123));
    }

    public function testGetsFieldShouldThrowAnExceptionIdFieldNotExists(): void
    {
        $entity = new Entity();
        $entity->setRole('post');

        $this->expectException(RelationException::class);
        $this->expectExceptionMessage('Field `post`.`id` does not exists, referenced by `test`');

        $this->getField($entity, 123);
    }

    public function testGetsFieldsShouldReturnFieldsMapForSingleField(): void
    {
        $entity = new Entity();
        $entity->getFields()->set('id', $field = (new Field())->setColumn('id'));

        $fields = $this->getFields($entity, 123);

        $this->assertCount(1, $fields);
        $this->assertSame($field, $fields->get('id'));
    }

    public function testGetsFieldsShouldReturnFieldsMapForMultipleField(): void
    {
        $entity = new Entity();
        $entity->getFields()
            ->set('id', $fieldId = (new Field())->setColumn('id'))
            ->set('slug', $fieldSlug = (new Field())->setColumn('slug'));

        $fields = $this->getFields($entity, 234);

        $this->assertCount(2, $fields);
        $this->assertSame($fieldId, $fields->get('id'));
        $this->assertSame($fieldSlug, $fields->get('slug'));
    }

    public function testGetsFieldsShouldThrowAnExcpetionIfFieldNotExists(): void
    {
        $entity = new Entity();
        $entity->setRole('post');
        $entity->getFields()
            ->set('id', $fieldId = (new Field())->setColumn('id'));

        $this->expectException(RelationException::class);
        $this->expectExceptionMessage('Field `post`.`slug` does not exists, referenced by `test`');

        $this->getFields($entity, 234);
    }

    public function testEnsureFieldIfFieldExistsItShouldNotBeCreated(): void
    {
        $target = new Entity();

        $outer = (new Field())->setColumn('id');
        $this->assertFalse($outer->isReferenced());

        $target->getFields()->set('id', $fieldId = (new Field())->setColumn('id'));

        $this->ensureField($target, 'id', $outer);

        $this->assertTrue($outer->isReferenced());
        $this->assertCount(1, $target->getFields());
    }

    /**
     * @dataProvider outerFieldTypes
     */
    public function testEnsureFieldIfFieldNotExistsItShouldBeCreated(
        string $originalType,
        string $type,
        bool $nullable
    ): void {
        $target = new Entity();

        $outer = (new Field())
            ->setColumn('name')
            ->setType($originalType)
            ->setTypecast('typecast');

        $this->assertFalse($outer->isReferenced());

        $target->getFields()->set('id', $fieldId = (new Field())->setColumn('id'));

        $this->ensureField($target, 'name', $outer, $nullable);

        $this->assertTrue($outer->isReferenced());
        $this->assertCount(2, $target->getFields());

        $resultField = $target->getFields()->get('name');

        $this->assertSame($type, $resultField->getType());
        $this->assertSame('typecast', $resultField->getTypecast());
        $this->assertSame($outer->getColumn(), $resultField->getColumn());

        if ($nullable) {
            $this->assertTrue($resultField->getOptions()->get(Column::OPT_NULLABLE));
        } else {
            $this->assertFalse($resultField->getOptions()->has(Column::OPT_NULLABLE));
        }
    }

    public function outerFieldTypes(): array
    {
        return [
            ['primary', 'int', false],
            ['bigPrimary', 'bigint', true],
            ['test', 'test', true],
        ];
    }

    /**
     * @return OptionSchema
     */
    protected function getOptions(): OptionSchema
    {
        return $this->options;
    }
}
