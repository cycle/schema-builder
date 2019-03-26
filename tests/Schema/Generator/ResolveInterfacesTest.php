<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Generator;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\ResolveInterfaces;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\AuthorInterface;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\User;

abstract class ResolveInterfacesTest extends BaseTest
{
    public function testResolveInterfaceDependency()
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setTarget(AuthorInterface::class);
        $e->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');

        $schema = (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);

        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('author', $schema['post'][Schema::RELATIONS]);

        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('author_id', $schema['post'][Schema::COLUMNS]);
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RelationException
     */
    public function testUnableResolveInterfaceDependency()
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setTarget(AuthorInterface::class);
        $e->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RelationException
     */
    public function testInvalidStaticLink()
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setTarget('invalid');
        $e->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');

        $schema = (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);
    }

    /**
     * @expectedException \Cycle\Schema\Exception\RelationException
     */
    public function testAmbiguousDependency()
    {
        $e = Post::define();
        $u = Author::define();
        $u1 = User::define();

        $e->getRelations()->get('author')->setTarget(AuthorInterface::class);
        $e->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');
        $r->register($u)->linkTable($u, 'default', 'author');
        $r->register($u1)->linkTable($u1, 'default', 'user');

        $schema = (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()])
        ]);
    }
}