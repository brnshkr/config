# actionlint [🔍](../conf/actionlint.dist.yaml 'Go to source')

`./conf/actionlint.dist.yaml` is the @brnshkr actionlint configuration, ready to copy into a project.
It accepts `RUNNER_IMAGE` as the only `vars.` name and no self-hosted runner label.

## Usage

```yaml
# ./conf/actionlint.dist.yaml
config-variables:
  - RUNNER_IMAGE
```

`actionlint -config-file ./conf/actionlint.dist.yaml` reads the whole copy, which `make configs tools` writes.
The shared Makefile passes it over every tracked workflow.

If your app image has no actionlint, add an `actionlint` service, its entrypoint cleared:

```yaml
# ./conf/compose.yaml
services:
  actionlint:
    image: rhysd/actionlint:<tag>
    entrypoint: []
    profiles:
      - actionlint
    working_dir: /app
    volumes:
      - ..:/app
```

## Customizing

actionlint cannot extend a config, so change the copy itself.

```yaml
# ./conf/actionlint.dist.yaml
config-variables:
  - RUNNER_IMAGE
  - DEPLOY_TARGET
```
