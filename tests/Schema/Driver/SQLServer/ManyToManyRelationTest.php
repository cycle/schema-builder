<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Driver\SQLServer;

use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Relation as RelationDefinition;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\ManyToMany;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\Tag;
use Cycle\Schema\Tests\Fixtures\TagContext;
use Cycle\Schema\Tests\Relation\ManyToManyRelationTest as BaseTest;

class ManyToManyRelationTest extends BaseTest
{
    public const DRIVER = 'sqlserver';

    public function testRenderWithoutIndex(): void
    {
        $post = Post::define();
        $tag = Tag::define();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()
            ->set('through', 'tagContext')
            ->set('indexCreate', false);

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'default', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'default', 'tag_context');

        (new Compiler())->compile($r, [
            new GenerateRelations(['manyToMany' => new ManyToMany()]),
            $t = new RenderTables(),
            new RenderRelations(),
        ]);

        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('tag_context');
        $this->assertFalse($table->hasIndex(['tag_p_id']));
        $this->assertFalse($table->hasIndex(['post_p_id']));
    }
}
