<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Relation;

use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;
use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Relation as RelationDefinition;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\BelongsTo;
use Cycle\Schema\Relation\ManyToMany;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Post;
use Cycle\Schema\Tests\Fixtures\Tag;
use Cycle\Schema\Tests\Fixtures\TagContext;

abstract class ManyToManyRelationTest extends BaseTest
{
    public function testGenerate(): void
    {
        $post = Post::define();
        $tag = Tag::define();
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

        $this->assertInstanceOf(ManyToMany::class, $r->getRelation($post, 'tags'));
    }

    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage('Entity `post` must have defined primary key');

        $post = Post::defineWithoutPK();
        $tag = Tag::define();
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
    }

    public function testThrowAnExceptionWhenPkNotDefinedInTarget(): void
    {
        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage('Entity `tag` must have defined primary key');

        $post = Post::define();
        $tag = Tag::defineWithoutPK();
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
    }

    public function testDifferentDatabases(): void
    {
        $post = Post::define();
        $tag = Tag::define();
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

        $this->expectException(SchemaException::class);

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);
    }

    public function testDifferentDatabases2(): void
    {
        $post = Post::define();
        $tag = Tag::define();
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

        $this->expectException(SchemaException::class);

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);
    }

    public function testPackSchema(): void
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
            ->set('collection', ArrayCollectionFactory::class);

        $r = new Registry($this->dbal);
        $r->register($post)->linkTable($post, 'default', 'post');
        $r->register($tag)->linkTable($tag, 'default', 'tag');
        $r->register($tagContext)->linkTable($tagContext, 'default', 'tag_context');

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);
        $schema = (new Compiler())->compile($r);

        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('tag', $schema);
        $this->assertArrayHasKey('tagContext', $schema);

        $this->assertArrayHasKey('tags', $schema['post'][Schema::RELATIONS]);

        $this->assertSame('tag', $schema['post'][Schema::RELATIONS]['tags'][Relation::TARGET]);
        $this->assertSame(
            Relation::MANY_TO_MANY,
            $schema['post'][Schema::RELATIONS]['tags'][Relation::TYPE]
        );

        $this->assertSame(
            ArrayCollectionFactory::class,
            $schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::COLLECTION_TYPE]
        );

        $this->assertSame(
            ['p_id'],
            $schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::INNER_KEY]
        );

        $this->assertSame(
            ['p_id'],
            $schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::OUTER_KEY]
        );

        $this->assertSame(
            'post_p_id',
            $schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_INNER_KEY]
        );

        $this->assertSame(
            'tag_p_id',
            $schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_OUTER_KEY]
        );

        $this->assertSame(
            'tagContext',
            $schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_ENTITY]
        );
    }

    public function testRenderTables(): void
    {
        $post = Post::define();
        $tag = Tag::define();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()->set('through', 'tagContext');

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

        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('post_p_id'));
        $this->assertTrue($table->hasColumn('tag_p_id'));
        $this->assertTrue($table->hasIndex(['post_p_id', 'tag_p_id']));
        $this->assertTrue($table->hasForeignKey(['post_p_id']));
        $this->assertTrue($table->hasForeignKey(['tag_p_id']));
    }

    /**
     * Unique indexes shouldn't be duplicated. Second unique index should be converted into not unique index
     */
    public function testDuplicatedUniqueIndexes(): void
    {
        $tag = Tag::define();
        $post = Post::define();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()
            ->set('through', 'tagContext')
            ->set('indexCreate', true);

        $tag->getRelations()->set('posts', new RelationDefinition());
        $tag->getRelations()->get('posts')
            ->setType('manyToMany')
            ->setTarget('post')
            ->getOptions()
            ->set('through', 'tagContext')
            ->set('indexCreate', true);

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
        assert($table instanceof AbstractTable);

        $uniques = array_filter($table->getIndexes(), static fn (AbstractIndex $index): bool => $index->isUnique());

        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('post_p_id'));
        $this->assertTrue($table->hasColumn('tag_p_id'));
        $this->assertTrue($table->hasIndex(['post_p_id', 'tag_p_id']));
        $this->assertTrue($table->hasIndex(['tag_p_id', 'post_p_id']));
        $this->assertTrue($table->hasForeignKey(['post_p_id']));
        $this->assertTrue($table->hasForeignKey(['tag_p_id']));
        // Unique indexes shouldn't be duplicated
        $this->assertCount(1, $uniques);
        $this->assertCount(4, $table->getIndexes());
    }

    public function testInverseInvalidType(): void
    {
        $post = Post::define();
        $tag = Tag::define();
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

        $this->expectException(SchemaException::class);

        (new GenerateRelations([
            'manyToMany' => new ManyToMany(),
            'belongsTo' => new BelongsTo(),
        ]))->run($r);
    }

    public function testInverse(): void
    {
        $post = Post::define();
        $tag = Tag::define();
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

        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('tag', $schema);
        $this->assertArrayHasKey('tagContext', $schema);

        $this->assertArrayHasKey('posts', $schema['tag'][Schema::RELATIONS]);

        $this->assertSame('post', $schema['tag'][Schema::RELATIONS]['posts'][Relation::TARGET]);
        $this->assertSame(
            Relation::MANY_TO_MANY,
            $schema['tag'][Schema::RELATIONS]['posts'][Relation::TYPE]
        );

        $this->assertSame(
            ['p_id'],
            $schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::INNER_KEY]
        );

        $this->assertSame(
            ['p_id'],
            $schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::OUTER_KEY]
        );

        $this->assertSame(
            'tag_p_id',
            $schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_INNER_KEY]
        );

        $this->assertSame(
            'post_p_id',
            $schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_OUTER_KEY]
        );

        $this->assertSame(
            'tagContext',
            $schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_ENTITY]
        );
    }

    public function testRenderWithIndex(): void
    {
        $post = Post::define();
        $tag = Tag::define();
        $tagContext = TagContext::define();

        $post->getRelations()->remove('author');

        $post->getRelations()->set('tags', new RelationDefinition());
        $post->getRelations()->get('tags')
            ->setType('manyToMany')
            ->setTarget('tag')
            ->getOptions()->set('through', 'tagContext');

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
        $this->assertTrue($table->hasIndex(['tag_p_id']));
        $this->assertTrue($table->hasIndex(['post_p_id']));
    }
}
