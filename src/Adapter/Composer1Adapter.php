<?php declare(strict_types=1);

/*
 * This file is part of composer-require-better.
 *
 * (c) 2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace RequireBetter\Adapter;

use Composer\DependencyResolver\Pool;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\PlatformRepository;

/**
 * @internal
 */
final class Composer1Adapter implements AdapterInterface
{
    public function findBestCandidate(VersionSelector $versionSelector, string $package, string $targetPhpVersion)
    {
        return $versionSelector->findBestCandidate($package, '*', $targetPhpVersion);
    }

    public function getRepositorySet()
    {
        return new Pool();
    }

    public function createVersionSelector($repositorySet, PlatformRepository $platformRepo): VersionSelector
    {
        return new VersionSelector($repositorySet);
    }
}
