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

use Composer\Console\Application;
use RequireBetter\RequireBetterCommand;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
final class Runner
{
    public const DIRECTORY = __DIR__ . '/tmp';

    /** @var string */
    private $directory;

    /**
     * @param array<mixed> $composerJsonContent
     */
    public function __construct(array $composerJsonContent = [])
    {
        $this->directory = self::DIRECTORY . '/' . \uniqid();

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->directory);

        \file_put_contents(
            $this->directory . '/composer.json',
            \json_encode(\array_merge(['require' => new \stdClass()], $composerJsonContent))
        );
    }

    /**
     * @param array<string, array<string>|bool> $parameters
     */
    public function run(string $commandName, array $parameters = []): string
    {
        $application = new Application();
        $application->add(new RequireBetterCommand());
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $applicationTester = new ApplicationTester($application);
        $applicationTester->run(\array_merge(
            [
                'command' => $commandName,
                '--no-interaction' => true,
                '--no-plugins' => true,
                '--working-dir' => $this->directory,
            ],
            $parameters
        ));

        return $applicationTester->getDisplay();
    }

    /**
     * @return array<mixed>
     */
    public function getComposerJsonDecoded(): array
    {
        /** @var string $content */
        $content = \file_get_contents($this->directory . '/composer.json');

        return \json_decode($content, true);
    }
}
