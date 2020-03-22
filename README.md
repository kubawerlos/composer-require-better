# composer-require-better

Plugin for [Composer](https://getcomposer.org) to require package with constraint having [patch version](https://semver.org).

[![Current version](https://img.shields.io/packagist/v/kubawerlos/composer-require-better.svg?label=Current%20version)](https://packagist.org/packages/kubawerlos/composer-require-better)
[![PHP version](https://img.shields.io/packagist/php-v/kubawerlos/composer-require-better.svg)](https://php.net)
[![CI Status](https://github.com/kubawerlos/composer-require-better/workflows/CI/badge.svg?branch=master&event=push)](https://github.com/kubawerlos/composer-require-better/actions)
[![Code coverage](https://img.shields.io/coveralls/github/kubawerlos/composer-require-better/master.svg)](https://coveralls.io/github/kubawerlos/composer-require-better?branch=master)
[![Psalm type coverage](https://shepherd.dev/github/kubawerlos/composer-require-better/coverage.svg)](https://shepherd.dev/github/kubawerlos/composer-require-better)


## Installation
```bash
composer global require kubawerlos/composer-require-better
```


## Usage
```bash
composer rb vendor/package
```
All Composer's [require](https://getcomposer.org/doc/03-cli.md#require) options (except `prefer-lowest` and `prefer-stable`) can be used.


## Motivation
Let's assume we want to install package `acme-corporation/adding-machine` for our project and it has versions `1.0.0` and `1.0.1` released. Usually, we run:
```bash
composer require acme-corporation/adding-machine
```
We will have the latest version installed (`1.0.1`) and constraint `^1.0` added to `composer.json`. The constraint means all version from `1.0.0`, but lower than `2.0.0` are allowed.

This can result in some problems in the future:
 1. If we would want to install another package, that allows `acme-corporation/adding-machine` only in version `1.0.0` (or has a conflict with `acme-corporation/adding-machine` version `1.0.1`) it would result with `acme-corporation/adding-machine` being downgraded to version `1.0.0` - we can easily miss that downgrade (as it will be one line in the console) - what if `1.0.1` fixes critical bug for us?
 2. If we run `composer update --prefer-lowest` (quite often practice when developing a library) we would end up with `acme-corporation/adding-machine` in version `1.0.0`.
 3. Command `composer update` could take a long time to run when having many packages with many allowed versions (e.g. Symfony 3 LTS has current version `3.4.38`, so constraint `^3.4` is allowing 39 versions - from `3.4.0` to `3.4.38`).

So instead we can run:
```bash
composer rb acme-corporation/adding-machine
```
We will have the latest version installed - the same as with `require` command, but the constraint added to `composer.json` will be `^1.0.1` - it would mean all version from `1.0.0`, but lower than `2.0.0` are allowed.
What would that change?
 1. If we would want to install the package that previously downgraded `acme-corporation/adding-machine` we would see an error and would have to make a decision - is this acceptable to us or we cannot allow it?
 2. Running `composer update --prefer-lowest` would do nothing for the package as now installed version is the lowest allowed with the constraint.
 3. Command `composer update` would work faster - mentioned Symfony 3 LST constraint would be `^3.4.38`, so it would allow only single version, not 39 versions.
 4. In `composer.json` we now have the installed version as the constraint, so we don't have to check with `composer show` or in `composer.lock` (if we even have it in the repository) which version is used in the project.
