---
name: add-custom-rule
description: Add a custom ESLint, markdownlint, Stylelint, PHPStan, PHP-CS-Fixer, Rector or Twig-CS-Fixer rule to this repository, with its test, docs page and pairing. Not for configuring an existing rule.
---

# Custom rules

Every rule is a source file, a test and its docs.

| Tool | Source | Test | Page |
| --- | --- | --- | --- |
| ESLint | `src/js/eslint/configs/builtin/<rule>.ts` | `tests/js/eslint-rules/` | `docs/js/eslint/rules/<rule>.md` |
| markdownlint | `src/js/markdownlint/configs/builtin/<rule>.ts`, in `RULE_DEFINITIONS` | `tests/js/markdownlint-rules/` | `docs/js/markdownlint/rules/<rule>.md` |
| Stylelint | `src/js/stylelint/configs/builtin/<rule>.ts`, a `stylelint.createPlugin()` in its module's `plugins` | `tests/js/stylelint-rules/` | `docs/js/stylelint/rules/<rule>.md` |
| PHPStan | `src/php/PhpStan/Rule/<Name>Rule.php` | `tests/php/PhpStan/Rule/`, fixtures in `tests/php/Fixtures/PhpStan/Rule/<Name>/` | `docs/php/phpstan/rules/<Name>Rule.md` |
| PHP-CS-Fixer | `src/php/PhpCsFixer/Fixer/`, a `FixerTrait` class the `PhpCsFixer` builder registers | `tests/php/PhpCsFixer/Fixer/` | `docs/php/php-cs-fixer/rules/` |
| Rector | `src/php/Rector/Rule/`, in a set in `src/php/Rector/config/` | `tests/php/Rector/Rule/` on `AbstractRectorTestCase`, `*.inc` fixtures in `tests/php/Fixtures/Rector/Rule/<Name>/` | `docs/php/rector/rules/` |
| Twig-CS-Fixer | `src/php/TwigCsFixer/Rule/<Name>Rule.php`, added in the `TwigCsFixer` builder | `tests/php/TwigCsFixer/Rule/`, fixtures in `tests/php/Fixtures/TwigCsFixer/` | its section in `docs/php/TwigCsFixer.md` |

## Workflow

1. Read the closest sibling rule and its test, and mirror both.
2. Write the rule, its test and its page. The page carries the examples, `❌ Bad` then `✅ Good`.
3. Add the rule to its tool's `rules/index.md` under the fitting section. Twig-CS-Fixer has none.
4. An ESLint rule and a PHPStan rule come in pairs.
   `RuleDocsTest` fails until both exist, or the rule is in its `JS_ONLY_RULES` or `PHP_ONLY_RULES`.
5. Run the rule's test,
   then update the config snapshot with `brnshkr-test-run --doUpdateSnapshots` and read its diff.
6. Lint the whole repository once with the new rule.

## Rules

- A PHPStan rule's fixture marks each expected error with `// ERROR <context>`.
  Two errors on one line, or a line that cannot carry the comment (e.g. in an XML or Makefile code block),
  take `RuleTestCase` with an explicit list.
- Fixtures use the vendor `Acme`.
