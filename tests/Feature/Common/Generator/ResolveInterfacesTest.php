<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Generator;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\ResolveInterfaces;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\AuthorInterface;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class ResolveInterfacesTest extends BaseTest
{
    #[Test]
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

        Assert::array($schema)->hasKeys('post');
        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('author');

        Assert::array($schema)->hasKeys('author');
        Assert::array($schema['post'][Schema::COLUMNS])->hasKeys('author_p_id');
    }

    #[Test]
    public function testUnableResolveInterfaceDependency(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setTarget(AuthorInterface::class);
        $e->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');

        Expect::exception(RelationException::class);

        (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);
    }

    #[Test]
    public function testInvalidStaticLink(): void
    {
        $e = Post::define();
        $u = Author::define();

        $e->getRelations()->get('author')->setTarget('invalid');
        $e->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'post');

        Expect::exception(RelationException::class);

        (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);
    }

    #[Test]
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

        Expect::exception(RelationException::class);

        (new Compiler())->compile($r, [
            new ResolveInterfaces(),
            new GenerateRelations(['belongsTo' => new BelongsTo()]),
        ]);
    }

    #[Test]
    public function keepsAStaticLinkToARegisteredEntity(): void
    {
        $post = Post::define();
        $post->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);
        $r = new Registry($this->dbal);
        $r->register($post);
        $r->register(Author::define());

        (new ResolveInterfaces())->run($r);

        $author = $post->getRelations()->get('author');
        Assert::same($author->getTarget(), Author::class);
        Assert::true($author->getOptions()->has(ResolveInterfaces::STATIC_LINK));
    }

    #[Test]
    public function skipsEntitiesWithoutAClass(): void
    {
        $post = Post::define();
        $post->getRelations()->get('author')->setTarget(AuthorInterface::class);
        $post->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);
        $classless = new Entity();
        $classless->setRole('classless');
        $r = new Registry($this->dbal);
        $r->register($post);
        $r->register($classless);
        $r->register(Author::define());

        (new ResolveInterfaces())->run($r);

        Assert::same($post->getRelations()->get('author')->getTarget(), Author::class);
    }

    #[Test]
    public function rejectsAnEntityOfAMissingClass(): never
    {
        $post = Post::define();
        $post->getRelations()->get('author')->setTarget(AuthorInterface::class);
        $post->getRelations()->get('author')->getOptions()->set(ResolveInterfaces::STATIC_LINK, true);
        $missing = new Entity();
        $missing->setRole('missing');
        $missing->setClass('Cycle\Schema\Tests\Fixtures\MissingClass');
        $r = new Registry($this->dbal);
        $r->register($missing);
        $r->register($post);

        Expect::exception(RegistryException::class)->withMessageContaining('MissingClass');

        (new ResolveInterfaces())->run($r);
    }
}
