<?php

declare(strict_types=1);

namespace Tests;

use Composer\Composer;
use Composer\Console\Application;
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
    public function testActivatingAndDeactivating(): void
    {
        $plugin = new RequireBetterPlugin();

        $application = new Application();
        $composer = $application->getComposer(true, true);
        $pluginManager = $composer->getPluginManager();

        self::assertEmpty($pluginManager->getPlugins());

        $pluginManager->addPlugin($plugin);
        self::assertCount(1, $pluginManager->getPlugins());
        self::assertContainsOnlyInstancesOf(RequireBetterPlugin::class, $pluginManager->getPlugins());

        if (\method_exists($plugin, 'removePlugin')) {
            $pluginManager->removePlugin($plugin);
            self::assertEmpty($pluginManager->getPlugins());

            $pluginManager->addPlugin($plugin);
            self::assertCount(1, $pluginManager->getPlugins());
            self::assertContainsOnlyInstancesOf(RequireBetterPlugin::class, $pluginManager->getPlugins());

            $pluginManager->uninstallPlugin($plugin);
        }
    }

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
            $plugin->getCapabilities()
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
