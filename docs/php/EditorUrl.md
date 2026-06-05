# EditorUrl [🔍](../../src/php/EditorUrl.php 'Go to source')

`Brnshkr\Config\EditorUrl` produces the URL templates that PHPStan and Rector emit as per-error "jump to IDE" links. The shape of those links depends on both the editor (`vscode://...`, `phpstorm://...`) and the calling tool's own placeholder syntax (PHPStan uses `%%relFile%%`, Rector uses `%relFile%`); `EditorUrl` knows about both and produces the correct template for each combination.

## What it does

- Targets one of the supported editors. The current set is exposed as `EditorUrl::EDITOR_VSCODE` and `EditorUrl::EDITOR_PHPSTORM` — pass one of these constants to pin the editor, or omit the argument to let `EditorUrl` auto-detect.
- Auto-detects the editor from `VSCODE_*` / `PHPSTORM*` / `TERMINAL_EMULATOR` environment variables first, then by probing `PATH` for the editor's CLI command (`code`, `pstorm`, `phpstorm`).
- Switches the VSCode template to the `vscode-remote/wsl+<distro>` variant when `WSL_DISTRO_NAME` is set, so links land in the WSL host instead of the Windows one.
- Returns `null` when no editor is configured and none can be detected — both `forPhpStan()` and `forRector()` accept that gracefully.

## Usage

`EditorUrl` exposes one static factory per supported tool. Each returns a ready-to-use template string (or `null` when no editor can be resolved).

```php
use Brnshkr\Config\EditorUrl;

// Most setups need only auto-detection.
$phpStanEditorUrl = EditorUrl::forPhpStan();
$rectorEditorUrl  = EditorUrl::forRector();

// Pin the editor — and optionally the project root — when auto-detection picks the wrong one.
$phpStanEditorUrl = EditorUrl::forPhpStan(EditorUrl::EDITOR_PHPSTORM, __DIR__);
$rectorEditorUrl  = EditorUrl::forRector(EditorUrl::EDITOR_VSCODE, __DIR__);
```

Both `Brnshkr\Config\PhpStan::getConfig()` and `Brnshkr\Config\Rector::getConfig()` already call the matching factory internally, so most consumers never reach for `EditorUrl` themselves. The two cases that bring it into play are assembling a tool configuration by hand outside this package's builders, and pinning the editor when auto-detection cannot see it. The latter shows up in Docker setups in particular — the host's `VSCODE_*` environment variables do not reach the container and the `code` CLI is not on the container's `PATH`, so `EditorUrl::forPhpStan()` (and friends) return `null` unless the caller passes one of the `EditorUrl::EDITOR_*` constants explicitly.
