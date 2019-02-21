<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Map\FieldMap;
use PHPUnit\Framework\TestCase;

class FieldsTest extends TestCase
{
    /**
     * @expectedException \Cycle\Schema\Exception\FieldException
     */
    public function testNoField()
    {
        $m = new FieldMap();
        $m->get('id');
    }

    public function testSetGet()
    {
        $m = new FieldMap();

        $this->assertFalse($m->has('id'));
        $m->set('id', $f = new Field());
        $this->assertTrue($m->has('id'));
        $this->assertSame($f, $m->get('id'));

        $this->assertSame(['id' => $f], $m->getIterator());
    }

    /**
     * @expectedException \Cycle\Schema\Exception\FieldException
     */
    public function testSetTwice()
    {
        $m = new FieldMap();

        $m->set('id', $f = new Field());
        $m->set('id', $f = new Field());
    }
}