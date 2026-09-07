# `NamedArgumentsUsageRule` [🔍](../../../../src/php/PhpStan/Rule/NamedArgumentsUsageRule.php 'Go to source')

A call to a symbol tagged `@named-arguments` must name every argument it passes.
PHPStan already reports the opposite mistake — naming an argument of a `@no-named-arguments` symbol.

The payoff is that a parameter can be inserted _before_ an existing one without breaking a caller,
because no caller depends on position. That only holds while every argument is named,
so a single positional argument is reported.

```php
/**
 * @api
 *
 * @named-arguments
 */
final class Registration
{
    public function __construct(
        public string $email = '',
        public string $locale = '',
    ) {}
}

// ❌ Bad — positional, so inserting a parameter before `$email` would break this call
new Registration('ada@example.com', 'en');

// ❌ Bad — one positional argument is enough to break it
new Registration('ada@example.com', locale: 'en');

// ✅ Good
new Registration(email: 'ada@example.com', locale: 'en');
```

## Resolution

The stance resolves from the nearest declaration, exactly as visibility does: **a method overrides its class**.
So one class can require names where they matter and waive them where they do not.

```php
/**
 * @named-arguments
 */
final class UserService
{
    public function __construct(public array $listeners = []) {}

    /**
     * One obvious argument, so naming it adds nothing.
     *
     * @no-named-arguments
     */
    public function setListeners(array $listeners): self
    {
        $this->listeners = $listeners;

        return $this;
    }
}

// ✅ Good — the class requires names
new UserService(listeners: []);

// ✅ Good — the method waived them
$userService->setListeners([]);
```

## Exemptions

- **Private and protected methods.** They are nobody's contract, so no rename can break an outside caller.
- **Calls from inside the declaring class.** The class is not its own consumer.
- **`@no-named-arguments` symbols.** PHPStan's own `argument.named` covers the opposite direction.

`@internal` does not excuse a call. A symbol that declares the stance is held to it wherever it is reachable
from — visibility and named arguments are independent claims, and `@internal` only means no stance is _required_.
