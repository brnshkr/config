# PHPUnit [🔍](../../conf/phpunit.dist.xml 'Go to source')

`./conf/phpunit.dist.xml` is the @brnshkr PHPUnit configuration, ready to copy into a project.
It runs the tests in random order, requires coverage metadata,
and fails on every deprecation, notice, warning and risky, skipped or incomplete test.

## Usage

```xml
<!-- ./conf/phpunit.dist.xml -->
<testsuites>
  <testsuite name="App\Tests">
    <directory>../tests</directory>
  </testsuite>

  <testsuite name="Brnshkr\Config">
    <directory>../vendor/brnshkr/config/src/php/Testing</directory>
  </testsuite>
</testsuites>
```

`phpunit --configuration ./conf/phpunit.dist.xml` reads the whole copy, which `make configs` writes.
The `Brnshkr\Config` suite runs the tests this package ships, the [spelling check](../spelling.md) among them.

## Customizing

PHPUnit cannot extend a config, so change the copy itself.
A private `./conf/phpunit.xml` replaces it outright rather than layering on it.
A Symfony application names its test environment and kernel beside the shipped settings.

```xml
<!-- ./conf/phpunit.dist.xml -->
<php>
  <ini name="display_errors" value="1"/>
  <ini name="error_reporting" value="-1"/>
  <ini name="memory_limit" value="512M"/>
  <server name="APP_ENV" value="test" force="true"/>
  <server name="KERNEL_CLASS" value="App\Kernel"/>
</php>
```
