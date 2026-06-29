<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateInMemoryUsersCommandTest extends KernelTestCase
{
    public function testMigrateInMemoryUsersDryRunSucceeds(): void
    {
        $commandTester = $this->buildCommandTester();

        $commandTester->execute([
            '--dry-run' => true,
        ]);
        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('utilisateur(s) traité(s)', $output);
    }

    private function buildCommandTester(): CommandTester
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:migrate-inmemory-users');

        return new CommandTester($command);
    }
}
