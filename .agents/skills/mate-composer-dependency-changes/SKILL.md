---
name: mate-composer-dependency-changes
description: Add, remove, update, or install Composer dependencies through Mate, and read what the run actually changed. Use when a package should be required or dropped, dependencies refreshed after a branch switch, or a vendor directory rebuilt, in a project that already has a composer.json. For diagnosing why a package is present or why a version will not install, use composer dependency conflicts instead. Not for a standalone script or algorithm task with no Composer project in scope.
---

# Composer dependency changes

Runs Composer through Mate's CLI and returns the parsed result. Every one of these tools writes: they edit `composer.json` or `composer.lock` and touch `vendor/`.

- `composer-require` (`package`, opt `version`, `dev`, `mode`)
- `composer-remove` (`package`, opt `dev`, `mode`)
- `composer-update` (opt `packages`, `preferDist`, `withDependencies`, `mode`): `packages` takes a comma- or space-separated list.
- `composer-install` (opt `preferDist`, `noDev`, `optimizeAutoloader`, `mode`)
- `composer://config`: the project's `composer.json`, decoded.

These commands accept `--format`: `json` to parse the result, `toon` (when `helgesverre/toon` is installed) for the smallest context footprint.

## Workflow

1. Look before writing: `vendor/bin/mate resources:read composer://config` says whether the package is already required, and under which constraint.
2. Make the change, scoped.
   - `vendor/bin/mate tools:call composer-require --package=symfony/uid --version='^7.3'`
   - Tooling goes to `require-dev`: `--package=phpstan/phpstan --dev`. A test framework or analyser in the runtime requirements is a bug you are introducing.
   - `vendor/bin/mate tools:call composer-update --packages='symfony/console symfony/process'`. Without `packages` it updates everything the constraints allow and rewrites the whole lock file, which is rarely what a task needs and makes the diff unreviewable.
3. Re-run the tests (`phpunit-run`) and the analysis (`phpstan-analyse`) afterwards, and report which versions actually landed. A dependency change changes what the code runs against.

Pass an explicit `version` when the user named one or the project pins consistently; without it Composer takes the newest release that fits, which can be a major the project is not ready for. `withDependencies` defaults to true, so transitive requirements move with the named packages: turn it off when only one lock entry should change.

## Reading

- `status` is `SUCCESS` or `FAILED`, next to the `command` that ran.
- `packages` lists what moved, each `{name, version, action, from}` where `action` is `installed`, `updated`, `downgraded`, or `removed`; `package_count` is the total.
- A `downgraded` entry deserves a second look. It usually means a newly added constraint forced an existing package backwards, a compatibility decision hiding inside a routine install.
- `errors` and `warnings` carry Composer's own output lines when the run failed.
- `mode`: `summary` for counts, `default` for the moved packages, `detailed` for full package records and metadata. A plain install only needs `summary`.

## Failure paths

- The run fails on a version constraint: do not retry with a looser constraint to force it through. Find the blocker with `composer-explain` first (composer dependency conflicts).
- Runs stop at 300 seconds. A wide update with a cold cache can hit that; narrow it to the packages you need.
- A successful `require` but the class is still missing: the autoloader was rebuilt by the run, so this is the wrong package name or an optional suggestion, not a stale autoloader.

## Rules

- Confirm before any dependency change the user did not ask for.
- Never pass `noDev` in a development checkout; it strips dev dependencies out of `vendor/` and the next test run fails for unrelated reasons. It and `optimizeAutoloader` belong to production builds.
- Report which packages moved and to which versions, not the whole payload.
