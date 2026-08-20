import { expect, test } from 'vitest';

import {
  INTERFACE_SUFFIX,
  interfaceSuffixRule,
  MESSAGE_ID_MISSING_SUFFIX,
} from '../../../src/js/eslint/configs/builtin/interface-suffix';

import { extractPhpStringConstants, readPhpRuleSource } from '../utils/php-rule';
import { createRuleCaseBuilders, runTsRuleTests } from '../utils/rule-tester';

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders();

test('interfaceSuffixRule stays in sync with the PHP rule', () => {
  const source = readPhpRuleSource('InterfaceSuffixRule.php');
  const [suffix] = extractPhpStringConstants(source, 'INTERFACE_');

  expect(suffix).toBe(INTERFACE_SUFFIX);
});

test('interfaceSuffixRule scenarios', () => {
  runTsRuleTests(interfaceSuffixRule, {
    valid: [
      buildValidCase(
        'class ending with the matching prefix',
        'class InMemoryUserRepository implements UserRepositoryInterface {}\n',
      ),
      buildValidCase(
        'class named exactly like the prefix',
        'class UserRepository implements UserRepositoryInterface {}\n',
      ),
      buildValidCase(
        'two suffixed interfaces make the prefix ambiguous',
        'class CachedUserStore implements UserRepositoryInterface, CacheableInterface {}\n',
      ),
      buildValidCase(
        'no suffixed interface at all',
        'class CachedUserStore implements Stringable {}\n',
      ),
      buildValidCase(
        'class without an implements clause',
        'class CachedUserStore {}\n',
      ),
      buildValidCase(
        'anonymous class expression',
        'const store = class implements UserRepositoryInterface {};\n',
      ),
      buildValidCase(
        'interface without a prefix',
        'class CachedUserStore implements Interface {}\n',
      ),
      buildValidCase(
        'qualified interface name ending with the matching prefix',
        'class InMemoryUserRepository implements Acme.UserRepositoryInterface {}\n',
      ),
      buildValidCase(
        'non-suffixed parents are ignored for the count',
        'class InMemoryUserRepository extends Base implements Stringable, UserRepositoryInterface {}\n',
      ),
    ],
    invalid: [
      buildInvalidCase(
        'class not ending with the prefix',
        'class InMemoryUsers implements UserRepositoryInterface {}\n',
        [MESSAGE_ID_MISSING_SUFFIX],
      ),
      buildInvalidCase(
        'named class expression not ending with the prefix',
        'const store = class InMemoryUsers implements UserRepositoryInterface {};\n',
        [MESSAGE_ID_MISSING_SUFFIX],
      ),
      buildInvalidCase(
        'qualified interface name not ending with the prefix',
        'class InMemoryUsers implements Acme.UserRepositoryInterface {}\n',
        [MESSAGE_ID_MISSING_SUFFIX],
      ),
      buildInvalidCase(
        'prefix matched only partially',
        'class UserRepositoryFactory implements UserRepositoryInterface {}\n',
        [MESSAGE_ID_MISSING_SUFFIX],
      ),
    ],
  });
});
