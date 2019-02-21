<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests;

use Cycle\ORM\Schema;
use Cycle\Schema\Field;
use Cycle\Schema\Map\FieldMap;
use PHPUnit\Framework\TestCase;

class FieldMapTest extends TestCase
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

    /**
     * @expectedException \Cycle\Schema\Exception\BuilderException
     */
    public function testPackException()
    {
        $m = new FieldMap();

        $m->set('id', $f = new Field());
        $m->packSchema();
    }

    public function testPackSchema()
    {
        $m = new FieldMap();

        $m->set('id', new Field());
        $m->get('id')->setColumn('id');

        $this->assertSame([
            Schema::COLUMNS      => ['id' => 'id'],
            Schema::TYPECAST     => [],
            Schema::FIND_BY_KEYS => []
        ], $m->packSchema());
    }

    public function testPackSchemaTypecast()
    {
        $m = new FieldMap();

        $m->set('id', new Field());
        $m->get('id')->setColumn('id');
        $m->get('id')->setTypecast('int');

        $this->assertSame([
            Schema::COLUMNS      => ['id' => 'id'],
            Schema::TYPECAST     => ['id' => 'int'],
            Schema::FIND_BY_KEYS => []
        ], $m->packSchema());
    }

    public function testPackSchemaReference()
    {
        $m = new FieldMap();

        $m->set('id', new Field());
        $m->get('id')->setColumn('id');
        $m->get('id')->setTypecast('int');
        $m->get('id')->setReferenced(true);

        $m->set('name', new Field());
        $m->get('name')->setColumn('full_name');

        $this->assertSame([
            Schema::COLUMNS      => ['id' => 'id', 'name' => 'full_name'],
            Schema::TYPECAST     => ['id' => 'int'],
            Schema::FIND_BY_KEYS => ['id']
        ], $m->packSchema());
    }
}