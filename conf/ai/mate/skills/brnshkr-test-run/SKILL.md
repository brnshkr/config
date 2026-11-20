---
name: brnshkr-test-run
description: Run the JS tests, or update PHP or JS snapshots, through the @brnshkr/config Makefile via Mate, narrowed to a file or name. For a plain PHP test run prefer `mate-phpunit-test-run`. Not for static analysis or code style (`mate-brnshkr-code-quality`).
---

# Test runs

Runs the PHP suite through PHPUnit or Pest, or the JS suite through Vitest, via the project's make targets:

- `brnshkr-test-run` (opt `suite` php or js, `filter`, `doUpdateSnapshots`, `arguments`):
  returns `exitCode`, plus `failures`, `changedSnapshots` and `unchangedDirtySnapshots` when they have entries.

## Workflow

1. Run what covers your change: `--suite=php --filter=AcmeUserTest`,
   or one file with `--arguments='./tests/php/UserTest.php'`.
2. Fix the cause and re-run the same scope.
3. After an intended output change, run the same scope with `--doUpdateSnapshots`,
   then read every file in `changedSnapshots`.
4. Once, before handing work back, run the suite without `filter`.

## Reading

- `exitCode` `0` means every selected test passed. A run that finds no test fails.
- `failures` lists each failed test with `test`, `file`, `line` and `message`. A failing run without failures,
  e.g. a crash, adds `output`, the runner's report, at most its last 20000 characters.
- `changedSnapshots` lists the snapshot files the run rewrote. An update is not a review: read each diff.
- `unchangedDirtySnapshots` lists snapshot files that were already modified before the run and that it left alone.
- A PHP `filter` is the runner's `--filter`. A JS `filter` matches test file names.

## Failure paths

- `brnshkr-test-run` rejects an unknown `suite` and lists the valid ones.
