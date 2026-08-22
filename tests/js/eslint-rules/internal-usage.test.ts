import path from 'node:path';

import { expect, test } from 'vitest';

import {
  internalUsageRule,
  MESSAGE_ID_INTERNAL_USAGE,
  MESSAGE_ID_TARGETED_INTERNAL_USAGE,
} from '../../../src/js/eslint/configs/builtin/internal-usage';

import { readPhpRuleSource } from '../utils/php-rule';

import {
  createRuleCaseBuilders,
  createTypeAwareRuleTester,
  runRuleTests,
  runTsRuleTests,
} from '../utils/rule-tester';

const FIXTURE_ROOT = path.resolve(import.meta.dirname, '../fixtures/internal-usage');
const CALLER_INSIDE = path.join(FIXTURE_ROOT, 'src/internal/consumer.ts');
const CALLER_BELOW = path.join(FIXTURE_ROOT, 'src/internal/nested/consumer.ts');
const CALLER_SIBLING = path.join(FIXTURE_ROOT, 'src/public/consumer.ts');
const CALLER_EMAIL = path.join(FIXTURE_ROOT, 'email/consumer.ts');
const CALLER_LOOKALIKE = path.join(FIXTURE_ROOT, 'acmecorp/consumer.ts');
const CALLER_UNSCOPED = path.join(FIXTURE_ROOT, 'plain/src/public/consumer.ts');
const CALLER_UNSCOPED_BELOW = path.join(FIXTURE_ROOT, 'plain/src/internal/nested/consumer.ts');
const FIXTURE_TSCONFIG = path.join(FIXTURE_ROOT, 'tsconfig.json');

const typeAwareRuleTester = createTypeAwareRuleTester(FIXTURE_ROOT, [
  'acmecorp/*.ts',
  'email/*.ts',
  'plain/src/internal/nested/*.ts',
  'plain/src/public/*.ts',
  'src/internal/*.ts',
  'src/internal/nested/*.ts',
  'src/public/*.ts',
]);

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders({ filename: CALLER_SIBLING });

test('internalUsageRule mirrors the PHP option names', () => {
  const source = readPhpRuleSource('InternalUsageRule.php');

  for (const optionName of <const>[
    'allowedInternalTargets',
    'allowedDeclaringNamespaces',
    'allowedCallingNamespaces',
    'allowedSymbols',
  ]) {
    expect(source).toContain(`$${optionName}`);
  }
});

