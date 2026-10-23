<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversNothing]
final class MatrixTest extends TestCase
{
    use MakeTrait;
    use MatchesSnapshots;

    private const string MATRIX_DIRECTORY = __DIR__ . '/../Fixtures/Make/Matrix';

    public function testEveryRowRunsTheTestsOnItsImageWithItsConstraints(): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);

        $result = $this->runMake(['test-matrix'], self::createEnvironment($matrixDirectory, 'true'), $matrixDirectory);

        self::assertStringContainsString(
            'update acme/email acme/user --with-all-dependencies --with=acme/email:^2.0 --with=acme/user:^2.0',
            $result,
        );

        self::assertStringContainsString('bun pm pkg set overrides.@acme/ui=^3.1', $result);
        self::assertStringContainsString('matrix-test on ghcr.io/acme/app:php8.4-bun1.3-dev in acme-matrix as run', $result);
        self::assertStringContainsString('update --prefer-lowest', $result);
        self::assertStringContainsString('matrix-test on ghcr.io/acme/app:php8.5-bun1.4-dev in acme-matrix as run', $result);
        self::assertCount(1, Str::matchAll($result, '/composer install --no-progress/'));
    }

    public function testAPassedRowRunsAlone(): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);

        $result = $this->runMake(
            ['test-matrix', '--', 'tag[php8.5-bun1.4];composer[*:lowest]'],
            self::createEnvironment($matrixDirectory, 'true'),
            $matrixDirectory,
        );

        self::assertStringContainsString('matrix-test on ghcr.io/acme/app:php8.5-bun1.4-dev in acme-matrix as run', $result);
        self::assertStringNotContainsString('php8.4', $result);
    }

    public function testNamedPackagesGoLowestWhileOthersStayLocked(): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);

        $result = $this->runMake(
            ['test-matrix', 'TEST_MATRIX=composer[acme/user:lowest&acme/email:^2.0]'],
            self::createEnvironment($matrixDirectory, 'true'),
            $matrixDirectory,
        );

        self::assertStringContainsString('update acme/user --prefer-lowest --with-all-dependencies', $result);
        self::assertStringContainsString('update acme/email --with-all-dependencies --with=acme/email:^2.0', $result);
        self::assertStringContainsString('matrix-test on ghcr.io/acme/app:1-dev@sha256:0000 in acme-matrix as run', $result);
    }

    public function testAnImageAndATagCompose(): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);

        $composed = $this->runMake(
            ['test-matrix', 'TEST_MATRIX=image[registry.acme.test/user];tag[php8.5-bun1.4]'],
            self::createEnvironment($matrixDirectory, 'true'),
            $matrixDirectory,
        );

        $otherVariable = $this->runMake(
            [
                'test-matrix',
                'TEST_MATRIX_IMAGE_KEY=USER_IMAGE',
                'USER_IMAGE=registry.acme.test/user:2',
                'TEST_MATRIX=tag[php8.5-bun1.4]',
            ],
            self::createEnvironment($matrixDirectory, 'true'),
            $matrixDirectory,
        );

        self::assertStringContainsString('matrix-test on registry.acme.test/user:php8.5-bun1.4-dev in acme-matrix', $composed);
        self::assertStringContainsString('matrix-test on registry.acme.test/user:php8.5-bun1.4 in acme-matrix', $otherVariable);
    }

    public function testARowRunLocallyPutsTheLockFilesBack(): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);

        $result = $this->runMake(
            ['test-matrix', 'TEST_MATRIX=composer[*:lowest]'],
            self::createEnvironment($matrixDirectory, ''),
            $matrixDirectory,
        );

        self::assertStringContainsString('update --prefer-lowest', $result);
        self::assertStringContainsString('composer install --no-progress', $result);
        self::assertSame("{}\n", new Filesystem()->readFile($matrixDirectory . '/composer.lock'));
    }

    public function testAFailingRowLetsTheOthersRunAndAddsARowEachToTheCiSummary(): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);
        $summaryPath     = $matrixDirectory . '/summary.md';

        $result = $this->runMake(
            ['test-matrix', 'TEST_MATRIX=composer[acme/user:^9.0] composer[*:lowest]'],
            [
                ...self::createEnvironment($matrixDirectory, 'true'),
                'CI_SUMMARY_FILE'   => $summaryPath,
                'GITHUB_REPOSITORY' => 'acme/matrix',
                'GITHUB_RUN_ID'     => '1',
                'GITHUB_SERVER_URL' => 'https://github.com',
            ],
            $matrixDirectory,
            doExpectFailure: true,
        );

        self::assertStringContainsString('matrix-test on ghcr.io/acme/app:1-dev@sha256:0000 in acme-matrix as run', $result);
        self::assertStringContainsString('`composer[acme/user:^9.0]` failed.', $result);

        $this->assertMatchesSnapshot(Str::replaceMatches(
            new Filesystem()->readFile($summaryPath),
            '/\| \d+s \|/',
            static fn (): string => '| <seconds>s |',
        ));
    }

    public function testCiRunsTheMatrixAfterTheTestsUnlessItIsEmptied(): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);
        $environment     = self::createEnvironment($matrixDirectory, 'true');
        $ci              = $this->runMake(['ci'], $environment, $matrixDirectory);
        $emptied         = $this->runMake(['ci', 'TEST_MATRIX='], $environment, $matrixDirectory);

        self::assertMatchesRegularExpression('/ in acme as [^\n]*\n.* in acme-matrix as run\n/s', $ci);
        self::assertStringContainsString(' in acme as ', $emptied);
        self::assertStringNotContainsString('acme-matrix', $emptied);
    }

    public function testTheRecipeStaysFarBelowTheShellsArgumentLimit(): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);

        $recipe = $this->runMake(['-n', 'test-matrix'], self::createEnvironment($matrixDirectory, 'true'), $matrixDirectory);

        self::assertLessThan(65_536, Str::length($recipe));
    }

    public function testTheRowsPrintAsAJsonList(): void
    {
        $listed   = $this->runMake(['-s', 'test-matrix-rows'], directory: self::MATRIX_DIRECTORY);
        $escaped  = $this->runMake(['-s', 'test-matrix-rows', 'TEST_MATRIX=tag[a\b] tag["c"]'], directory: self::MATRIX_DIRECTORY);
        $none     = $this->runMake(['-s', 'test-matrix-rows', 'TEST_MATRIX='], directory: self::MATRIX_DIRECTORY);
        $untested = $this->runMake(['-s', 'test-matrix-rows', 'TEST_TARGETS='], directory: self::MATRIX_DIRECTORY);

        self::assertSame(
            [
                'tag[php8.4-bun1.3];composer[acme/*:^2.0];bun[@acme/ui:^3.1]',
                'tag[php8.5-bun1.4];composer[*:lowest]',
            ],
            Json::decode($listed, isAssociative: false),
        );

        self::assertSame(['tag[a\b]', 'tag["c"]'], Json::decode($escaped, isAssociative: false));
        self::assertSame([], Json::decode($none, isAssociative: false));
        self::assertSame([], Json::decode($untested, isAssociative: false));
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('provideAWrongRowIsRefusedCases')]
    public function testAWrongRowIsRefused(array $arguments, string $message): void
    {
        $matrixDirectory = $this->getFixtureCopy(self::MATRIX_DIRECTORY);

        $result = $this->runMake(
            ['test-matrix', ...$arguments],
            self::createEnvironment($matrixDirectory, 'true'),
            $matrixDirectory,
            doExpectFailure: true,
        );

        self::assertStringContainsString($message, $result);
        self::assertStringNotContainsString('matrix-test on', $result);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideAWrongRowIsRefusedCases(): iterable
    {
        yield 'no rows' => [['TEST_MATRIX='], '`TEST_MATRIX` has no rows, so `test-matrix` has nothing to run.'];

        yield 'unlisted row' => [['--', 'composer[acme/usr:lowest]'], '`composer[acme/usr:lowest]` is no `TEST_MATRIX` row.'];

        yield 'repeated row' => [
            ['TEST_MATRIX=composer[*:lowest] composer[*:lowest]'],
            '`composer[*:lowest]` is in `TEST_MATRIX` twice.',
        ];

        yield 'broken later row' => [
            ['TEST_MATRIX=composer[*:lowest] pip[acme:1.0]'],
            '`pip[…]` is no matrix field, use `image`, `tag`, `composer` or `bun`.',
        ];

        yield 'no brackets' => [['TEST_MATRIX=acme'], '`acme` is no field, write `name[value]`.'];

        yield 'empty block' => [['TEST_MATRIX=composer[]'], '`composer[]` is no field, write `name[value]`.'];

        yield 'repeated block' => [
            ['TEST_MATRIX=composer[acme/user:^2.0];composer[acme/email:^2.0]'],
            '`composer[…]` is in one row twice.',
        ];

        yield 'repeated package' => [
            ['TEST_MATRIX=composer[acme/*:^2.0&acme/user:lowest]'],
            '`acme/user` is moved twice in one row.',
        ];

        yield 'no constraint' => [['TEST_MATRIX=composer[acme/user]'], '`acme/user` is no `package:constraint`.'];

        yield 'wildcard constraint' => [['TEST_MATRIX=composer[*:^2.0]'], '`*` only takes `lowest`, not `^2.0`.'];

        yield 'nothing matched' => [['TEST_MATRIX=composer[missing/*:^1.0]'], '`missing/*` matches no installed package.'];

        yield 'everything and some lowest' => [
            ['TEST_MATRIX=composer[*:lowest&acme/user:lowest]'],
            '`*:lowest` already covers `acme/user`.',
        ];

        yield 'bun lowest' => [
            ['TEST_MATRIX=bun[@acme/ui:lowest]'],
            '`@acme/ui:lowest` needs a lower bound, since Bun resolves no lowest versions.',
        ];

        yield 'repeated bun package' => [
            ['TEST_MATRIX=bun[@acme/ui:^3.1&@acme/ui:^3.2]'],
            '`@acme/ui` is moved twice in one row.',
        ];

        yield 'tag with a separator' => [['TEST_MATRIX=tag[php8.5:dev]'], '`php8.5:dev` is no tag, drop its `:`, `/` or `@`.'];

        yield 'tag next to a full image' => [
            ['TEST_MATRIX=image[registry.acme.test/user:2];tag[php8.5-bun1.4]'],
            '`tag[php8.5-bun1.4]` contradicts `image[registry.acme.test/user:2]`, which carries its own tag.',
        ];

        yield 'image without its variable' => [
            ['TEST_MATRIX_IMAGE_KEY=', 'TEST_MATRIX=tag[php8.5-bun1.4]'],
            '`tag[php8.5-bun1.4]` needs `TEST_MATRIX_IMAGE_KEY`, set it to the variable the compose file reads the image from.',
        ];
    }

    /**
     * @return array{
     *     CI: string,
     *     PATH: string,
     * }
     */
    private static function createEnvironment(string $matrixDirectory, string $continuousIntegration): array
    {
        return [
            'CI'   => $continuousIntegration,
            'PATH' => $matrixDirectory . '/bin:' . Str::fromEnvironment('PATH'),
        ];
    }
}
