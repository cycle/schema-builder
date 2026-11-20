<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Generator;

use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Generator\PrintChanges;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\User;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class PrintChangesTest extends BaseTest
{
    private BufferedOutput $output;
    private Entity $user;
    private Registry $registry;
    private PrintChanges $generator;
    private Compiler $compiler;

    #[\Testo\Test]
    public function testRunWithoutChanges(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);
        $this->registry->getTableSchema($this->user)->save();

        $this->generator->run($this->registry);

        \Testo\Assert::string($this->output->fetch())->contains('No database changes has been detected');
    }

    #[\Testo\Test]
    public function testRunCreateTable(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);
        $this->generator->run($this->registry);

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users: 5 change(s) detected');
    }

    #[\Testo\Test]
    public function testRunCreateTableVerbose(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->output->setVerbosity(BufferedOutput::VERBOSITY_VERBOSE);
        $this->generator->run($this->registry);

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users')->contains('- create table')->contains('- add column [id]')->contains('- add column [user_name]')->contains('- add column [active]')->contains('- add column [balance]')->contains('- add column [created_at]')->notContains('default.users: 5 change(s) detected');
    }

    #[\Testo\Test]
    public function testRunDropTable(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->save();
        $this->registry->getTableSchema($this->user)->declareDropped();

        $this->generator->run($this->registry);

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users    - drop table');
    }

    #[\Testo\Test]
    public function testRunChangedColumns(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->save();
        $this->registry->getTableSchema($this->user)->column('new_column')->string();
        $this->registry->getTableSchema($this->user)->column('user_name')->integer();
        $this->registry->getTableSchema($this->user)->dropColumn('created_at');

        $this->generator->run($this->registry);

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users: 3 change(s) detected')->notContains('- add column [new_column]')->notContains('- drop column [created_at]')->notContains('- alter column [user_name]');
    }

    #[\Testo\Test]
    public function testRunChangedColumnsVerbose(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->save();
        $this->registry->getTableSchema($this->user)->column('new_column')->string();
        $this->registry->getTableSchema($this->user)->column('user_name')->integer();
        $this->registry->getTableSchema($this->user)->dropColumn('created_at');

        $this->output->setVerbosity(BufferedOutput::VERBOSITY_VERBOSE);
        $this->generator->run($this->registry);

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users')->contains('- add column [new_column]')->contains('- drop column [created_at]')->contains('- alter column [user_name]')->notContains('default.users: 3 change(s) detected');
    }

    #[\Testo\Test]
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

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users: 3 change(s) detected')->notContains('- add index on [created_at]')->notContains('- drop index on [balance]')->notContains('- alter index on [user_name]');
    }

    #[\Testo\Test]
    public function testRunChangedIndexesVerbose(): void
    {
        $this->compiler->compile($this->registry, [new RenderTables()]);

        $this->registry->getTableSchema($this->user)->index(['user_name']);
        $this->registry->getTableSchema($this->user)->index(['balance']);
        $this->registry->getTableSchema($this->user)->save();

        $this->registry->getTableSchema($this->user)->renameIndex(['user_name'], 'changed');
        $this->registry->getTableSchema($this->user)->dropIndex(['balance']);
        $this->registry->getTableSchema($this->user)->index(['created_at']);

        $this->output->setVerbosity(BufferedOutput::VERBOSITY_VERBOSE);
        $this->generator->run($this->registry);

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users')->contains('- add index on [created_at]')->contains('- drop index on [balance]')->contains('- alter index on [user_name]')->notContains('default.users: 3 change(s) detected');
    }

    #[\Testo\Test]
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

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users: 3 change(s) detected')->notContains('- add foreign key on [some_id]')->notContains('- drop foreign key on [partner_id]')->notContains('- alter foreign key on [friend_id]');
    }

    #[\Testo\Test]
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

        $this->output->setVerbosity(BufferedOutput::VERBOSITY_VERBOSE);
        $this->generator->run($this->registry);

        $content = $this->output->fetch();
        \Testo\Assert::string($content)->contains('Schema changes:')->contains('default.users')->contains('- add foreign key on [some_id]')->contains('- drop foreign key on [partner_id]')->contains('- alter foreign key on [friend_id]')->notContains('default.users: 3 change(s) detected');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->output = new BufferedOutput();
        $this->user = User::define();

        $this->registry = new Registry($this->dbal);
        $this->registry->register($this->user);
        $this->registry->linkTable($this->user, 'default', 'users');

        $this->generator = new PrintChanges($this->output);

        $this->compiler = new Compiler();
    }
}
