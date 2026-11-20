<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Feature\Common\Relation;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Relation as RelationDefinition;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Feature\BaseTest;
use Cycle\Schema\Tests\Fixtures\Author;
use Cycle\Schema\Tests\Fixtures\Post;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

/**
 * The relation is declared on the author and points to the post; every key is given as a column name.
 */
final class RelationKeysTest extends BaseTest
{
    public static function relations(): iterable
    {
        yield 'belongsTo' => ['belongsTo', 'outerKey', Relation::OUTER_KEY, 'author'];
        yield 'refersTo' => ['refersTo', 'outerKey', Relation::OUTER_KEY, 'author'];
        yield 'hasOne' => ['hasOne', 'innerKey', Relation::INNER_KEY, 'post'];
        yield 'hasMany' => ['hasMany', 'innerKey', Relation::INNER_KEY, 'post'];
    }

    #[Test]
    #[DataProvider('relations')]
    public function resolvesKeyColumnsToFieldNames(string $type, string $option, int $key, string $indexed): void
    {
        $registry = $this->registry($type, [$option => 'id']);

        $schema = (new Compiler())->compile($registry, [new GenerateRelations()]);

        Assert::same($schema['author'][Schema::RELATIONS]['post'][Relation::SCHEMA][$key], ['p_id']);
    }

    #[Test]
    #[DataProvider('relations')]
    public function indexesTheReferencingColumnWithoutAForeignKey(
        string $type,
        string $option,
        int $key,
        string $indexed,
    ): void {
        $registry = $this->registry($type, ['fkCreate' => false]);

        (new Compiler())->compile($registry, [new GenerateRelations(), new RenderTables(), new RenderRelations()]);

        $table = $registry->getTableSchema($registry->getEntity($indexed));
        Assert::count($table->getForeignKeys(), 0);
        Assert::count($table->getIndexes(), 1);
    }

    #[Test]
    #[DataProvider('relations')]
    public function createsNoIndexWhenDisabled(string $type, string $option, int $key, string $indexed): void
    {
        $registry = $this->registry($type, ['fkCreate' => false, 'indexCreate' => false]);

        (new Compiler())->compile($registry, [new GenerateRelations(), new RenderTables(), new RenderRelations()]);

        Assert::count($registry->getTableSchema($registry->getEntity($indexed))->getIndexes(), 0);
    }

    private function registry(string $type, array $options): Registry
    {
        $author = Author::define();
        $post = Post::define();
        $post->getRelations()->remove('author');
        $relation = (new RelationDefinition())->setTarget('post')->setType($type);
        foreach ($options as $name => $value) {
            $relation->getOptions()->set($name, $value);
        }
        $author->getRelations()->set('post', $relation);

        $registry = new Registry($this->dbal);
        $registry->register($author)->linkTable($author, 'default', 'author');
        $registry->register($post)->linkTable($post, 'default', 'post');

        return $registry;
    }
}
