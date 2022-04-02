<?php declare(strict_types=1);

/*
 * This file is part of composer-require-better.
 *
 * (c) 2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__ . '/vendor/autoload.php';

$config = PhpCsFixerConfig\Factory::createForLibrary('composer-require-better', 'Kuba Werłos', 2020)
    ->setUsingCache(false)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->files()
            ->in(__DIR__ . '/../src')
            ->in(__DIR__ . '/../tests')
            ->append([__FILE__])
    );

$rules = $config->getRules();

unset($rules['use_arrow_functions']); // TODO: remove when dropping support to PHP <7.4
unset($rules[PhpCsFixerCustomFixers\Fixer\PromotedConstructorPropertyFixer::name()]); // TODO: remove when dropping support to PHP <8.0
$rules['trailing_comma_in_multiline'] = true; // TODO: remove when dropping support to PHP <8.0

return $config->setRules($rules);