test('internalUsageRule scenarios', () => {
  runRuleTests(typeAwareRuleTester, internalUsageRule, {
    valid: [
      buildValidCase(
        'usage inside the declaring namespace',
        'import { hashPassword } from \'./hasher\';\n\nhashPassword(\'a\');\n',
        { filename: CALLER_INSIDE },
      ),
      buildValidCase(
        'usage below the declaring namespace',
        'import { hashPassword } from \'../hasher\';\n\nhashPassword(\'a\');\n',
        { filename: CALLER_BELOW },
      ),
      buildValidCase(
        'symbol without an internal tag',
        'import { describeHash } from \'../internal/hasher\';\n\ndescribeHash(\'a\');\n',
      ),
      buildValidCase(
        'explicit target reached from that target',
        'import { emailOnlyHelper } from \'../src/internal/scoped\';\n\nemailOnlyHelper(\'a\');\n',
        { filename: CALLER_EMAIL },
      ),
      buildValidCase(
        'bare vendor target reached from a sibling package',
        'import { organizationHelper } from \'../src/internal/vendor\';\n\norganizationHelper(\'a\');\n',
        { filename: CALLER_EMAIL },
      ),
      buildValidCase(
        'separators trimmed from the target',
        'import { slashedHelper } from \'../src/internal/slashed\';\n\nslashedHelper(\'a\');\n',
        { filename: CALLER_EMAIL },
      ),
      buildValidCase(
        'import specifier alone is not a usage',
        'import { hashPassword } from \'../internal/hasher\';\n\nexport const value = true;\n',
      ),
      buildValidCase(
        'local declaration shadowing an internal name',
        'const hashPassword = (value: string): string => value;\n\nhashPassword(\'a\');\n',
      ),
      buildValidCase(
        'untagged member of an untagged interface',
        'import type { HashOptions } from \'../internal/hasher\';\n\nexport const read = (options: HashOptions): string => options.label;\n',
      ),
      buildValidCase(
        'file-level internal reached from below',
        'import { fileLevelHelper } from \'../file-level\';\n\nfileLevelHelper(\'a\');\n',
        { filename: CALLER_BELOW },
      ),
      buildValidCase(
        'described tag reached from inside the declaring namespace',
        'import { describedHelper } from \'./described\';\n\ndescribedHelper(\'a\');\n',
        { filename: CALLER_INSIDE },
      ),
      buildValidCase(
        'untagged symbol in a file-level @api module',
        'import { apiFileOpenHelper } from \'../entry/api-file\';\n\napiFileOpenHelper(\'a\');\n',
      ),
      buildValidCase(
        '@api symbol escapes a file-level @internal module',
        'import { internalFileApiHelper } from \'../entry/internal-file\';\n\ninternalFileApiHelper(\'a\');\n',
      ),
      buildValidCase(
        'a module may use its own internal symbols',
        '/**\n * @internal @acme/email\n */\nexport const helper = (value: string): string => value;\n\nhelper(\'a\');\n',
      ),
      buildValidCase(
        'unscoped package target covers the whole package',
        'import { packageWideHelper } from \'../internal/tokens\';\n\npackageWideHelper(\'a\');\n',
        { filename: CALLER_UNSCOPED },
      ),
      buildValidCase(
        'unscoped package usage below the declaring namespace',
        'import { plainHelper } from \'../tokens\';\n\nplainHelper(\'a\');\n',
        { filename: CALLER_UNSCOPED_BELOW },
      ),
    ],
    invalid: [
      {
        name: 'call of an internal function from a sibling namespace',
        code: 'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\n',
        filename: CALLER_SIBLING,
        errors: [{
          message: '`@acme/user/internal/hasher#hashPassword` is internal and must not be used from `@acme/user/public`.',
        }],
      },
      buildInvalidCase(
        'construction of an internal class',
        'import { PasswordHasher } from \'../internal/hasher\';\n\nnew PasswordHasher();\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'internal method reached through an instance',
        'import { PasswordHasher } from \'../internal/hasher\';\n\nnew PasswordHasher().rehash(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE, MESSAGE_ID_TARGETED_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'internal property of an untagged interface',
        'import type { HashOptions } from \'../internal/hasher\';\n\nexport const read = (options: HashOptions): string => options.secret;\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'internal property on a callback parameter',
        'import type { HashOptions } from \'../internal/hasher\';\n\ndeclare const handle: (callback: (options: HashOptions) => string) => void;\n\nhandle((options) => options.secret);\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'internal type in a type position',
        'import type { HashToken } from \'../internal/hasher\';\n\nexport const read = (token: HashToken): string => token;\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'usage from another package',
        'import { hashPassword } from \'../src/internal/hasher\';\n\nhashPassword(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
        { filename: CALLER_EMAIL },
      ),
      {
        name: 'explicit target excludes the declaring namespace',
        code: 'import { emailOnlyHelper } from \'./scoped\';\n\nemailOnlyHelper(\'a\');\n',
        filename: CALLER_INSIDE,
        errors: [{
          message: '`@acme/user/internal/scoped#emailOnlyHelper` is internal to `@acme/email` and must not be used from `@acme/user/internal`.',
        }],
      },
      buildInvalidCase(
        'bare vendor target from a lookalike scope',
        'import { organizationHelper } from \'../src/internal/vendor\';\n\norganizationHelper(\'a\');\n',
        [MESSAGE_ID_TARGETED_INTERNAL_USAGE],
        { filename: CALLER_LOOKALIKE },
      ),
      buildInvalidCase(
        'described tag from a sibling namespace',
        'import { describedHelper } from \'../internal/described\';\n\ndescribedHelper(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'file-level internal from a sibling namespace',
        'import { fileLevelHelper } from \'../internal/file-level\';\n\nfileLevelHelper(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        're-export of an internal symbol',
        'export { hashPassword } from \'../internal/hasher\';\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'export all of an internal module',
        'export * from \'../internal/file-level\';\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'dynamic import of an internal module',
        'export const load = async (): Promise<unknown> => import(\'../internal/file-level\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'barrel re-export resolves to the origin',
        'import { hashPassword } from \'../internal/barrel\';\n\nhashPassword(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'member of a namespace import',
        'import * as hasher from \'../internal/hasher\';\n\nhasher.hashPassword(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      {
        name: 'a declared function carries parentheses in its symbol name',
        code: 'import { hashLegacy } from \'../internal/hasher\';\n\nhashLegacy(\'a\');\n',
        filename: CALLER_SIBLING,
        errors: [{
          message: '`@acme/user/internal/hasher#hashLegacy()` is internal and must not be used from `@acme/user/public`.',
        }],
      },
      {
        name: 'unscoped package name in the symbol identity',
        code: 'import { plainHelper } from \'../internal/tokens\';\n\nplainHelper(\'a\');\n',
        filename: CALLER_UNSCOPED,
        errors: [{
          message: '`acme-app/internal/tokens#plainHelper` is internal and must not be used from `acme-app/public`.',
        }],
      },
      {
        name: 'unscoped package target excludes another package',
        code: 'import { packageWideHelper } from \'../../plain/src/internal/tokens\';\n\npackageWideHelper(\'a\');\n',
        filename: CALLER_SIBLING,
        errors: [{
          message: '`acme-app/internal/tokens#packageWideHelper` is internal to `acme-app` and must not be used from `@acme/user/public`.',
        }],
      },
      buildInvalidCase(
        '@internal symbol inside a file-level @api module',
        'import { apiFileInternalHelper } from \'../entry/api-file\';\n\napiFileInternalHelper(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'untagged symbol in a file-level @internal module',
        'import { internalFileOpenHelper } from \'../entry/internal-file\';\n\ninternalFileOpenHelper(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'every usage is reported',
        'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\nhashPassword(\'b\');\n',
        [MESSAGE_ID_INTERNAL_USAGE, MESSAGE_ID_INTERNAL_USAGE],
      ),
    ],
  });
});

