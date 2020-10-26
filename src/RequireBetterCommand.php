<?php

/*
 * This file is part of composer-require-better.
 *
 * (c) 2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace RequireBetter;

use Composer\Command\RequireCommand;
use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use RequireBetter\Adapter\AdapterFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RequireBetterCommand extends RequireCommand
{
    /** @var string */
    protected static $defaultName = 'rb';

    /** @var AdapterFactory */
    private $adapterFactory;

    public function __construct()
    {
        $this->adapterFactory = new AdapterFactory((string) Composer::getVersion());

        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName(self::$defaultName);
        $this->setDescription('Adds required packages to your composer.json (with patch version) and installs them.');
        $this->setHelp(\sprintf('The %s command', self::$defaultName));

        $definition = $this->getDefinition();

        $definition->setArguments([
            new InputArgument(
                'packages',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Package name(s) without a version constraint, e.g. foo/bar'
            ),
        ]);

        $definition->setOptions(
            \array_filter(
                $definition->getOptions(),
                static function (InputOption $option): bool {
                    return $option->getName() !== 'prefer-lowest' && $option->getName() !== 'prefer-stable';
                }
            )
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->addOption('prefer-lowest');
        $this->addOption('prefer-stable');

        /** @var string[] $packages */
        $packages = $input->getArgument('packages');

        /** @var string[][] $requires */
        $requires = $this->normalizeRequirements($packages);

        $input->setArgument(
            'packages',
            \array_map(
                function (array $require): string {
                    return $this->addVersionToPackage($require, $this->getTargetPhpVersion());
                },
                $requires
            )
        );

        return parent::execute($input, $output);
    }

    /**
     * @param string[] $require
     */
    private function addVersionToPackage(array $require, string $targetPhpVersion): string
    {
        if (isset($require['version'])) {
            throw new \RuntimeException('Passing version constraint is not allowed, use "require" command to do it.');
        }

        $package = $require['name'];

        $versionSelector = $this->getVersionSelector($targetPhpVersion);

        $bestCandidate = $this->adapterFactory->create()->findBestCandidate($versionSelector, $package, $targetPhpVersion);

        if (!$bestCandidate instanceof PackageInterface) {
            throw new \RuntimeException(\sprintf('Could not find a stable version of package %s.', $package));
        }

        $version = $this->normalizeVersion($bestCandidate->getVersion());

        $this->getIO()->writeError(\sprintf(
            'Using version <info>%s</info> for <info>%s</info>',
            $version,
            $package
        ));

        return \sprintf('%s:%s', $package, $version);
    }

    private function getVersionSelector(string $targetPhpVersion): VersionSelector
    {
        $adapter = $this->adapterFactory->create();

        $repositorySet = $adapter->getRepositorySet();

        $repositorySet->addRepository($this->getRepository());

        return $adapter->createVersionSelector($repositorySet, $this->getPlatformRepository());
    }

    private function getTargetPhpVersion(): string
    {
        $repository = $this->getRepository();

        /** @var PackageInterface $package */
        $package = $repository->findPackage('php', '*');

        return $package->getPrettyVersion();
    }

    private function getRepository(): CompositeRepository
    {
        /** @var Composer $composer */
        $composer = $this->getComposer();

        $repositories = $composer->getRepositoryManager()->getRepositories();

        return new CompositeRepository(\array_merge(
            [$this->getPlatformRepository()],
            $repositories
        ));
    }

    private function getPlatformRepository(): PlatformRepository
    {
        /** @var Composer $composer */
        $composer = $this->getComposer();

        /** @var string[] $platformOverrides */
        $platformOverrides = $composer->getConfig()->get('platform');

        return new PlatformRepository([], $platformOverrides);
    }

    private function normalizeVersion(string $version): string
    {
        $parts = \explode('.', $version);

        return '^' . \implode('.', \array_slice($parts, 0, 3));
    }
}
