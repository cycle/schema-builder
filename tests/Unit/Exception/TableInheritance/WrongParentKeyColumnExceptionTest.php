<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\TableInheritance\WrongParentKeyColumnException;
use Testo\Assert;
use Testo\Test;

#[Test]
final class WrongParentKeyColumnExceptionTest
{
    public function testGetsSolution(): void
    {
        $author = new Entity();
        $author->setRole('author');
        $author->getFields()
            ->set('id', (new Field())->setType('primary')->setColumn('id'))
            ->set('name', (new Field())->setType('string')->setColumn('name'));

        $e = new WrongParentKeyColumnException($author, 'test');

        Assert::same($e->getName(), 'Outer key column is not found among parent entity fields.');
        Assert::same($e->getSolution(), 'You have to specify one of the defined fields of the `author` role: `id`, `name`');
    }
}
