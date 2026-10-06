<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function array_diff_key;
use function array_flip;
use function array_keys;
use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class FuzzTest extends TestCase
{
    use MakeTrait;

    private const string CACHES_DIRECTORY = __DIR__ . '/../Fixtures/Make/Caches';
    private const string GROUP_DIRECTORY  = __DIR__ . '/../Fixtures/Make/Group';
    private const string SENTINEL         = 'fuzz-ran';

    private const array PAYLOADS = [
        'command substitution'   => '$(touch${IFS}fuzz-ran)',
        'backticks'              => '`touch${IFS}fuzz-ran`',
        'separator'              => 'x;touch${IFS}fuzz-ran;y',
        'single-quote breakout'  => 'x\';touch${IFS}fuzz-ran;\'y',
        'double-quote breakout'  => 'x";touch${IFS}fuzz-ran;"y',
        'pipe'                   => 'x|touch${IFS}fuzz-ran',
        'make function'          => '$(shell touch fuzz-ran)',
        'make wildcards'         => 'a%b#c*d',
        'terminal escapes'       => "x\e]0;title\x07\e[2Jy",
        'newline'                => "x\ny",
        'option'                 => '--version',
        'backslash and variable' => 'x\y$HOME',
    ];

    private const array TARGETS_BY_CHANNEL = [
        'tracked file names'   => ['help', 'configs'],
        'cache names'          => ['cc', 'cc nope'],
        'stage names'          => ['help'],
        'unquoted .env values' => ['help resolve', 'help env'],
        'quoted .env values'   => ['help resolve', 'help env'],
        'commit messages'      => ['changelog'],
        'tags'                 => ['changelog'],
        'package.json names'   => ['help', 'configs'],
        'composer.json names'  => ['help', 'configs'],
        'autoload paths'       => ['help', 'configs'],
        'project paths'        => ['help'],
        'tool output'          => ['group-text', 'group-text V=1'],
    ];

    private const array UNCARRIED_PAYLOADS_BY_CHANNEL = [
        'tags' => [
            'make function',
            'make wildcards',
            'terminal escapes',
            'newline',
            'option',
            'backslash and variable',
        ],
        'tool output' => ['newline'],
    ];

    /**
     * @param key-of<self::TARGETS_BY_CHANNEL> $channel
     */
    #[DataProvider('provideNoPayloadEscapesItsChannelCases')]
    public function testNoPayloadEscapesItsChannel(string $channel, string $payload): void
    {
        $directory = match ($channel) {
            'tracked file names'   => $this->seedTrackedFileName($payload),
            'cache names'          => $this->seedCacheName($payload),
            'stage names'          => $this->seedStageName($payload),
            'unquoted .env values' => $this->seedDotenvValue($payload),
            'quoted .env values'   => $this->seedDotenvValue(sprintf('\'%s\'', $payload)),
            'commit messages'      => $this->seedCommitMessage($payload),
            'tags'                 => $this->seedTag($payload),
            'package.json names'   => $this->seedPackageName($payload),
            'composer.json names'  => $this->seedComposerName($payload),
            'autoload paths'       => $this->seedAutoloadPath($payload),
            'project paths'        => $this->seedProjectPath($payload),
            'tool output'          => $this->seedToolOutput($payload),
        };

        $runs = [];

        foreach (self::TARGETS_BY_CHANNEL[$channel] as $target) {
            $runs[$target] = [
                'args'      => Str::split($target, ' '),
                'directory' => $directory,
            ];
        }

        foreach (self::runMakeAllowingRefusals($runs) as $target => $process) {
            $output  = $this->readMakeOutput($process, true);
            $command = sprintf('make %s', $target);

            self::assertStringNotContainsString("\e", $output, $command);
            self::assertStringNotContainsString("\x07", $output, $command);

            self::assertDoesNotMatchRegularExpression(
                '/unterminated|missing separator|awk:|syntax error|bad substitution|not found|missing \'}\'/i',
                $output,
                $command,
            );

            if (!$process->isSuccessful()) {
                self::assertMatchesRegularExpression('/^\[.+\] |\*\*\* \[.+\] .*  Stop\.$/m', $output, $command);
            }
        }

        $finder = new Finder()
            ->in($this->fixtureRoots)
            ->name(self::SENTINEL)
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
        ;

        self::assertFalse($finder->hasResults(), 'a payload ran code');
    }

    /**
     * @return iterable<string, array{key-of<self::TARGETS_BY_CHANNEL>, string}>
     */
    public static function provideNoPayloadEscapesItsChannelCases(): iterable
    {
        foreach (array_keys(self::TARGETS_BY_CHANNEL) as $channel) {
            $uncarriedPayloads = array_flip(self::UNCARRIED_PAYLOADS_BY_CHANNEL[$channel] ?? []);

            foreach (array_diff_key(self::PAYLOADS, $uncarriedPayloads) as $payloadName => $payload) {
                yield sprintf('%s × %s', $channel, $payloadName) => [$channel, $payload];
            }
        }
    }

    private function seedTrackedFileName(string $payload): string
    {
        $directory  = $this->createFixtureCopy(self::CONSUMER_DIRECTORY);
        $filesystem = new Filesystem();

        $filesystem->dumpFile($directory . '/' . $payload . '.md', '');
        $filesystem->dumpFile($directory . '/.vscode/' . $payload . '.css-data.json', '{}');
        new Process(['git', 'add', '--all'], $directory)->mustRun();

        return $directory;
    }

    private function seedCacheName(string $payload): string
    {
        $directory = $this->createFixtureCopy(self::CACHES_DIRECTORY);

        new Filesystem()->mkdir($directory . '/.cache/' . $payload);

        return $directory;
    }

    private function seedStageName(string $payload): string
    {
        $directory = $this->createFixtureCopy(self::CONSUMER_DIRECTORY);

        new Filesystem()->dumpFile($directory . '/.env.' . $payload, '');

        return $directory;
    }

    private function seedDotenvValue(string $value): string
    {
        $directory = $this->createFixtureCopy(self::CONSUMER_DIRECTORY);

        new Filesystem()->dumpFile($directory . '/.env', sprintf("ACME_VALUE=%s\n", $value));

        return $directory;
    }

    private function seedCommitMessage(string $payload): string
    {
        $directory = $this->createFixtureCopy(self::CONSUMER_DIRECTORY);

        self::commitInto($directory, sprintf("feat(user): %s\n\n%s", $payload, $payload));
        self::commitInto($directory, sprintf('fix(%s): keep address casing', $payload));

        return $directory;
    }

    private function seedTag(string $payload): string
    {
        $directory = $this->createFixtureCopy(self::CONSUMER_DIRECTORY);

        self::commitInto($directory, 'feat(user): import users');
        new Process(['git', '-c', 'tag.gpgsign=false', 'tag', '--', $payload], $directory)->mustRun();

        return $directory;
    }

    private function seedPackageName(string $payload): string
    {
        $directory = $this->createFixtureCopy(self::CONSUMER_DIRECTORY);

        new Filesystem()->dumpFile($directory . '/package.json', Json::encode(['name' => $payload]));

        return $directory;
    }

    private function seedComposerName(string $payload): string
    {
        $directory = $this->createFixtureCopy(self::CONSUMER_DIRECTORY);

        new Filesystem()->dumpFile($directory . '/composer.json', Json::encode(['name' => 'acme/' . $payload]));

        return $directory;
    }

    private function seedAutoloadPath(string $payload): string
    {
        $directory  = $this->createFixtureCopy(self::CONSUMER_DIRECTORY);
        $filesystem = new Filesystem();

        $filesystem->dumpFile($directory . '/composer.json', Json::encode([
            'name'     => 'acme/user',
            'autoload' => ['psr-4' => ['Acme\\' => $payload . '/']],
        ]));

        $filesystem->mkdir($directory . '/' . $payload);

        return $directory;
    }

    private function seedProjectPath(string $payload): string
    {
        $directory = $this->createFixtureRoot() . '/x' . $payload;

        new Filesystem()->dumpFile($directory . '/Makefile', "include ../conf/Makefile\n");

        return $directory;
    }

    private function seedToolOutput(string $payload): string
    {
        $directory = $this->createFixtureCopy(self::GROUP_DIRECTORY);

        new Filesystem()->dumpFile(
            $directory . '/errors.txt',
            sprintf("src/%s.ts(3,5): error TS2345: %s\n", $payload, $payload),
        );

        return $directory;
    }
}
