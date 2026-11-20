<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Unit\Generator;

use Cycle\Schema\Generator\PrintChanges;
use Cycle\Schema\Generator\RenderTables;
use Cycle\Schema\Registry;
use Cycle\Schema\Tests\Fixtures\User;
use Cycle\Testing\Database\Memory;
use Symfony\Component\Console\Output\BufferedOutput;
use Testo\Assert;
use Testo\Test;

#[Test]
final class PrintChangesTest
{
    public function countsTheChangesOfANewTable(): void
    {
        $output = new BufferedOutput();
        $generator = new PrintChanges($output);

        $generator->run($this->registry());

        Assert::true($generator->hasChanges());
        Assert::string($output->fetch())
            ->contains('Schema changes:')
            ->contains('• default.users: 5 change(s) detected');
    }

    public function describesANewTableWhenVerbose(): void
    {
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_VERBOSE);

        (new PrintChanges($output))->run($this->registry());

        Assert::string($output->fetch())
            ->contains('- create table')
            ->contains('- add column [user_name]')
            ->notContains('change(s) detected');
    }

    private function registry(): Registry
    {
        $user = User::define();
        $registry = new Registry(Memory::manager());
        $registry->register($user)->linkTable($user, 'default', 'users');

        return (new RenderTables())->run($registry);
    }
}
