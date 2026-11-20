<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Generator;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\EntityException;
use Cycle\Schema\Generator\ValidateEntities;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class ValidateEntitiesTest
{
    public function acceptsEntitiesWithFields(): void
    {
        $r = $this->registry();
        $r->register(User::define());

        Assert::same((new ValidateEntities())->run($r), $r);
    }

    public function rejectsAnEntityWithoutFields(): never
    {
        $empty = new Entity();
        $empty->setRole('empty');
        $r = $this->registry();
        $r->register(User::define());
        $r->register($empty);

        Expect::exception(EntityException::class)->withMessage('Entity `empty` is empty');

        (new ValidateEntities())->run($r);
    }

    private function registry(): Registry
    {
        return new Registry(\Mockery::mock(DatabaseProviderInterface::class)->shouldIgnoreMissing());
    }
}
