<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

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
use PHPUnit\Framework\TestCase;

class GenerateModifiersTest extends TestCase
{
    public function testEntityShouldBeModified()
    {
        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->getFields()->set('foo_bar', (new Field())->setType('primary')->setColumn('id'));

        $user->addSchemaModifier(
            new class () implements SchemaModifierInterface {
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
            }
        );

        $r->register($user);

        $c = new Compiler();
        $schema = $c->compile($r, [new GenerateModifiers()]);

        $this->assertSame(Author::class, $schema['user'][SchemaInterface::PARENT]);
        $this->assertSame(
            ['foo_bar' => 'id', 'type' => 'type'],
            $schema['user'][SchemaInterface::COLUMNS]
        );
    }

    /**
     * @dataProvider brokenMethodsDataProvider
     */
    public function testErrorInsideModifierShouldThrowAnException(string $method)
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage(
            'Unable to compute modifier `Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier` for the `user` role.'
        );

        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
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
