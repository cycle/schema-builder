<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\RenderModifiers;
use Cycle\Schema\Registry;
use Cycle\Schema\SchemaModifierInterface;
use Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier;
use Cycle\Schema\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

class RenderModifiersTest extends TestCase
{
    public function testEntityShouldBeRendered()
    {
        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->getFields()->set('foo_bar', (new Field())->setType('primary')->setColumn('id'));

        $user->addSchemaModifier($mock = $this->createMock(SchemaModifierInterface::class));
        $mock
            ->expects($this->atLeastOnce())
            ->method('withRole')
            ->with($this->equalTo('user'))
            ->willReturn($mock);

        $mock
            ->expects($this->atLeastOnce())
            ->method('render')
            ->with($this->equalTo($r));

        $r->register($user);

        $c = new Compiler();
        $c->compile($r, [new RenderModifiers()]);
    }

    /**
     * @dataProvider brokenMethodsDataProvider
     */
    public function testErrorInsideModifierShouldThrowAnException(string $method)
    {
        $this->expectException(SchemaException::class);
        $this->expectErrorMessage(
            'Unable to render modifier `Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier` for the `user` role.'
        );

        $r = new Registry(
            $this->createMock(DatabaseProviderInterface::class)
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->addSchemaModifier(new BrokenSchemaModifier(BrokenSchemaModifier::class . '::' . $method));

        $r->register($user);

        $c = new Compiler();
        $c->compile($r, [new RenderModifiers()]);
    }

    public function brokenMethodsDataProvider()
    {
        return [
            'withRole' => ['withRole'],
            'render' => ['render'],
        ];
    }
}
