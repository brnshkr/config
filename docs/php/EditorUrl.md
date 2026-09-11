# EditorUrl [🔍](../../src/php/EditorUrl.php 'Go to source')

`Brnshkr\Config\EditorUrl` builds the URL template a tool turns into a clickable "jump to IDE" link beside every error.
[`PhpStan`](./phpstan/index.md) and [`Rector`](./Rector.md) call it for you.

## Usage

```php
use Brnshkr\Config\EditorUrl;

$phpStanEditorUrl = EditorUrl::forPhpStan();
$rectorEditorUrl  = EditorUrl::forRector();
```

Each factory emits its own tool's placeholders. Pass a project root as the second argument
when the links should not be relative to the working directory.

## Customizing

Auto-detection covers VS Code and PhpStorm. To override it, pass `EditorUrl::EDITOR_VSCODE`
or `EditorUrl::EDITOR_PHPSTORM` as the first argument, or set one of these:

- `EDITOR` — the name of a supported editor
- `EDITOR_URL` — a template of your own, in `{cwd}`, `{file}`, `{line}` and `{wslDistro}`
  placeholders, for an editor that is not supported

An explicit argument wins over `EDITOR_URL`, which wins over `EDITOR`, which wins over detection.
When nothing answers, the factories return `null` and the tool emits no links.
