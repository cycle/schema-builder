<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\TableInheritance\WrongDiscriminatorColumnException;
use Testo\Assert;
use Testo\Test;

#[Test]
final class WrongDiscriminatorColumnExceptionTest
{
    public function testGetsSolution(): void
    {
        $author = new Entity();
        $author->setRole('author');
        $author->getFields()
            ->set('id', (new Field())->setType('primary')->setColumn('id'))
            ->set('name', (new Field())->setType('string')->setColumn('name'));

        $e = new WrongDiscriminatorColumnException($author, 'test');

        Assert::same($e->getName(), 'Discriminator column is not found among the entity fields.');
        Assert::same($e->getSolution(), 'You have to specify one of the defined fields of the `author` role: `id`, `name`');
    }
}
