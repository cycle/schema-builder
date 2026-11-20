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

class FieldTraitTest
{
    use FieldTrait;

    /** @var OptionSchema */
    private $options;

    /** @var string */
    private $source;

    #[\Testo\Test]
    public function testGetsFieldShouldReturnFieldIfItExists(): void
    {
        $entity = new Entity();
        $entity->getFields()->set('id', $field = new Field());

        \Testo\Assert::same($this->getField($entity, 123), $field);
    }

    #[\Testo\Test]
    public function testGetsFieldShouldThrowAnExceptionIdFieldNotExists(): void
    {
        $entity = new Entity();
        $entity->setRole('post');

        \Testo\Expect::exception(RelationException::class)->withMessageContaining('Field `post`.`id` does not exists, referenced by `test`');

        $this->getField($entity, 123);
    }

    #[\Testo\Test]
    public function testGetsFieldsShouldReturnFieldsMapForSingleField(): void
    {
        $entity = new Entity();
        $entity->getFields()->set('id', $field = (new Field())->setColumn('id'));

        $fields = $this->getFields($entity, 123);

        \Testo\Assert::count($fields, 1);
        \Testo\Assert::same($fields->get('id'), $field);
    }

    #[\Testo\Test]
    public function testGetsFieldsShouldReturnFieldsMapForMultipleField(): void
    {
        $entity = new Entity();
        $entity->getFields()
            ->set('id', $fieldId = (new Field())->setColumn('id'))
            ->set('slug', $fieldSlug = (new Field())->setColumn('slug'));

        $fields = $this->getFields($entity, 234);

        \Testo\Assert::count($fields, 2);
        \Testo\Assert::same($fields->get('id'), $fieldId);
        \Testo\Assert::same($fields->get('slug'), $fieldSlug);
    }

    #[\Testo\Test]
    public function testGetsFieldsShouldThrowAnExcpetionIfFieldNotExists(): void
    {
        $entity = new Entity();
        $entity->setRole('post');
        $entity->getFields()
            ->set('id', $fieldId = (new Field())->setColumn('id'));

        \Testo\Expect::exception(RelationException::class)->withMessageContaining('Field `post`.`slug` does not exists, referenced by `test`');

        $this->getFields($entity, 234);
    }

    #[\Testo\Test]
    public function testEnsureFieldIfFieldExistsItShouldNotBeCreated(): void
    {
        $target = new Entity();

        $outer = (new Field())->setColumn('id');
        \Testo\Assert::false($outer->isReferenced());

        $target->getFields()->set('id', $fieldId = (new Field())->setColumn('id'));

        $this->ensureField($target, 'id', $outer);

        \Testo\Assert::true($outer->isReferenced());
        \Testo\Assert::count($target->getFields(), 1);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('nullableOrders')]
    public function testEnsureFieldMergesNullableOfRelationGeneratedField(bool $first, bool $second): void
    {
        $target = new Entity();
        $outer = (new Field())->setColumn('id')->setType('primary');

        $this->ensureField($target, 'user_id', $outer, $first);
        $this->ensureField($target, 'user_id', $outer, $second);

        $field = $target->getFields()->get('user_id');
        \Testo\Assert::true($field->isRelationGenerated());
        \Testo\Assert::true($field->getOptions()->get(Column::OPT_NULLABLE));
    }

    public function nullableOrders(): iterable
    {
        yield 'not nullable first' => [false, true];
        yield 'nullable first' => [true, false];
    }

    #[\Testo\Test]
    public function testEnsureFieldDoesNotChangeUserDefinedField(): void
    {
        $target = new Entity();
        $outer = (new Field())->setColumn('id')->setType('primary');
        $target->getFields()->set('user_id', $userField = (new Field())->setColumn('user_id')->setType('int'));

        $this->ensureField($target, 'user_id', $outer, true);

        \Testo\Assert::false($userField->isRelationGenerated());
        \Testo\Assert::false($userField->getOptions()->has(Column::OPT_NULLABLE));
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('outerFieldTypes')]
    public function testEnsureFieldIfFieldNotExistsItShouldBeCreated(
        string $originalType,
        string $type,
        bool $nullable,
    ): void {
        $target = new Entity();

        $outer = (new Field())
            ->setColumn('name')
            ->setType($originalType)
            ->setTypecast('typecast');

        \Testo\Assert::false($outer->isReferenced());

        $target->getFields()->set('id', $fieldId = (new Field())->setColumn('id'));

        $this->ensureField($target, 'name', $outer, $nullable);

        \Testo\Assert::true($outer->isReferenced());
        \Testo\Assert::count($target->getFields(), 2);

        $resultField = $target->getFields()->get('name');

        \Testo\Assert::same($resultField->getType(), $type);
        \Testo\Assert::same($resultField->getTypecast(), 'typecast');
        \Testo\Assert::same($resultField->getColumn(), $outer->getColumn());

        if ($nullable) {
            \Testo\Assert::true($resultField->getOptions()->get(Column::OPT_NULLABLE));
        } else {
            \Testo\Assert::false($resultField->getOptions()->has(Column::OPT_NULLABLE));
        }
    }

    public function outerFieldTypes(): iterable
    {
        yield 'primary' => [
            'primary',
            'int',
            false,
        ];
        yield 'bigPrimary' => [
            'bigPrimary',
            'bigint',
            true,
        ];
        yield 'smallPrimary' => [
            'smallPrimary',
            'smallint',
            true,
        ];
        yield 'test' => [
            'test',
            'test',
            true,
        ];
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->options = (new OptionSchema([]))->withTemplate([
            123 => 'id',
            234 => ['id', 'slug'],
        ]);

        $this->source = 'test';
    }

    protected function getOptions(): OptionSchema
    {
        return $this->options;
    }
}