test('internalUsageRule option scenarios', () => {
  runRuleTests(typeAwareRuleTester, internalUsageRule, {
    valid: [
      buildValidCase(
        'allowedCallingNamespaces exempts the caller',
        'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\n',
        { options: [{ allowedCallingNamespaces: ['@acme/user/public'] }] },
      ),
      buildValidCase(
        'allowedCallingNamespaces ignores a leading separator',
        'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\n',
        { options: [{ allowedCallingNamespaces: ['/@acme/user/public'] }] },
      ),
      buildValidCase(
        'allowedCallingNamespaces matches a parent prefix',
        'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\n',
        { options: [{ allowedCallingNamespaces: ['@acme/user'] }] },
      ),
      buildValidCase(
        'allowedDeclaringNamespaces accepts a regular expression',
        'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\n',
        { options: [{ allowedDeclaringNamespaces: [/^@acme\/user\/internal/v] }] },
      ),
      buildValidCase(
        'allowedInternalTargets exempts a shared target',
        'import { emailOnlyHelper } from \'./scoped\';\n\nemailOnlyHelper(\'a\');\n',
        {
          filename: CALLER_INSIDE,
          options: [{ allowedInternalTargets: [/^@acme\/email$/v] }],
        },
      ),
      buildValidCase(
        'allowedSymbols exempts one export',
        'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\n',
        { options: [{ allowedSymbols: ['@acme/user/internal/hasher#hashPassword'] }] },
      ),
      buildValidCase(
        'allowedSymbols exempts a module and its members',
        'import { PasswordHasher } from \'../internal/hasher\';\n\nnew PasswordHasher().rehash(\'a\');\n',
        { options: [{ allowedSymbols: ['@acme/user/internal/hasher'] }] },
      ),
    ],
    invalid: [
      buildInvalidCase(
        'a partial segment is not a prefix match',
        'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
        { options: [{ allowedCallingNamespaces: ['@acme/user/pub'] }] },
      ),
    ],
  });
});

