<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Relation as RelationDefinition;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Relation\ManyToMany;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\Tag;
use Cycle\Schema\Tests\Fixtures\TagContext;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class ManyToManyRelationCompositePKTest extends BaseTest
{
    #[Test]
    public function testGenerate(): void
    {
        $post = Post::defineCompositePK();
        $tag = Tag::defineCompositePK();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()->set('though', 'tagContext');

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'default', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'default', 'tag_context');

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);

        Assert::instanceOf($r->getRelation($post, 'tags'), ManyToMany::class);
    }

    #[Test]
    public function testDifferentDatabases(): void
    {
        $post = Post::defineCompositePK();
        $tag = Tag::defineCompositePK();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()->set('though', 'tagContext');

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'secondary', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'default', 'tag_context');

        Expect::exception(SchemaException::class);

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);
    }

    #[Test]
    public function testDifferentDatabases2(): void
    {
        $post = Post::defineCompositePK();
        $tag = Tag::defineCompositePK();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()->set('though', 'tagContext');

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'default', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'secondary', 'tag_context');

        Expect::exception(SchemaException::class);

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);
    }

    #[Test]
    public function testPackSchema(): void
    {
        $post = Post::defineCompositePK();
        $tag = Tag::defineCompositePK();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()->set('though', 'tagContext');

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'default', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'default', 'tag_context');

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);
        $schema = (new Compiler())->compile($r);

        Assert::array($schema)->hasKeys('post')->hasKeys('tag')->hasKeys('tagContext');

        Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('tags');

        Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::TARGET], 'tag');
        Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::TYPE], Relation::MANY_TO_MANY);

        Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id', 'p_slug']);

        Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id', 'p_slug']);

        Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_INNER_KEY], ['post_p_id', 'post_p_slug']);

        Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_OUTER_KEY], ['tag_p_id', 'tag_p_slug']);

        Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_ENTITY], 'tagContext');
    }

    #[Test]
    public function testRenderTables(): void
    {
        $post = Post::defineCompositePK();
        $tag = Tag::defineCompositePK();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()->set('though', 'tagContext');

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

        Assert::true($table->hasColumn('post_p_id'));
        Assert::true($table->hasColumn('tag_p_id'));
        Assert::true($table->hasIndex(['post_p_id', 'post_p_slug', 'tag_p_id', 'tag_p_slug']));
        Assert::true($table->hasForeignKey(['post_p_id', 'post_p_slug']));
        Assert::true($table->hasForeignKey(['tag_p_id', 'tag_p_slug']));
    }

    #[Test]
    public function testInverseInvalidType(): void
    {
        $post = Post::defineCompositePK();
        $tag = Tag::defineCompositePK();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->setInverse('posts', 'belongsTo')
            ->getOptions()->set('though', 'tagContext');

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'default', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'default', 'tag_context');

        Expect::exception(SchemaException::class);

        (new GenerateRelations([
            'manyToMany' => new ManyToMany(),
            'belongsTo' => new BelongsTo(),
        ]))->run($r);
    }

    #[Test]
    public function testInverse(): void
    {
        $post = Post::defineCompositePK();
        $tag = Tag::defineCompositePK();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->setInverse('posts', 'manyToMany')
            ->getOptions()->set('though', 'tagContext');

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'default', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'default', 'tag_context');

        (new GenerateRelations([
            'manyToMany' => new ManyToMany(),
        ]))->run($r);

        $schema = (new Compiler())->compile($r);

        Assert::array($schema)->hasKeys('post')->hasKeys('tag')->hasKeys('tagContext');

        Assert::array($schema['tag'][Schema::RELATIONS])->hasKeys('posts');

        Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::TARGET], 'post');
        Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::TYPE], Relation::MANY_TO_MANY);

        Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id', 'p_slug']);

        Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id', 'p_slug']);

        Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_INNER_KEY], ['tag_p_id', 'tag_p_slug']);

        Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_OUTER_KEY], ['post_p_id', 'post_p_slug']);

        Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_ENTITY], 'tagContext');
    }
}
