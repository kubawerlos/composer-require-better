<?php

declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use RequireBetter\RequireBetterCommand;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 *
 * @covers \RequireBetter\RequireBetterCommand
 */
final class RequireBetterCommandTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove(Runner::DIRECTORY);
    }

    public function testPackagesDescription(): void
    {
        $command = new RequireBetterCommand();

        self::assertSame(
            'Package name(s) without a version constraint, e.g. foo/bar',
            $command->getDefinition()->getArgument('packages')->getDescription()
        );
    }

    /**
     * @dataProvider provideNotAllowedOptionCases
     */
    public function testNotAllowedOption(string $option): void
    {
        $runner = new Runner();

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage(\sprintf('The "%s" option does not exist.', $option));

        $runner->run('rb', ['packages' => ['psr/log'], $option => true]);
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function provideNotAllowedOptionCases(): iterable
    {
        yield '--prefer-lowest' => ['--prefer-lowest'];
        yield '--prefer-stable' => ['--prefer-stable'];
    }

    public function testWithoutPackageResultsWithAnError(): void
    {
        $runner = new Runner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "packages").');

        $runner->run('rb');
    }

    public function testWithNonExistingPackageResultsWithAnError(): void
    {
        $runner = new Runner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find a stable version of package foo/bar.');

        $runner->run('rb', ['packages' => ['foo/bar']]);
    }

    public function testWithPackageNotHavingStableVersionResultsWithAnError(): void
    {
        $runner = new Runner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find a stable version of package roave/security-advisories.');

        $runner->run('rb', ['packages' => ['roave/security-advisories']]);
    }

    public function testWithColonSeparatedSpecifiedVersionResultsWithAnError(): void
    {
        $runner = new Runner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Passing version constraint is not allowed, use "require" command to do it.');

        $runner->run('rb', ['packages' => ['foo/bar:^1.2']]);
    }

    public function testWithSpaceSeparatedSpecifiedVersionResultsWithAnError(): void
    {
        $runner = new Runner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Passing version constraint is not allowed, use "require" command to do it.');

        $runner->run('rb', ['packages' => ['foo/bar', '^1.2']]);
    }

    public function testSinglePackage(): void
    {
        $runner = new Runner();
        $runner->run('rb', ['packages' => ['psr/log'], '--no-update' => true]);

        $json = $runner->getComposerJsonDecoded();

        self::assertArrayHasKey('require', $json);
        self::assertIsArray($json['require']);
        self::assertArrayHasKey('psr/log', $json['require']);
        self::assertRegExp('/^\^\d+\.\d+\.\d+$/', $json['require']['psr/log']);
    }

    public function testMultiplePackages(): void
    {
        $runner = new Runner();

        $packages = ['psr/container', 'psr/http-message', 'psr/log'];

        $runner->run('rb', ['packages' => $packages, '--no-update' => true]);

        $json = $runner->getComposerJsonDecoded();

        self::assertArrayHasKey('require', $json);
        self::assertIsArray($json['require']);
        foreach ($packages as $package) {
            self::assertArrayHasKey($package, $json['require']);
            self::assertRegExp('/^\^\d+\.\d+\.\d+$/', $json['require'][$package]);
        }
    }

    public function testWithUpdate(): void
    {
        $runner = new Runner();
        $runner->run('rb', ['packages' => ['psr/log']]);

        $json = $runner->getComposerJsonDecoded();

        self::assertArrayHasKey('require', $json);
        self::assertIsArray($json['require']);
        self::assertArrayHasKey('psr/log', $json['require']);
        self::assertRegExp('/^\^\d+\.\d+\.\d+$/', $json['require']['psr/log']);
    }

    public function testRespectingPhpVersion(): void
    {
        $runner = new Runner(['config' => ['platform' => ['php' => '5.6.40']]]);

        $runner->run('rb', ['packages' => ['phpunit/phpunit'], '--no-update' => true]);

        $json = $runner->getComposerJsonDecoded();

        self::assertArrayHasKey('require', $json);
        self::assertIsArray($json['require']);
        self::assertArrayHasKey('phpunit/phpunit', $json['require']);
        self::assertRegExp('/^\^5\.\d+\.\d+$/', $json['require']['phpunit/phpunit']);
    }

    /**
     * @dataProvider provideWithCustomRepositoryCases
     */
    public function testWithCustomRepository(string $expectedConstraint, string $customRepositoryVersion): void
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir(Runner::DIRECTORY . '/foo');

        \file_put_contents(
            Runner::DIRECTORY . '/foo/composer.json',
            \json_encode([
                'name' => 'foo/bar',
                'version' => $customRepositoryVersion,
            ])
        );

        $runner = new Runner(['repositories' => [['type' => 'path', 'url' => '../foo']]]);

        $runner->run('rb', ['packages' => ['foo/bar'], '--no-update' => true]);

        $json = $runner->getComposerJsonDecoded();

        self::assertArrayHasKey('require', $json);
        self::assertIsArray($json['require']);
        self::assertArrayHasKey('foo/bar', $json['require']);
        self::assertSame($expectedConstraint, $json['require']['foo/bar']);
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function provideWithCustomRepositoryCases(): iterable
    {
        foreach ([
            '1.2.3' => '^1.2.3',
            'v1.2.3' => '^1.2.3',
            'v1.2' => '^1.2.0',
            'v1' => '^1.0.0',
        ] as $customRepositoryVersion => $expectedConstraint) {
            yield $customRepositoryVersion => [$expectedConstraint, $customRepositoryVersion];
        }
    }

    public function testOutputIsSimilarToRequireOutput(): void
    {
        $packages = ['psr/log', 'doctrine/cache'];

        $runner = new Runner();
        $requireOutput = $runner->run('require', ['packages' => $packages, '--no-update' => true]);

        $runner = new Runner();
        $requireBetterOutput = $runner->run('rb', ['packages' => $packages, '--no-update' => true]);

        self::assertSame(4, \levenshtein($requireOutput, $requireBetterOutput));
    }
}
