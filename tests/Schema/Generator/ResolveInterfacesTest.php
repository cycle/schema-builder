<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Exception\RelationException;
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
    public function testResolveInterfaceDependency(): void
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
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);

        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('author', $schema['post'][Schema::RELATIONS]);

        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('author_id', $schema['post'][Schema::COLUMNS]);
    }

    public function testUnableResolveInterfaceDependency(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setTarget(AuthorInterface::class);
        $e->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');

        $this->expectException(RelationException::class);

        (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);
    }

    public function testInvalidStaticLink(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setTarget('invalid');
        $e->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');

        $this->expectException(RelationException::class);

        (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);
    }

    public function testAmbiguousDependency(): void
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

        $this->expectException(RelationException::class);

        (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);
    }
}
