<?php declare(strict_types=1);

/*
 * This file is part of composer-require-better.
 *
 * (c) 2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use PHPUnit\Framework\TestCase;
use RequireBetter\RequireBetterCommand;
use RequireBetter\RequireBetterPlugin;

/**
 * @internal
 *
 * @covers \RequireBetter\RequireBetterPlugin
 */
final class RequireBetterPluginTest extends TestCase
{
    /**
     * @dataProvider providePluginInterfaceUsageDoesNotUsePluginManagerCases
     */
    public function testPluginInterfaceUsageDoesNotUsePluginManager(string $function): void
    {
        $plugin = new RequireBetterPlugin();

        $composer = $this->createMock(Composer::class);
        $composer->expects(self::never())->method('getPluginManager');

        $plugin->{$function}($composer, $this->createMock(IOInterface::class));
    }

    public static function providePluginInterfaceUsageDoesNotUsePluginManagerCases(): iterable
    {
        yield ['activate'];
        yield ['deactivate'];
        yield ['uninstall'];
    }

    public function testCapabilities(): void
    {
        $plugin = new RequireBetterPlugin();

        self::assertSame(
            [CommandProvider::class => RequireBetterPlugin::class],
            $plugin->getCapabilities(),
        );
    }

    public function testCommands(): void
    {
        $plugin = new RequireBetterPlugin();

        $commands = $plugin->getCommands();

        self::assertCount(1, $commands);
        self::assertContainsOnlyInstancesOf(RequireBetterCommand::class, $commands);
    }
}
