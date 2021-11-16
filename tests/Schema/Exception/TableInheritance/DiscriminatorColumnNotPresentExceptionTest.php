<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Exception\TableInheritance;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\TableInheritance\DiscriminatorColumnNotPresentException;
use PHPUnit\Framework\TestCase;

class DiscriminatorColumnNotPresentExceptionTest extends TestCase
{
    public function testGetsSolution()
    {
        $author = new Entity();
        $author->setRole('author');
        $author->getFields()
            ->set('id', (new Field())->setType('primary')->setColumn('id'))
            ->set('name', (new Field())->setType('string')->setColumn('name'));

        $e = new DiscriminatorColumnNotPresentException($author, 'test');

        $this->assertSame(
            'You have to specify one of the defined fields of the `author` role: `id`, `name`',
            $e->getSolution()
        );
    }
}
