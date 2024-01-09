<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Generator\ShowChanges;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\FakeOutput;
use Cycle\Schema\Tests\Fixtures\User;

abstract class ShowChangesTest extends BaseTest
{
    private FakeOutput $output;
    private Entity $user;
    private Registry $registry;
    private ShowChanges $generator;
    private Compiler $compiler;

    public function setUp(): void
    {
        parent::setUp();

        $this->output = new FakeOutput();
        $this->user = User::define();

        $this->registry = new Registry($this->dbal);
        $this->registry->register($this->user);
        $this->registry->linkTable($this->user, 'default', 'users');

        $this->generator = new ShowChanges($this->output);

        $this->compiler = new Compiler();
    }

    public function testRunWithoutChanges(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);
        $this->registry->getTableSchema($this->user)->save();

        $this->generator->run($this->registry);

        $this->assertStringContainsString('No database changes has been detected', $this->output->getBuffer());
    }

    public function testRunCreateTable(): void
    {
        $this->generator->run($this->registry);

        $this->assertStringContainsString('Schema changes:', $this->output->getBuffer());
        $this->assertStringContainsString('default.users    - create table', $this->output->getBuffer());
    }

    public function testRunDropTable(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->save();
        $this->registry->getTableSchema($this->user)->declareDropped();

        $this->generator->run($this->registry);

        $this->assertStringContainsString('Schema changes:', $this->output->getBuffer());
        $this->assertStringContainsString('default.users    - drop table', $this->output->getBuffer());
    }

    public function testRunChangedColumns(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->save();
        $this->registry->getTableSchema($this->user)->column('new_column')->string();
        $this->registry->getTableSchema($this->user)->column('user_name')->integer();
        $this->registry->getTableSchema($this->user)->dropColumn('created_at');

        $this->generator->run($this->registry);

        $this->assertStringContainsString('Schema changes:', $this->output->getBuffer());
        $this->assertStringContainsString('default.users: 3 change(s) detected', $this->output->getBuffer());

        $this->assertStringNotContainsString('- add column [new_column]', $this->output->getBuffer());
        $this->assertStringNotContainsString('- drop column [created_at]', $this->output->getBuffer());
        $this->assertStringNotContainsString('- alter column [user_name]', $this->output->getBuffer());
    }

    public function testRunChangedColumnsVerbose(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->save();
        $this->registry->getTableSchema($this->user)->column('new_column')->string();
        $this->registry->getTableSchema($this->user)->column('user_name')->integer();
        $this->registry->getTableSchema($this->user)->dropColumn('created_at');

        $this->output->setVerbosity(FakeOutput::VERBOSITY_VERBOSE);
        $this->generator->run($this->registry);

        $this->assertStringContainsString('Schema changes:', $this->output->getBuffer());
        $this->assertStringContainsString('default.users', $this->output->getBuffer());
        $this->assertStringContainsString('- add column [new_column]', $this->output->getBuffer());
        $this->assertStringContainsString('- drop column [created_at]', $this->output->getBuffer());
        $this->assertStringContainsString('- alter column [user_name]', $this->output->getBuffer());

        $this->assertStringNotContainsString('default.users: 3 change(s) detected', $this->output->getBuffer());
    }

    public function testRunChangedIndexes(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->index(['user_name']);
        $this->registry->getTableSchema($this->user)->index(['balance']);
        $this->registry->getTableSchema($this->user)->save();

        $this->registry->getTableSchema($this->user)->renameIndex(['user_name'], 'changed');
        $this->registry->getTableSchema($this->user)->dropIndex(['balance']);
        $this->registry->getTableSchema($this->user)->index(['created_at']);

        $this->generator->run($this->registry);

        $this->assertStringContainsString('Schema changes:', $this->output->getBuffer());
        $this->assertStringContainsString('default.users: 3 change(s) detected', $this->output->getBuffer());

        $this->assertStringNotContainsString('- add index on [created_at]', $this->output->getBuffer());
        $this->assertStringNotContainsString('- drop index on [balance]', $this->output->getBuffer());
        $this->assertStringNotContainsString('- alter index on [user_name]', $this->output->getBuffer());
    }

    public function testRunChangedIndexesVerbose(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->index(['user_name']);
        $this->registry->getTableSchema($this->user)->index(['balance']);
        $this->registry->getTableSchema($this->user)->save();

        $this->registry->getTableSchema($this->user)->renameIndex(['user_name'], 'changed');
        $this->registry->getTableSchema($this->user)->dropIndex(['balance']);
        $this->registry->getTableSchema($this->user)->index(['created_at']);

        $this->output->setVerbosity(FakeOutput::VERBOSITY_VERBOSE);
        $this->generator->run($this->registry);

        $this->assertStringContainsString('Schema changes:', $this->output->getBuffer());
        $this->assertStringContainsString('default.users', $this->output->getBuffer());
        $this->assertStringContainsString('- add index on [created_at]', $this->output->getBuffer());
        $this->assertStringContainsString('- drop index on [balance]', $this->output->getBuffer());
        $this->assertStringContainsString('- alter index on [user_name]', $this->output->getBuffer());

        $this->assertStringNotContainsString('default.users: 3 change(s) detected', $this->output->getBuffer());
    }

    public function testRunChangedFk(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->column('friend_id')->integer();
        $this->registry->getTableSchema($this->user)->column('partner_id')->integer();
        $this->registry->getTableSchema($this->user)->column('some_id')->integer();
        $this->registry->getTableSchema($this->user)
            ->foreignKey(['friend_id'])
            ->references('users', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION);
        $this->registry->getTableSchema($this->user)->foreignKey(['partner_id'])->references('users', ['id']);
        $this->registry->getTableSchema($this->user)->save();

        $this->registry->getTableSchema($this->user)
            ->foreignKey(['friend_id'])
            ->references('users', ['id'])
            ->onDelete(AbstractForeignKey::CASCADE);
        $this->registry->getTableSchema($this->user)->dropForeignKey(['partner_id']);
        $this->registry->getTableSchema($this->user)->foreignKey(['some_id'], false)->references('users', ['id']);

        $this->generator->run($this->registry);

        $this->assertStringContainsString('Schema changes:', $this->output->getBuffer());
        $this->assertStringContainsString('default.users: 3 change(s) detected', $this->output->getBuffer());

        $this->assertStringNotContainsString('- add foreign key on [some_id]', $this->output->getBuffer());
        $this->assertStringNotContainsString('- drop foreign key on [partner_id]', $this->output->getBuffer());
        $this->assertStringNotContainsString('- alter foreign key on [friend_id]', $this->output->getBuffer());
    }

    public function testRunChangedFkVerbose(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->column('friend_id')->integer();
        $this->registry->getTableSchema($this->user)->column('partner_id')->integer();
        $this->registry->getTableSchema($this->user)->column('some_id')->integer();
        $this->registry->getTableSchema($this->user)
            ->foreignKey(['friend_id'])
            ->references('users', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION);
        $this->registry->getTableSchema($this->user)->foreignKey(['partner_id'])->references('users', ['id']);
        $this->registry->getTableSchema($this->user)->save();

        $this->registry->getTableSchema($this->user)
            ->foreignKey(['friend_id'])
            ->references('users', ['id'])
            ->onDelete(AbstractForeignKey::CASCADE);
        $this->registry->getTableSchema($this->user)->dropForeignKey(['partner_id']);
        $this->registry->getTableSchema($this->user)->foreignKey(['some_id'], false)->references('users', ['id']);

        $this->output->setVerbosity(FakeOutput::VERBOSITY_VERBOSE);
        $this->generator->run($this->registry);

        $this->assertStringContainsString('Schema changes:', $this->output->getBuffer());
        $this->assertStringContainsString('default.users', $this->output->getBuffer());
        $this->assertStringContainsString('- add foreign key on [some_id]', $this->output->getBuffer());
        $this->assertStringContainsString('- drop foreign key on [partner_id]', $this->output->getBuffer());
        $this->assertStringContainsString('- alter foreign key on [friend_id]', $this->output->getBuffer());

        $this->assertStringNotContainsString('default.users: 3 change(s) detected', $this->output->getBuffer());
    }
}
