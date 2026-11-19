# 🧩 Custom markdownlint rules

All rules ship as `brnshkr/<rule>` and are enabled by the default configuration unless the `brnshkr` option is `false`.

## Code

- [`brnshkr/no-php-open-tag`](./no-php-open-tag.md)
  — a page must not carry a PHP open tag, nor a `php` code block `declare(strict_types=1);`

## Comments

- [`brnshkr/no-warning-comments`](./no-warning-comments.md)
  — a `TODO`, `FIXME`, `XXX` or `HACK` must not stand in prose, an HTML comment or a code block
