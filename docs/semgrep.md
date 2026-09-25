# semgrep [🔍](../conf/semgrep.dist.yaml 'Go to source')

`./conf/semgrep.dist.yaml` is the @brnshkr semgrep configuration, ready to copy into a project.
Its `rules` are the project's own, run beside rulesets from the Semgrep registry.

## Usage

```yaml
# ./conf/semgrep.dist.yaml
x-brnshkr: {}

rules: []
```

`semgrep scan --config p/default --config ./conf/semgrep.dist.yaml` reads it beside one registry ruleset.
The shared Makefile picks the rulesets that fit the manifests, the installed packages and the tracked files,
and caches each one in `./.cache/semgrep/` for `SEMGREP_MAX_AGE` days. `make configs` writes the copy.

## Customizing

The shared Makefile reads `x-brnshkr`. A ruleset set to `true` or `false` overrides what was detected,
and `ignores` takes rule ids, everywhere or for the files named, each with a comment saying why.

```yaml
# ./conf/semgrep.dist.yaml
x-brnshkr:
  react: false
  terraform: true
  ignores:
    - javascript.lang.security.audit.sqli.node-knex-sqli.node-knex-sqli
    - rule: javascript.lang.security.audit.detect-non-literal-regexp.detect-non-literal-regexp
      files:
        - src/config.ts

rules: []
```

`SEMGREP_RULESETS` replaces the detected list outright.
