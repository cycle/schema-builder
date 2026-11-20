<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\TableInheritance\DiscriminatorColumnNotPresentException;
use Testo\Assert;
use Testo\Test;

#[Test]
final class DiscriminatorColumnNotPresentExceptionTest
{
    public function testGetsSolution(): void
    {
        $author = new Entity();
        $author->setRole('author');
        $author->getFields()
            ->set('id', (new Field())->setType('primary')->setColumn('id'))
            ->set('name', (new Field())->setType('string')->setColumn('name'));

        $e = new DiscriminatorColumnNotPresentException($author, 'test');

        Assert::same($e->getName(), 'Discriminator column is not present.');
        Assert::same($e->getSolution(), "Discriminator column is required for Single Table Inheritance schema.\n" .
        'You have to specify one of the defined fields of the `author` role: `id`, `name`');
    }
}
