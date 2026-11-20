<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Relation\Traits;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\Traits\FieldTrait;
use Cycle\Schema\Table\Column;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class FieldTraitTest
{
    use FieldTrait;

    /** @var OptionSchema */
    private $options;

    /** @var string */
    private $source;

    public function testGetsFieldShouldReturnFieldIfItExists(): void
    {
        $entity = new Entity();
        $entity->getFields()->set('id', $field = new Field());

        Assert::same($this->getField($entity, 123), $field);
    }

    public function testGetsFieldShouldThrowAnExceptionIdFieldNotExists(): void
    {
        $entity = new Entity();
        $entity->setRole('post');

        Expect::exception(RelationException::class)->withMessageContaining('Field `post`.`id` does not exists, referenced by `test`');

        $this->getField($entity, 123);
    }

    public function testGetsFieldsShouldReturnFieldsMapForSingleField(): void
    {
        $entity = new Entity();
        $entity->getFields()->set('id', $field = (new Field())->setColumn('id'));

        $fields = $this->getFields($entity, 123);

        Assert::count($fields, 1);
        Assert::same($fields->get('id'), $field);
    }

    public function testGetsFieldsShouldReturnFieldsMapForMultipleField(): void
    {
        $entity = new Entity();
        $entity->getFields()
            ->set('id', $fieldId = (new Field())->setColumn('id'))
            ->set('slug', $fieldSlug = (new Field())->setColumn('slug'));

        $fields = $this->getFields($entity, 234);

        Assert::count($fields, 2);
        Assert::same($fields->get('id'), $fieldId);
        Assert::same($fields->get('slug'), $fieldSlug);
    }

    public function testGetsFieldsShouldThrowAnExcpetionIfFieldNotExists(): void
    {
        $entity = new Entity();
        $entity->setRole('post');
        $entity->getFields()
            ->set('id', $fieldId = (new Field())->setColumn('id'));

        Expect::exception(RelationException::class)->withMessageContaining('Field `post`.`slug` does not exists, referenced by `test`');

        $this->getFields($entity, 234);
    }

    public function testEnsureFieldIfFieldExistsItShouldNotBeCreated(): void
    {
        $target = new Entity();

        $outer = (new Field())->setColumn('id');
        Assert::false($outer->isReferenced());

        $target->getFields()->set('id', $fieldId = (new Field())->setColumn('id'));

        $this->ensureField($target, 'id', $outer);

        Assert::true($outer->isReferenced());
        Assert::count($target->getFields(), 1);
    }

    #[DataProvider('nullableOrders')]
    public function testEnsureFieldMergesNullableOfRelationGeneratedField(bool $first, bool $second): void
    {
        $target = new Entity();
        $outer = (new Field())->setColumn('id')->setType('primary');

        $this->ensureField($target, 'user_id', $outer, $first);
        $this->ensureField($target, 'user_id', $outer, $second);

        $field = $target->getFields()->get('user_id');
        Assert::true($field->isRelationGenerated());
        Assert::true($field->getOptions()->get(Column::OPT_NULLABLE));
    }

    public function nullableOrders(): iterable
    {
        yield 'not nullable first' => [false, true];
        yield 'nullable first' => [true, false];
    }

    public function testEnsureFieldDoesNotChangeUserDefinedField(): void
    {
        $target = new Entity();
        $outer = (new Field())->setColumn('id')->setType('primary');
        $target->getFields()->set('user_id', $userField = (new Field())->setColumn('user_id')->setType('int'));

        $this->ensureField($target, 'user_id', $outer, true);

        Assert::false($userField->isRelationGenerated());
        Assert::false($userField->getOptions()->has(Column::OPT_NULLABLE));
    }

    #[DataProvider('outerFieldTypes')]
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

        Assert::false($outer->isReferenced());

        $target->getFields()->set('id', $fieldId = (new Field())->setColumn('id'));

        $this->ensureField($target, 'name', $outer, $nullable);

        Assert::true($outer->isReferenced());
        Assert::count($target->getFields(), 2);

        $resultField = $target->getFields()->get('name');

        Assert::same($resultField->getType(), $type);
        Assert::same($resultField->getTypecast(), 'typecast');
        Assert::same($resultField->getColumn(), $outer->getColumn());

        if ($nullable) {
            Assert::true($resultField->getOptions()->get(Column::OPT_NULLABLE));
        } else {
            Assert::false($resultField->getOptions()->has(Column::OPT_NULLABLE));
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

    #[BeforeTest]
    public function createOptions(): void
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
