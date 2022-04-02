<?php declare(strict_types=1);

/*
 * This file is part of composer-require-better.
 *
 * (c) 2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace RequireBetter;

use Composer\Command\RequireCommand;
use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositorySet;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RequireBetterCommand extends RequireCommand
{
    /** @var string */
    protected static $defaultName = 'rb';

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

        /** @var array<string> $packages */
        $packages = $input->getArgument('packages');

        /** @var array<array<string>> $requires */
        $requires = $this->normalizeRequirements($packages);

        $input->setArgument(
            'packages',
            \array_map(
                function (array $require): string {
                    return $this->addVersionToPackage($require);
                },
                $requires
            )
        );

        return parent::execute($input, $output);
    }

    /**
     * @param array<string> $require
     */
    private function addVersionToPackage(array $require): string
    {
        if (\array_key_exists('version', $require)) {
            throw new \RuntimeException('Passing version constraint is not allowed, use "require" command to do it.');
        }

        $package = $require['name'];

        $versionSelector = $this->getVersionSelector();

        $bestCandidate = $versionSelector->findBestCandidate($package);

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

    private function getVersionSelector(): VersionSelector
    {
        $repositorySet = new RepositorySet();

        $repositorySet->addRepository($this->getRepository());

        return new VersionSelector($repositorySet, $this->getPlatformRepository());
    }

    private function getRepository(): CompositeRepository
    {
        $composer = $this->getComposer();
        \assert($composer instanceof Composer);

        $repositories = $composer->getRepositoryManager()->getRepositories();

        return new CompositeRepository(\array_merge(
            [$this->getPlatformRepository()],
            $repositories
        ));
    }

    private function getPlatformRepository(): PlatformRepository
    {
        $composer = $this->getComposer();
        \assert($composer instanceof Composer);

        /** @var array<string, string> $platformOverrides */
        $platformOverrides = $composer->getConfig()->get('platform');

        return new PlatformRepository([], $platformOverrides);
    }

    private function normalizeVersion(string $version): string
    {
        $parts = \explode('.', $version);

        return '^' . \implode('.', \array_slice($parts, 0, 3));
    }
}
