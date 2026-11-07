<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function sprintf;
use function Symfony\Component\String\s;

use const PHP_BINARY;

/**
 * @internal
 */
#[CoversNothing]
final class DotenvParityTest extends TestCase
{
    use MakeTrait;

    private const array PARITY_DIRECTORY_BY_MAKEFILE = [
        'shared Makefile' => __DIR__ . '/../Fixtures/Make/DotenvParity',
        'starter'         => __DIR__ . '/../Fixtures/Make/DotenvStarterParity',
    ];

    private const string SYMFONY_PROGRAM = <<<'PHP'
        require $argv[2];

        try {
            (new Symfony\Component\Dotenv\Dotenv())->loadEnv($argv[1] . '/.env');
        } catch (Throwable $exception) {
            echo 'ERROR: ', str_replace($argv[1] . '/', '', strtok($exception->getMessage(), "\n"));

            exit;
        }

        $names = array_filter(explode(',', $_SERVER['SYMFONY_DOTENV_VARS'] ?? ''));

        sort($names);

        echo implode(' ', $names), "\n";

        foreach ($names as $name) {
            printf("%s=[%s]\n", $name, $_ENV[$name]);
        }
        PHP;

    /**
     * @param array<string, string> $files
     * @param array<string, string> $environment
     */
    #[DataProvider('provideMakeReadsEnvironmentFilesLikeSymfonyCases')]
    public function testMakeReadsEnvironmentFilesLikeSymfony(
        string $parityDirectory,
        array $files,
        array $environment = [],
    ): void {
        $directory  = $this->getFixtureCopy($parityDirectory);
        $filesystem = new Filesystem();

        foreach ($files as $fileName => $content) {
            $filesystem->dumpFile($directory . '/' . $fileName, $content);
        }

        $symfonyOutput = new Process(
            [
                PHP_BINARY,
                '-r',
                self::SYMFONY_PROGRAM,
                $directory,
                self::PROJECT_DIRECTORY . '/vendor/autoload.php',
            ],
            env: [
                ...self::getBaselineEnvironment(),
                ...$environment,
            ],
        )->mustRun()->getOutput();

        if (Str::startsWith($symfonyOutput, 'ERROR: ')) {
            $makeOutput = $this->runMake(['values'], $environment, $directory, doExpectFailure: true);

            self::assertStringContainsString(Str::trimPrefix($symfonyOutput, 'ERROR: ') . '  Stop.', $makeOutput);

            return;
        }

        $names          = s($symfonyOutput)->before("\n")->toString();
        $expectedValues = s($symfonyOutput)->after("\n")->toString();

        self::assertSame($expectedValues, $this->runMake(['values', 'NAMES=' . $names], $environment, $directory));
    }

    /**
     * @return iterable<string, array{
     *     0: string,
     *     1: array<string, string>,
     *     2?: array<string, string>,
     * }>
     */
    public static function provideMakeReadsEnvironmentFilesLikeSymfonyCases(): iterable
    {
        foreach (self::PARITY_DIRECTORY_BY_MAKEFILE as $makefile => $parityDirectory) {
            foreach (self::getEnvironmentFileCases() as $caseName => $case) {
                yield sprintf('%s × %s', $makefile, $caseName) => [$parityDirectory, ...$case];
            }
        }
    }

