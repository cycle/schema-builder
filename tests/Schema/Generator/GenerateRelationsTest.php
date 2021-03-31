<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Relation as RelationDefinition;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\Tag;
use Cycle\Schema\Tests\Fixtures\TagContext;
use Cycle\Schema\Tests\Fixtures\User;

abstract class GenerateRelationsTest extends BaseTest
{
    public function relationOptionsDataProvider(): array
    {
        return [
            'default orderBy' => ['orderBy', [], Relation::ORDER_BY],
            'custom orderBy' => ['orderBy', ['id' => 'DESC'], Relation::ORDER_BY],
            'default where' => ['where', [], Relation::WHERE],
            'custom where' => ['where', ['id' => '1'], Relation::WHERE],
        ];
    }

    /**
     * @dataProvider relationOptionsDataProvider
     */
    public function testHasManyToManyRelationOptions(string $optionKey, $optionValue, int $relationKey): void
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
                ->set('though', 'tagContext')
                ->set($optionKey, $optionValue);

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'default', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'default', 'tag_context');

        $c = new Compiler();
        $schema = $c->compile($r, [new RenderTables(), new GenerateRelations()]);

        $this->assertSame($optionValue, $schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][$relationKey]);
    }

    /**
     * @dataProvider relationOptionsDataProvider
     */
    public function testHasManyRelationOptions(string $optionKey, $optionValue, int $relationKey): void
    {
        $e = Plain::define();
        $u = User::define();

        $relation = $u->getRelations()->get('plain');
        $relation->setType('hasMany');
        $relation->getOptions()->set($optionKey, $optionValue);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $c = new Compiler();
        $schema = $c->compile($r, [new RenderTables(), new GenerateRelations()]);

        $this->assertSame($optionValue, $schema['user'][Schema::RELATIONS]['plain'][Relation::SCHEMA][$relationKey]);
    }
}
