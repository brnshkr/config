# `TagDescriptionRule` [🔍](../../../../src/php/PhpStan/Rule/TagDescriptionRule.php 'Go to source')

Every tag description must take one shape, on `@api` and `@internal` symbols alike.
`@param`, `@property` and `@template` put a dash between the declaration and the description,
`@return` and `@throws` take none, and a description is a lowercase fragment without a closing period.

```php
// ❌ Bad
/**
 * @param User $user The user being renamed.
 *
 * @return User - the persisted user
 */

// ✅ Good
/**
 * @param User $user - the user being renamed
 *
 * @return User the persisted user
 */
```

A `@throws` description names the condition, opening with `when` or `unless`:

```php
// ❌ Bad
/**
 * @throws InvalidArgumentException if the name is empty
 */

// ✅ Good
/**
 * @throws InvalidArgumentException when the name is empty
 */
```

The dash follows the whole declaration, a multi-line type or a `@template` bound included,
and the description may run over several lines.

A capital letter counts as a sentence start only when a lowercase letter or a space follows it,
so `URL` and `PHPStan` pass, and so does a backticked first word.
Open with something other than a proper noun instead: `- options of the Symfony extension`.
