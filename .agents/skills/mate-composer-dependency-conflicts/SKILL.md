---
name: mate-composer-dependency-conflicts
description: Find out why a Composer package is installed, or why a version cannot be installed, with composer-explain. Use when an install or update is refused by a constraint, a package appears in vendor that nobody required directly, or an upgrade has to be planned before it is attempted, in a project that already has a composer.json and an installed vendor/. Read-only; to perform the change use composer dependency changes. Not for a standalone script or algorithm task with no Composer project in scope, and not for "which version is installed" (system-information covers that).
---

# Composer conflict diagnosis

One read-only tool, two questions:

- `composer-explain --package=<vendor/name>` runs `composer why`: what pulls this in.
- `composer-explain --package=<vendor/name> --version=<constraint>` runs `composer why-not`: what blocks this version.
- `composer://config` gives the other half of the picture, the constraints the project declares itself.

These commands accept `--format`: `json` to parse the result, `toon` (when `helgesverre/toon` is installed) for the smallest context footprint.

Reach for them before an update rather than after a failure. They cost nothing and turn "the update refuses" into a named package and constraint.

## Workflow

1. Ask the question: `vendor/bin/mate tools:call composer-explain --package=symfony/console --version='^8.0'`.
2. Name the blocker: the package whose `constraint` excludes the version you want.
3. Ask what would move it. The blocker often has a newer release with a wider constraint, so the fix is updating blocker and target **together** in one `composer-update --packages='blocker target'`, not the target alone.
4. When the blocker is the project's own `composer.json`, the change is a constraint edit and a deliberate decision. Say so; do not widen a constraint on your own initiative. When it is `php` or an extension, no dependency change helps: report the runtime gap.

## Reading

`dependencies` is the answer, one entry per relation: `package` and `version` (who holds the constraint, at which installed version), `relation` (`requires` or `does not require`), `target` and `constraint` (what they demand).

- The project itself appears as the package `__root__`. That entry means `composer.json` requires the target directly.
- For **why**, a package listed only by other vendor packages, with no `__root__` entry, is a transitive dependency. Do not add it to `composer.json` because you saw it in `vendor/`, and do not remove it directly either. An empty list means nothing requires it at all, which points at a stale `vendor/`.
- For **why-not**, `does not require` entries are Composer explaining what it checked, not a cause. Only the excluding `constraint` is.
- `--mode=detailed` keeps Composer's raw text under `metadata.raw_output`. Fall back to it when the parsed list looks thinner than the real answer: the parser reads flat `A vX requires B (constraint)` lines, and nested conflict trees do not always survive that.

## Failure paths

- Empty result for a package you can see in `vendor/`: check the spelling. Package names are case-sensitive and always `vendor/name`.
- `why-not` returns nothing useful: the version may simply not exist. Confirm the available releases before treating it as a conflict.
- A non-zero exit is normal here; output is what matters, and the tool reports `SUCCESS` whenever Composer explained something.
- Report the blocking package, its version, and its constraint. That one line is the answer; the rest of the payload is not.
