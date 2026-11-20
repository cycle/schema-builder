<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Generator;

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
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

final class RenderModifiersTest
{
    #[Test]
    public function testEntityShouldBeRendered(): void
    {
        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
        );

        $user = new Entity();
        $user->setRole('user')->setClass(User::class);
        $user->getFields()->set('foo_bar', (new Field())->setType('primary')->setColumn('id'));

        $mock = \Mockery::mock(SchemaModifierInterface::class)->shouldIgnoreMissing();
        $mock->shouldReceive('withRole')->once()->with('user', \Mockery::andAnyOtherArgs())->andReturnSelf();

        $user->addSchemaModifier($mock);

        $mock->shouldReceive('render')->atLeast()->once()->with($r, \Mockery::andAnyOtherArgs());

        $r->register($user);

        $c = new Compiler();
        $c->compile($r, [new RenderModifiers()]);
    }

    #[Test]
    #[DataProvider('brokenMethodsDataProvider')]
    public function testErrorInsideModifierShouldThrowAnException(string $method): void
    {
        Expect::exception(SchemaException::class)->withMessageContaining('Unable to render modifier `Cycle\Schema\Tests\Fixtures\BrokenSchemaModifier` for the `user` role.');

        $r = new Registry(
            \Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing(),
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
            // 'withRole' => ['withRole'],
            'render' => ['render'],
        ];
    }
}
