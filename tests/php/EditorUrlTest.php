<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\EditorUrl;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function in_array;

/**
 * @internal
 */
#[CoversClass(EditorUrl::class)]
#[UsesClass(Str::class)]
final class EditorUrlTest extends TestCase
{
    private const array MANAGED_KEYS = [
        'EDITOR',
        'EDITOR_URL',
        'PATH',
        'TERM_PROGRAM',
        'TERMINAL_EMULATOR',
        'WSL_DISTRO_NAME',
    ];

    /**
     * @var array<array-key, mixed>
     */
    private array $server = [];

    /**
     * @var array<array-key, mixed>
     */
    private array $environment = [];

    #[Before]
    public function saveEnvironment(): void
    {
        $this->server      = $_SERVER;
        $this->environment = $_ENV;

        foreach ([...array_keys($_SERVER), ...array_keys($_ENV)] as $key) {
            $key = (string) $key;

            if (in_array($key, self::MANAGED_KEYS, true) || Str::startsWith($key, 'VSCODE_') || Str::startsWith($key, 'PHPSTORM')) {
                unset($_SERVER[$key], $_ENV[$key]);
            }
        }
    }

    #[After]
    public function resetEnvironment(): void
    {
        $_SERVER = $this->server;
        $_ENV    = $this->environment;
    }

    public function testNothingDetectedYieldsNoTemplate(): void
    {
        self::assertNull(EditorUrl::forPhpStan());
        self::assertNull(EditorUrl::forRector());
    }

    public function testEditorNamesTheEditor(): void
    {
        $_SERVER['EDITOR'] = EditorUrl::EDITOR_PHPSTORM;

        self::assertSame(
            'phpstorm://open?file=/repo/%%relFile%%&line=%%line%%',
            EditorUrl::forPhpStan(null, '/repo'),
        );
    }

    public function testAnUnsupportedEditorIsIgnored(): void
    {
        $_SERVER['EDITOR'] = 'vim';

        self::assertNull(EditorUrl::forPhpStan());
    }

    public function testTermProgramNamesVsCode(): void
    {
        $_SERVER['TERM_PROGRAM'] = EditorUrl::EDITOR_VSCODE;

        self::assertSame('vscode://file//repo/%relFile%:%line%', EditorUrl::forRector(null, '/repo'));
    }

    public function testWslSwitchesTheVsCodeTemplate(): void
    {
        $_SERVER['EDITOR']          = EditorUrl::EDITOR_VSCODE;
        $_SERVER['WSL_DISTRO_NAME'] = 'Ubuntu';

        self::assertSame(
            'vscode://vscode-remote/wsl+Ubuntu/repo/%relFile%:%line%',
            EditorUrl::forRector(null, '/repo'),
        );
    }

    public function testEditorUrlOverridesTheEditorItWouldPick(): void
    {
        $_SERVER['EDITOR']     = EditorUrl::EDITOR_PHPSTORM;
        $_SERVER['EDITOR_URL'] = 'zed://file/{cwd}/{file}:{line}';

        self::assertSame('zed://file//repo/%%relFile%%:%%line%%', EditorUrl::forPhpStan(null, '/repo'));
    }

    public function testAnEditorGivenAtTheCallSiteOverridesTheEnvironment(): void
    {
        $_SERVER['EDITOR']     = EditorUrl::EDITOR_PHPSTORM;
        $_SERVER['EDITOR_URL'] = 'zed://file/{cwd}/{file}:{line}';

        self::assertSame(
            'vscode://file//repo/%relFile%:%line%',
            EditorUrl::forRector(EditorUrl::EDITOR_VSCODE, '/repo'),
        );
    }
}
