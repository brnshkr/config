# hadolint [🔍](../conf/hadolint.dist.yaml 'Go to source')

`./conf/hadolint.dist.yaml` is the @brnshkr hadolint configuration, ready to copy into a project.
It requires the eight static OCI labels on every image and ignores `DL3008`,
since apt installs from the live mirror, which keeps only each package's newest version.

## Usage

```yaml
# ./conf/hadolint.dist.yaml
failure-threshold: style
strict-labels: true
```

`hadolint --config ./conf/hadolint.dist.yaml` reads the whole copy, which `make configs` writes.
The shared Makefile passes it over every tracked Dockerfile.

## Customizing

hadolint cannot extend a config, so change the copy itself.

```yaml
# ./conf/hadolint.dist.yaml
ignored:
  - DL3008
  - DL3059
```
