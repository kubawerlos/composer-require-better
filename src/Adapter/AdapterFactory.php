<?php

declare(strict_types=1);

/*
 * This file is part of composer-require-better.
 *
 * (c) 2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace RequireBetter\Adapter;

/**
 * @internal
 */
final class AdapterFactory
{
    /** @var string */
    private $composerVersion;

    public function __construct(string $composerVersion)
    {
        $this->composerVersion = $composerVersion;
    }

    public function create(): AdapterInterface
    {
        if ($this->composerVersion[0] === '1') {
            return new Composer1Adapter();
        }

        if ($this->composerVersion[0] === '2') {
            return new Composer2Adapter();
        }

        throw new \Exception(\sprintf('Invalid Composer version "%s" - expected first character to be "1" or "2"', $this->composerVersion));
    }
}
