---
name: mate-phpunit-test-run
description: Run PHPUnit through Mate and read the result, for the whole suite, one file, one class, or a single method. Use whenever tests should be executed, a failing test has to be narrowed down, or the test covering a class has to be found, in a project that already has a PHPUnit configuration or test suite. Not for static analysis (phpstan static analysis), refactoring (rector refactoring), or a standalone script/algorithm task with no test suite to run.
---

# PHPUnit test runs

Runs PHPUnit through Mate's CLI and returns the parsed JUnit result instead of PHPUnit's own output. Three tools:

- `phpunit-run` (opt `file`, `class`, `method`, `filter`, `configuration`, `stopOnFailure`, `mode`): the suite or any subset of it. `method` requires `class`; both are compiled into an anchored `--filter` for you. `configuration` defaults to `phpunit.xml`, `phpunit.xml.dist`, or `phpunit.dist.xml` in the project root.
- `phpunit-list-tests` (opt `directory`): discovered tests as `{file, class, method}`, scanning the `<directory>` entries of the detected configuration, or `tests` when there is none.
- `phpunit-run-detail` (req `id`; opt `group`, `test`, `raw`): the full messages behind a grouped `phpunit-run` result, read back from the cached run rather than by running the suite again.

These commands accept `--format`: `json` to parse the result, `toon` (when `helgesverre/toon` is installed) for the smallest context footprint.

## Workflow

1. Open with the default mode, not `--mode=summary`. The default already carries the failure groups and a worked example for each of the first few, so one call usually tells you both that the suite is red and why. `summary` answers only the first of those, and finding out why then costs a second call.
   - What you just changed: `vendor/bin/mate tools:call phpunit-run --file=tests/Service/InvoiceTest.php`
   - One known failure: `--class='App\Tests\Service\InvoiceTest' --method=testTotalIsRounded --mode=detailed`
   - Re-checking a suite you have already read: `--mode=summary` is right here, where counts are all you need.
2. When you only know the class under test, find the test first: `vendor/bin/mate tools:call phpunit-list-tests --directory=tests/Service`, then run the class it names.
3. When a group needs more than its summary, read it with `phpunit-run-detail --id=<run> --group=g1` rather than re-running the suite.
4. After a fix, re-run the narrow scope, then the full suite once.

`filter` takes a raw PHPUnit regex for what `class` and `method` cannot express. Combined with them it is ORed, so it widens the run rather than narrowing it. `stopOnFailure` hides everything after the first failure: useful to find one cause in a broadly broken suite, misleading while checking whether a fix is complete.

## Reading

- `status` is `OK` or `FAILED`; `summary` carries `tests`, `passed`, `failed`, `errors`, `warnings`, `skipped`, `time`.
- `tests: 0` with `status: OK` is not a green run. Nothing matched the scope you passed: a misspelled class, a method that does not exist, a filter matching nothing. Check the count before believing the status.
- `groups` collapse failures that share a cause. One broken method fails every test that touches it, so twelve failing tests are usually one problem: `count` is how many tests a group accounts for, `example` names one of them. Fix causes, not entries.
- A group's `type` separates assertion failures from exceptions. An exception usually means the test never really ran, which makes it the more serious of the two.
- `run` is the id of the cached run and `next` spells out the call that reads it. Reach for `phpunit-run-detail --id=<run> --group=g1` instead of re-running the suite in a more verbose mode: the messages are already stored, and running again costs a second suite execution.
- `mode` decides the detail. `summary`: counts only, for confirming a result you have already diagnosed. `default`: the groups, plus a worked example of the actual failure for the largest few, which is normally enough to start fixing. `detailed`: adds one worked example per group with the fully-qualified class and full path, which is what you need before opening a file. A group lists at most five member tests; `phpunit-run-detail` has the rest.
- Messages come back with unchanged diff context and vendor stack frames removed, and say so where that happened. Pass `raw` to `phpunit-run-detail` for the untouched text.
- `phpunit-list-tests` is an index, not the authority on what runs. It matches `*Test.php`, a class declared with `extends`, and `public function test…` methods, so tests using the `#[Test]` attribute or inherited from a base class are absent yet run fine. Never conclude from it that a test does not exist.

## Failure paths

- "Test file not found" or "class parameter is required": the tool rejected the call before PHPUnit started. Fix the call, do not retry it unchanged.
- "Unknown run id": the run has been evicted, only the last 20 are kept, or the id came from an older session. Run `phpunit-run` again to get a current one.
- A result with `groups` but no `run`: the cache could not be written, so the summaries are all there is. The suite result itself is unaffected.
- Raw PHPUnit text instead of the structured payload: the JUnit XML could not be parsed, so the run crashed (fatal error, bootstrap or configuration failure) rather than reporting test failures. Read the text for the fatal and fix that first.
- No tests and no configuration detected: the config files are looked up in the project root only, so a call from the wrong working directory finds none.
- PHPUnit missing, or reachable only through Docker or DDEV: the extension needs `matesofmate_phpunit.custom_command`. That is project setup, not a reason to shell out to `vendor/bin/phpunit`.
