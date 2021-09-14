<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Relation as RelationDefinition;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
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
    public function testHasManyToManyRelationOptions(string $optionKey, array $optionValue, int $relationKey): void
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

        $this->assertSame($optionValue, $schema['post'][SchemaInterface::RELATIONS]['tags'][Relation::SCHEMA][$relationKey]);
    }

    /**
     * @dataProvider relationOptionsDataProvider
     */
    public function testHasManyRelationOptions(string $optionKey, array $optionValue, int $relationKey): void
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

        $this->assertSame($optionValue, $schema['user'][SchemaInterface::RELATIONS]['plain'][Relation::SCHEMA][$relationKey]);
    }

    public function fkActionDataProvider(): array
    {
        return [
            'Default' => [false, false, 'CASCADE', 'CASCADE'],
            'Only fkAction' => ['SET NULL', false, 'SET NULL', 'SET NULL'],
            'Only onDelete' => [false, 'SET NULL', 'CASCADE', 'SET NULL'],
            'Both' => ['NO ACTION', 'SET NULL', 'NO ACTION', 'SET NULL'],
        ];
    }

    /**
     * @dataProvider fkActionDataProvider
     */
    public function testDefaultFkActionAndDifferentFkOnDelete(
        null|false|string $fkActionOption,
        null|false|string $onDeleteOption,
        string $onUpdateExpected,
        string $onDeleteExpected
    ): void {
        $plain = Plain::define();
        $user = User::define();

        $options = $user->getRelations()->get('plain')->getOptions();
        $options->set('nullable', true);

        if ($fkActionOption !== false) {
            $options->set('fkAction', $fkActionOption);
        }
        if ($onDeleteOption !== false) {
            $options->set('fkOnDelete', $onDeleteOption);
        }

        $r = new Registry($this->dbal);
        $r->register($plain)->linkTable($plain, 'default', 'plain');
        $r->register($user)->linkTable($user, 'default', 'user');

        (new GenerateRelations())->run($r);
        (new RenderTables())->run($r);
        (new RenderRelations())->run($r);

        $table = $r->getTableSchema($plain);

        $fks = $table->getForeignKeys();
        $this->assertCount(1, $fks);
        $fk = reset($fks);
        $this->assertSame($onUpdateExpected, $fk->getUpdateRule());
        $this->assertSame($onDeleteExpected, $fk->getDeleteRule());
    }
}
