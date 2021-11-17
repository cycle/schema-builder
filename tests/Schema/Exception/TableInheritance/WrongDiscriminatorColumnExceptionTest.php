<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\TableInheritance\WrongDiscriminatorColumnException;
use PHPUnit\Framework\TestCase;

class WrongDiscriminatorColumnExceptionTest extends TestCase
{
    public function testGetsSolution()
    {
        $author = new Entity();
        $author->setRole('author');
        $author->getFields()
            ->set('id', (new Field())->setType('primary')->setColumn('id'))
            ->set('name', (new Field())->setType('string')->setColumn('name'));

        $e = new WrongDiscriminatorColumnException($author, 'test');

        $this->assertSame(
            'Discriminator column is not found among the entity fields.',
            $e->getName()
        );
        $this->assertSame(
            'You have to specify one of the defined fields of the `author` role: `id`, `name`',
            $e->getSolution()
        );
    }
}
