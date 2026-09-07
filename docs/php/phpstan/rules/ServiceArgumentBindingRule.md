# `ServiceArgumentBindingRule` [🔍](../../../../src/php/PhpStan/Rule/ServiceArgumentBindingRule.php 'Go to source')

Every service argument bound by name must name a parameter that exists.
Binding by name makes that name part of the contract, and nothing checks it. The container is the first to object,
at compile time, in whichever application installs the package rather than the one that broke it.

```php
final class Mailer
{
    public function __construct(
        private string $dsn = '',
        private int $retries = 0,
    ) {}
}

// ❌ Bad — a typo, and nothing fails until an application compiles its container
$services->set('app.mailer', Mailer::class)
    ->arg('$dns', 'smtp://localhost')
;

// ✅ Good
$services->set('app.mailer', Mailer::class)
    ->arg('$dsn', 'smtp://localhost')
    ->arg('$retries', 3)
;
```

## What binds by name

| Call | Where the name sits | Parameters it must match |
| --- | --- | --- |
| `->arg('$dsn', …)` | first argument | the constructor |
| `->bind('$dsn', …)` | first argument | the constructor |
| `->args(['$dsn' => …])` | array key | the constructor |
| `->call('setUp', ['$mode' => …])` | array key | the method it names |

The class is the one the definition names, through `->set()` written either way, or through `inline_service()`.

```php
// ❌ Bad — an inline definition binds by name too
$services->set('app.mailer', Mailer::class)
    ->arg('$dsn', inline_service(Mailer::class)
        ->arg('$dns', 'smtp://inline'))
;
```

## Factories

A factory replaces the constructor with the callable it names, wherever it sits in the chain.

| Form | Parameters come from |
| --- | --- |
| `->factory([MailerFactory::class, 'create'])` | `MailerFactory::create()` |
| `->factory(MailerFactory::create(...))` | `MailerFactory::create()` |
| `->factory('Acme\Mail\MailerFactory::create')` | `MailerFactory::create()` |
| `->factory([null, 'fromDsn'])` | `fromDsn()` on the defined class |
| `->constructor('fromDsn')` | `fromDsn()` on the defined class |

```php
// ✅ Good — `$region` is a parameter of the factory, not of Mailer
$services->set('app.mailer', Mailer::class)
    ->factory([MailerFactory::class, 'create'])
    ->arg('$region', 'eu')
;
```

## Exemptions

A binding is skipped when there is nothing to check it against: a name built at run time,
a class the analysis cannot resolve, a `->call()` naming a method the class does not declare,
or a factory naming a service or an expression rather than a class and a method.

So is every binding that names no single class. `->bind()` on `defaults()` or `instanceof()`
applies to whatever the container matches, and a `->load()` prototype to a whole namespace.

A `->bind()` given a fully qualified class name binds a type rather than a parameter, so there is no name to check.

The receiver's type decides whether a call is a binding, never the method's name.
A class of your own with an `arg()` method is left alone.

The rule registers only when `symfony/dependency-injection` is installed.
