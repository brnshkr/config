# `SuperglobalUsageRule` [🔍](../../../../src/php/PhpStan/Rule/SuperglobalUsageRule.php 'Go to source')

Production code must not read or write a superglobal.
The configuration already bans `getenv()` and the native functions it has a replacement for,
but a superglobal is a variable, which a function ban never sees,
so this rule matches the variable's name and reports what to use instead.

```php
namespace Acme\Email;

// ❌ Bad — the host comes from a global no caller can see
final readonly class Mailer
{
    public function send(string $message): void
    {
        $this->connect($_ENV['MAILER_HOST'])->write($message);
    }
}

// ✅ Good — the environment is read in one place and the host injected
final readonly class Mailer
{
    public function __construct(
        private string $host,
    ) {}

    public function send(string $message): void
    {
        $this->connect($this->host)->write($message);
    }
}
```

The configuration builder enables it with a replacement for `$_SERVER`, `$_ENV` and `$GLOBALS`,
and for the request superglobals, such as `$_GET`, once `symfony/http-foundation` is installed.
The `autoload-dev` directories may still use them.

## Options

| Option | Description |
| --- | --- |
| `forbiddenSuperglobals` | what to use instead, keyed by the superglobal |
