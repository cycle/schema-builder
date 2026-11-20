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
    #[\Testo\Test]
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

        \Testo\Assert::instanceOf($r->getRelation($post, 'tags'), ManyToMany::class);
    }

    #[\Testo\Test]
    public function testThrowAnExceptionWhenPkNotDefinedInSource(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Entity `post` must have defined primary key');

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

    #[\Testo\Test]
    public function testThrowAnExceptionWhenPkNotDefinedInTarget(): void
    {
        \Testo\Expect::exception(RegistryException::class)->withMessageContaining('Entity `tag` must have defined primary key');

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

    #[\Testo\Test]
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

        \Testo\Expect::exception(SchemaException::class);

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);
    }

    #[\Testo\Test]
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

        \Testo\Expect::exception(SchemaException::class);

        (new GenerateRelations(['manyToMany' => new ManyToMany()]))->run($r);
    }

    #[\Testo\Test]
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

        \Testo\Assert::array($schema)->hasKeys('post')->hasKeys('tag')->hasKeys('tagContext');

        \Testo\Assert::array($schema['post'][Schema::RELATIONS])->hasKeys('tags');

        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::TARGET], 'tag');
        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::TYPE], Relation::MANY_TO_MANY);

        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::COLLECTION_TYPE], ArrayCollectionFactory::class);

        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);

        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id']);

        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_INNER_KEY], 'post_p_id');

        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_OUTER_KEY], 'tag_p_id');

        \Testo\Assert::same($schema['post'][Schema::RELATIONS]['tags'][Relation::SCHEMA][Relation::THROUGH_ENTITY], 'tagContext');
    }

    #[\Testo\Test]
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

        \Testo\Assert::true($table->hasColumn('id'));
        \Testo\Assert::true($table->hasColumn('post_p_id'));
        \Testo\Assert::true($table->hasColumn('tag_p_id'));
        \Testo\Assert::true($table->hasIndex(['post_p_id', 'tag_p_id']));
        \Testo\Assert::true($table->hasForeignKey(['post_p_id']));
        \Testo\Assert::true($table->hasForeignKey(['tag_p_id']));
    }

    /**
     * Unique indexes shouldn't be duplicated. Second unique index should be converted into not unique index
     */
    #[\Testo\Test]
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

        $uniques = array_filter($table->getIndexes(), static fn(AbstractIndex $index): bool => $index->isUnique());

        \Testo\Assert::true($table->hasColumn('id'));
        \Testo\Assert::true($table->hasColumn('post_p_id'));
        \Testo\Assert::true($table->hasColumn('tag_p_id'));
        \Testo\Assert::true($table->hasIndex(['post_p_id', 'tag_p_id']));
        \Testo\Assert::true($table->hasIndex(['tag_p_id', 'post_p_id']));
        \Testo\Assert::true($table->hasForeignKey(['post_p_id']));
        \Testo\Assert::true($table->hasForeignKey(['tag_p_id']));
        // Unique indexes shouldn't be duplicated
        \Testo\Assert::count($uniques, 1);
        \Testo\Assert::count($table->getIndexes(), 4);
    }

    #[\Testo\Test]
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

        \Testo\Expect::exception(SchemaException::class);

        (new GenerateRelations([
            'manyToMany' => new ManyToMany(),
            'belongsTo' => new BelongsTo(),
        ]))->run($r);
    }

    #[\Testo\Test]
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

        \Testo\Assert::array($schema)->hasKeys('post')->hasKeys('tag')->hasKeys('tagContext');

        \Testo\Assert::array($schema['tag'][Schema::RELATIONS])->hasKeys('posts');

        \Testo\Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::TARGET], 'post');
        \Testo\Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::TYPE], Relation::MANY_TO_MANY);

        \Testo\Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::INNER_KEY], ['p_id']);

        \Testo\Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::OUTER_KEY], ['p_id']);

        \Testo\Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_INNER_KEY], 'tag_p_id');

        \Testo\Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_OUTER_KEY], 'post_p_id');

        \Testo\Assert::same($schema['tag'][Schema::RELATIONS]['posts'][Relation::SCHEMA][Relation::THROUGH_ENTITY], 'tagContext');
    }

    #[\Testo\Test]
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
        \Testo\Assert::true($table->hasIndex(['tag_p_id']));
        \Testo\Assert::true($table->hasIndex(['post_p_id']));
    }
}
