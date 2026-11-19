# Blocks

```xml
<testsuites>
  <testsuite name="Acme">
</testsuites>
```

```xml
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd">
  <unknown/>
</phpunit>
```

```xml
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="./vendor/acme/missing.xsd">
  <unknown/>
</phpunit>
```

```xml
<phar xmlns="https://phar.io/xml/manifest/1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://phar.io/xml/manifest/1.0 ./vendor/phar-io/manifest/manifest.xsd https://acme.example/schema ./vendor/acme/missing.xsd">
  <unknown/>
</phar>
```

```Makefile
acme-greet:
    @echo hello
```

```makefile
include ./vendor/brnshkr/config/conf/Makefile

#~~! acme-unknown
```

```makefile
include ./node_modules/@brnshkr/config/conf/Makefile

#~~! acme-missing
```

```makefile
include ./vendor/acme/missing/Makefile

acme-greet: #~~ greets the user
	@echo hello
```

<!-- brnshkr-skip -->
```xml
<broken>
```
