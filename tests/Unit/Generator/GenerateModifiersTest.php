<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Generator;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\GenerateModifiers;
use Cycle\Schema\Registry;
use Cycle\Schema\SchemaModifierInterface;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

final class GenerateModifiersTest
{
    #[Test]
    public function testEntityShouldBeModified(): void
    {
        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->getFields()->set('foo_bar', (new Field())->setType('primary')->setColumn('id'));

        $user->addSchemaModifier(
            new class implements SchemaModifierInterface {
                private string $role;

                public function withRole(string $role): static
                {
                    $this->role = $role;

                    return $this;
                }

                public function compute(Registry $registry): void
                {
                    $registry->getEntity($this->role)->getFields()
                        ->set('type', (new Field())->setType('string')->setColumn('type'));
                }

                public function render(Registry $registry): void
                {
                    // TODO: Implement render() method.
                }

                public function modifySchema(array &$schema): void
                {
                    $schema[SchemaInterface::PARENT] = Author::class;
                }
            },
        );

        $r->register($user);

        $c = new Compiler();
        $schema = $c->compile($r, [new GenerateModifiers()]);

        Assert::same($schema['user'][SchemaInterface::PARENT], Author::class);
        Assert::same($schema['user'][SchemaInterface::COLUMNS], ['foo_bar' => 'id', 'type' => 'type']);
    }

    #[Test]
    #[DataProvider('brokenMethodsDataProvider')]
    public function testErrorInsideModifierShouldThrowAnException(string $method): void
    {
        Expect::exception(SchemaException::class)->withMessageContaining('Unable to compute modifier `Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier` for the `user` role.');

        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->addSchemaModifier(new BrokenSchemaModifier(BrokenSchemaModifier::class . '::' . $method));

        $r->register($user);

        $c = new Compiler();
        $c->compile($r, [new GenerateModifiers()]);
    }

    public function brokenMethodsDataProvider()
    {
        return [
            'compute' => ['compute'],
        ];
    }
}