test('internalUsageRule resolves namespaces through tsconfig path aliases', () => {
  runRuleTests(typeAwareRuleTester, internalUsageRule, {
    valid: [
      buildValidCase(
        'alias target reached from below the aliased namespace',
        'import { aliasedHelper } from \'../aliased\';\n\naliasedHelper(\'a\');\n',
        {
          filename: CALLER_BELOW,
          options: [{ tsConfigPath: FIXTURE_TSCONFIG }],
        },
      ),
    ],
    invalid: [
      buildInvalidCase(
        'alias target stays unresolved without a tsconfig',
        'import { aliasedHelper } from \'../aliased\';\n\naliasedHelper(\'a\');\n',
        [MESSAGE_ID_TARGETED_INTERNAL_USAGE],
        { filename: CALLER_BELOW },
      ),
      {
        name: 'alias target excludes a sibling namespace',
        code: 'import { aliasedHelper } from \'../internal/aliased\';\n\naliasedHelper(\'a\');\n',
        filename: CALLER_SIBLING,
        options: [{ tsConfigPath: FIXTURE_TSCONFIG }],
        errors: [{
          message: '`@acme/user/internal/aliased#aliasedHelper` is internal to `@user/internal` and must not be used from `@acme/user/public`.',
        }],
      },
    ],
  });
});

test('internalUsageRule rejects an option entry that is not a namespace prefix', () => {
  expect(() => {
    runRuleTests(typeAwareRuleTester, internalUsageRule, {
      valid: [
        buildValidCase(
          'entry that is neither a prefix nor a pattern',
          'export const value = true;\n',
          { options: [{ allowedCallingNamespaces: ['#^@acme'] }] },
        ),
      ],
      invalid: [],
    });
  }).toThrow('Entry "#^@acme" for option "allowedCallingNamespaces" is neither a namespace prefix nor a regular expression.');
});

test('internalUsageRule rejects a separator-only option entry', () => {
  expect(() => {
    runRuleTests(typeAwareRuleTester, internalUsageRule, {
      valid: [
        buildValidCase(
          'separator only',
          'export const value = true;\n',
          { options: [{ allowedDeclaringNamespaces: ['/'] }] },
        ),
      ],
      invalid: [],
    });
  }).toThrow('Entry "/" for option "allowedDeclaringNamespaces" is neither a namespace prefix nor a regular expression.');
});

test('internalUsageRule rejects an empty option entry', () => {
  expect(() => {
    runRuleTests(typeAwareRuleTester, internalUsageRule, {
      valid: [
        buildValidCase(
          'empty entry',
          'export const value = true;\n',
          { options: [{ allowedSymbols: [''] }] },
        ),
      ],
      invalid: [],
    });
  }).toThrow('Entry "" for option "allowedSymbols" is neither a namespace prefix nor a regular expression.');
});

test('internalUsageRule degrades to lexical resolution without type information', () => {
  runTsRuleTests(internalUsageRule, {
    valid: [
      buildValidCase(
        'usage inside the declaring namespace',
        'import { hashPassword } from \'./hasher\';\n\nhashPassword(\'a\');\n',
        { filename: CALLER_INSIDE },
      ),
      buildValidCase(
        'member access is not resolvable without type information',
        'import type { HashOptions } from \'../internal/hasher\';\n\nexport const read = (options: HashOptions): string => options.secret;\n',
      ),
      buildValidCase(
        'symbol without an internal tag',
        'import { describeHash } from \'../internal/hasher\';\n\ndescribeHash(\'a\');\n',
      ),
      buildValidCase(
        '@api symbol escapes a file-level @internal module',
        'import { internalFileApiHelper } from \'../entry/internal-file\';\n\ninternalFileApiHelper(\'a\');\n',
      ),
    ],
    invalid: [
      buildInvalidCase(
        'usage of an internal import',
        'import { hashPassword } from \'../internal/hasher\';\n\nhashPassword(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      {
        name: 'a declared function carries parentheses without type information',
        code: 'import { hashLegacy } from \'../internal/hasher\';\n\nhashLegacy(\'a\');\n',
        filename: CALLER_SIBLING,
        errors: [{
          message: '`@acme/user/internal/hasher#hashLegacy()` is internal and must not be used from `@acme/user/public`.',
        }],
      },
      buildInvalidCase(
        're-export of an internal symbol',
        'export { hashPassword } from \'../internal/hasher\';\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'export all of a file-level internal module',
        'export * from \'../internal/file-level\';\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
      buildInvalidCase(
        'usage of a file-level internal import',
        'import { fileLevelHelper } from \'../internal/file-level\';\n\nfileLevelHelper(\'a\');\n',
        [MESSAGE_ID_INTERNAL_USAGE],
      ),
    ],
  });
});
