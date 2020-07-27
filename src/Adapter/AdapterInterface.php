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

namespace RequireBetter\Adapter;

use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\PlatformRepository;

/**
 * @internal
 */
interface AdapterInterface
{
    /**
     * @return bool|PackageInterface
     */
    public function findBestCandidate(VersionSelector $versionSelector, string $package, string $targetPhpVersion);

    /**
     * Composer 1 needs Composer\DependencyResolver\Pool
     * Composer 2 needs Composer\Repository\RepositorySet
     *
     * @return \Composer\DependencyResolver\Pool|\Composer\Repository\RepositorySet
     */
    public function getRepositorySet();

    /**
     * @param \Composer\DependencyResolver\Pool|\Composer\Repository\RepositorySet $repositorySet
     */
    public function createVersionSelector($repositorySet, PlatformRepository $platformRepo): VersionSelector;
}
