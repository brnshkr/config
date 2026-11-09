# 🐚 ShellCheck [🔍](../conf/shellcheckrc.dist 'Go to source')

`./conf/shellcheckrc.dist` is the @brnshkr ShellCheck configuration, ready to copy into a project.
It turns on every optional check and follows the files a script sources.

## Usage

```ini
# ./conf/shellcheckrc.dist
enable=all
external-sources=true
```

`shellcheck --rcfile ./conf/shellcheckrc.dist ./script.sh` reads the whole copy, which `make configs tools` writes.
The shared Makefile passes it over every shell script Git does not ignore, found by its extension or its shebang.
It checks the `sh`, `bash`, and `shell` code blocks in your pages too.
A code block below `<!-- brnshkr-skip -->` is left alone. A placeholder like `<tag>` counts as a plain word.

If your app image has no ShellCheck, add a `shellcheck` service:

```yaml
# ./conf/compose.yaml
services:
  shellcheck:
    image: koalaman/shellcheck-alpine:<tag>
    profiles:
      - shellcheck
    working_dir: /app
    volumes:
      - ..:/app
```

## Customizing

ShellCheck cannot extend a config, so change the copy itself.

```ini
# ./conf/shellcheckrc.dist
enable=all
external-sources=true
disable=SC2250
```