    /**
     * @return iterable<string, array{
     *     0: array<string, string>,
     *     1?: array<string, string>,
     * }>
     */
    private static function getEnvironmentFileCases(): iterable
    {
        yield 'plain' => [['.env' => "ACME_NAME=shop\nACME_TAG=1.0.0\n"]];

        yield 'comment after a quote' => [['.env' => "ACME_NAME=\"shop\" # the shop\n"]];

        yield 'escapes in double quotes' => [['.env' => "ACME_TEXT=\"a\\nb\\\"c\\\\d\"\n"]];

        yield 'concatenated segments' => [['.env' => "ACME_TEXT=\"a\"'b'c\n"]];

        yield 'multi-line value' => [['.env' => "ACME_TEXT=\"one\ntwo\"\nACME_LITERAL='one\ntwo \$ACME'\n"]];

        yield 'escaped dollars' => [['.env' => "ACME_A=\\\$HOME\nACME_B=\"\\\$HOME\"\nACME_C='\$HOME'\n"]];

        yield 'backslashes' => [['.env' => "ACME_A='a\\b'\nACME_B=a\\\\b\nACME_C=ends\\\n"]];

        yield 'carriage returns' => [['.env' => "ACME_NAME=shop\r\nACME_TEXT=\"two\r\nlines\"\r\n"]];

        yield 'comments and blank lines' => [
            [
                '.env' => "# Acme\n\n   # indented\nACME_NAME=shop#tag\nACME_TAG=1 #comment\nACME_EMPTY=\n",
            ],
        ];

        yield 'command' => [
            [
                '.env' => "ACME_TAG=1\nACME_IMAGE=\$(echo acme/shop:\$ACME_TAG)\nACME_WORDS=\$(printf 'a  b\\n\\nc')\n",
            ],
        ];

        yield 'escaped command' => [['.env' => "ACME_TEXT=\\\$(echo shop)\nACME_QUOTED='\$(echo shop)'\n"]];

        yield 'forward reference' => [['.env' => "ACME_IMAGE=acme/\${ACME_NAME}\nACME_NAME=shop\n"]];

        yield 'reference overridden later' => [
            [
                '.env'       => "ACME_TAG=1\nACME_IMAGE=shop:\${ACME_TAG}\n",
                '.env.local' => "ACME_TAG=2\n",
            ],
        ];

        yield 'defaults' => [
            [
                '.env' => "ACME_A=\${ACME_MISSING:-fallback}\n"
                    . "ACME_B=\${ACME_SET:=assigned}-\$ACME_SET\n"
                    . "ACME_C=\${ACME_MISSING:-\"quoted\"}\n",
            ],
        ];

        yield 'unbraced references' => [['.env' => "ACME_NAME=shop\nACME_A=\$ACME_NAME-x\nACME_B=\$ACME_NAME}\n"]];

        yield 'self reference across files' => [
            [
                '.env'       => "ACME_PATH=base\n",
                '.env.local' => "ACME_PATH=\${ACME_PATH}/local\n",
                '.env.dev'   => "ACME_PATH=\${ACME_PATH}/dev\n",
            ],
        ];

        yield 'self reference to a reference' => [
            [
                '.env'       => "ACME_BASE=base\nACME_PATH=\${ACME_BASE}/x\n",
                '.env.local' => "ACME_PATH=\${ACME_PATH}/y\n",
            ],
        ];

        yield 'self reference with a default' => [['.env' => "ACME_PATH=\${ACME_PATH:-fallback}\n"]];

        yield 'circular reference' => [['.env' => "ACME_A=\${ACME_B}\nACME_B=\${ACME_A}\n"]];

        yield 'long chain' => [
            [
                '.env' => "ACME_A=\${ACME_B}\nACME_B=\${ACME_C}\nACME_C=\${ACME_D}\n"
                    . "ACME_D=\${ACME_E}\nACME_E=\${ACME_F}\nACME_F=end\n",
            ],
        ];

        yield 'environment from a file' => [
            [
                '.env'      => "APP_ENV=prod\n",
                '.env.prod' => "ACME_NAME=prod\n",
            ],
        ];

        yield 'interpolated environment' => [
            [
                '.env'      => "ACME_STAGE=prod\nAPP_ENV=\${ACME_STAGE}\n",
                '.env.prod' => "ACME_NAME=prod\n",
            ],
        ];

        yield 'environment set in the local file' => [
            [
                '.env'       => "ACME_NAME=base\n",
                '.env.local' => "APP_ENV=prod\n",
                '.env.prod'  => "ACME_NAME=prod\n",
            ],
        ];

        yield 'test environment skips the local file' => [
            [
                '.env'       => "APP_ENV=test\nACME_NAME=base\n",
                '.env.local' => "ACME_NAME=local\n",
                '.env.test'  => "ACME_TAG=test\n",
            ],
        ];

        yield 'local environment' => [
            [
                '.env'             => "APP_ENV=local\n",
                '.env.local'       => "ACME_NAME=local\n",
                '.env.local.local' => "ACME_NAME=never\n",
            ],
        ];

        yield 'dist file' => [['.env.dist' => "ACME_NAME=dist\n"]];

        yield 'environment wins' => [
            ['.env' => "ACME_NAME=file\nACME_IMAGE=acme/\${ACME_NAME}\n"],
            ['ACME_NAME' => 'environment'],
        ];

        yield 'environment value with a dollar' => [
            ['.env' => "ACME_TEXT=\${ACME_RAW}\n"],
            ['ACME_RAW' => 'a$b\c'],
        ];

        yield 'environment self reference' => [
            ['.env' => "ACME_PATH=\${ACME_PATH}/file\n"],
            ['ACME_PATH' => 'environment'],
        ];

        yield 'unquoted spaces' => [['.env' => "ACME_NAME=acme shop\n"]];

        yield 'space before the equals sign' => [['.env' => "ACME_NAME =shop\n"]];

        yield 'space before the value' => [['.env' => "ACME_NAME= shop\n"]];

        yield 'missing equals sign' => [['.env' => "ACME_NAME\n"]];

        yield 'export without a value' => [['.env' => "export ACME_NAME\n"]];

        yield 'invalid name' => [['.env' => "ACME-NAME=shop\n"]];

        yield 'not an assignment' => [['.env' => "ACME_NAME=shop\nthis is not an assignment\n"]];

        yield 'unterminated double quote' => [['.env' => "ACME_NAME=\"shop\n"]];

        yield 'unterminated single quote' => [['.env' => "ACME_NAME='shop\n"]];

        yield 'unclosed brace' => [['.env' => "ACME_NAME=\${ACME_OTHER\n"]];

        yield 'unclosed command' => [['.env' => "ACME_NAME=\$(echo shop\n"]];

        yield 'dollar in a default' => [['.env' => "ACME_NAME=\${ACME_MISSING:-\$ACME_OTHER}\n"]];

        yield 'byte order mark' => [['.env' => "\u{FEFF}ACME_NAME=shop\n"]];
    }
}
