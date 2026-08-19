import fs from 'node:fs';
import path from 'node:path';

import { expect, test } from 'vitest';

import {
  boolishPrefixRule,
  MESSAGE_ID_MISSING_PREFIX,
  MESSAGE_ID_RESERVED_PREFIX,
} from '../../../src/js/eslint/configs/builtin/boolish-prefix';

import {
  FLAG_PREFIXES,
  PREDICATE_PREFIXES,
  RESERVED_METHOD_PREFIXES,
  RESERVED_VALUE_PREFIXES,
} from '../../../src/js/eslint/utils/boolish-prefixes';

import { jsRuleTester, TYPE_AWARE_FIXTURE_FILE, typeAwareRuleTester } from '../utils/rule-tester';

const RULE_NAME = 'boolish-prefix';
const PHP_RULE_PATH = path.resolve(import.meta.dirname, '../../../src/php/PhpStan/Rule/BoolishPrefixRule.php');

const valid = (name: string, code: string): {
  name: string;
  filename: string;
  code: string;
} => ({
  name,
  filename: TYPE_AWARE_FIXTURE_FILE,
  code,
});

const invalid = (name: string, code: string, messageIds: string[]): {
  name: string;
  filename: string;
  code: string;
  errors: { messageId: string }[];
} => ({
  ...valid(name, code),
  errors: messageIds.map((messageId) => ({ messageId })),
});

const extractPhpPrefixes = (constantName: string): string[] => {
  const source = fs.readFileSync(PHP_RULE_PATH, 'utf-8');
  const pattern = new RegExp(String.raw`const array ${constantName} = \[(?<entries>[^\]]*)\]`, 'v');
  const entries = pattern.exec(source)?.groups?.['entries'] ?? '';

  return [...entries.matchAll(/'(?<prefix>[^']+)'/gv)].map((match) => match.groups?.['prefix'] ?? '');
};

test('boolishPrefixRule stays in sync with the PHP rule', () => {
  const reservedValuePrefixes = [
    ...extractPhpPrefixes('AUXILIARY_PREFIXES'),
    ...extractPhpPrefixes('CAPABILITY_PREFIXES'),
    ...extractPhpPrefixes('RELATIONAL_PREFIXES'),
  ];

  const colliderPrefixes = extractPhpPrefixes('COLLIDER_PREFIXES');
  const directivePrefixes = extractPhpPrefixes('DIRECTIVE_PREFIXES');

  expect(reservedValuePrefixes.length).toBeGreaterThan(0);
  expect(RESERVED_VALUE_PREFIXES).toStrictEqual(reservedValuePrefixes);
  expect(RESERVED_METHOD_PREFIXES).toStrictEqual([...reservedValuePrefixes, ...colliderPrefixes]);
  expect(PREDICATE_PREFIXES).toStrictEqual([...reservedValuePrefixes, ...colliderPrefixes, ...directivePrefixes]);
  expect(FLAG_PREFIXES).toStrictEqual([...reservedValuePrefixes, ...directivePrefixes, 'as']);
});

test('boolishPrefixRule forward direction', () => {
  expect(() => {
    typeAwareRuleTester.run(RULE_NAME, boolishPrefixRule, {
      valid: [
        valid('boolean method with auxiliary prefix', 'class Box { public isValid(): boolean { return true; } }\n'),
        valid('boolean method with capability prefix', 'class Box { public supportsHttps(): boolean { return true; } }\n'),
        valid('boolean method with relational prefix', 'class Box { public containsKey(): boolean { return true; } }\n'),
        valid('boolean method with directive prefix', 'class Box { public doProcess(): boolean { return true; } }\n'),
        valid('boolean converter methods', 'class Box { public asBoolean(): boolean { return true; }\npublic toBool(): boolean { return true; } }\n'),
        valid('boolean property with relational prefix', 'class Box { public allowsNull: boolean = false; }\n'),
        valid('boolean parameter with representation flag', 'class Box { public toText(asUpperCase: boolean): string { return String(asUpperCase); } }\n'),
        valid('boolean promise return', 'class Box { public async isReady(): Promise<boolean> { return true; } }\n'),
        valid('boolean getter', 'class Box { public get isActive(): boolean { return true; } }\n'),
        valid('boolean variable', 'const isReady: boolean = true;\n'),
        valid('boolean function', 'function isEnabled(): boolean { return true; }\n'),
        valid('boolean interface method', 'interface Box { isValid(): boolean }\n'),
        valid('nullable boolean property', 'class Box { public isOptional: boolean | undefined = undefined; }\n'),
        valid('type predicate return', 'function isText(value: unknown): value is string { return typeof value === \'string\'; }\n'),
      ],
      invalid: [
        invalid('boolean method without prefix', 'class Box { public validate(): boolean { return true; } }\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean method with non-boolish converter target', 'class Box { public asArray(): boolean { return true; } }\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean property without prefix', 'class Box { public active: boolean = false; }\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean parameter without prefix', 'function run(enabled: boolean): void {}\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean promoted property without prefix', 'class Box { public constructor(public ready: boolean) {} }\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean variable without prefix', 'const ready: boolean = true;\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean function without prefix', 'function checkEnabled(): boolean { return true; }\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean interface method without prefix', 'interface Box { compute(): boolean }\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean promise return without prefix', 'class Box { public async check(): Promise<boolean> { return true; } }\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean getter without prefix', 'class Box { public get active(): boolean { return true; } }\n', [MESSAGE_ID_MISSING_PREFIX]),
        invalid('boolean closure parameter without prefix', 'const check = (force: boolean): boolean => force;\n', [MESSAGE_ID_MISSING_PREFIX, MESSAGE_ID_MISSING_PREFIX]),
        invalid('coincidental substrings are not prefixes', 'function island(assigned: boolean): boolean { return assigned; }\n', [MESSAGE_ID_MISSING_PREFIX, MESSAGE_ID_MISSING_PREFIX]),
      ],
    });
  }).not.toThrow();
});

test('boolishPrefixRule inverse direction', () => {
  expect(() => {
    typeAwareRuleTester.run(RULE_NAME, boolishPrefixRule, {
      valid: [
        valid('collider on a value-holder', 'class Box { public matches: string[] = [];\npublic startsAt: number = 0; }\n'),
        valid('collider getter', 'class Box { public get matches(): string[] { return []; } }\n'),
        valid('directive command method', 'class Box { public doRun(): void {} }\n'),
        valid('converter naming another target', 'class Box { public asArray(): string[] { return []; } }\n'),
        valid('plain accessor', 'class Box { public getStatus(): string { return \'ok\'; } }\n'),
        valid('coincidental substring', 'const island: string = \'x\';\n'),
        valid('ambiguous union stays unchecked', 'class Box { public isAmbiguous: boolean | number = 0; }\n'),
        valid('generic return stays unchecked', 'function isWrapped<TValue>(value: TValue): TValue { return value; }\n'),
        valid('any stays unchecked', 'const isLoose: any = 1;\n'),
        valid('setter is judged on its getter', 'class Box { public set isActive(value: string) {} }\n'),
        valid('external override is skipped', 'class Numbers extends Array<number> { public some(): boolean { return false; } }\n'),
      ],
      invalid: [
        invalid('non-boolean method with reserved prefix', 'class Box { public hasName(): string { return \'x\'; } }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean method with capability prefix', 'class Box { public requiresList(): string[] { return []; } }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean method with relational prefix', 'class Box { public allowsAccess(): string[] { return []; } }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean method with collider prefix', 'class Box { public startsWith(): string[] { return []; } }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean converter method', 'class Box { public asBoolean(): string { return \'x\'; } }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean property with reserved prefix', 'class Box { public hasCount: number = 0; }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean promoted property with reserved prefix', 'class Box { public constructor(public isName: string) {} }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean parameter with reserved prefix', 'function run(shouldLabel: string): void {}\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean variable with reserved prefix', 'const isLabel: string = \'draft\';\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean interface property with reserved prefix', 'interface Box { isMixed: number }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('non-boolean enum member with reserved prefix', 'enum Box { IS_LABEL = \'draft\' }\n', [MESSAGE_ID_RESERVED_PREFIX]),
        invalid('project-owned override is checked', 'class Base { public verify(): boolean { return true; } }\nclass Child extends Base { public verify(): boolean { return false; } }\n', [MESSAGE_ID_MISSING_PREFIX, MESSAGE_ID_MISSING_PREFIX]),
      ],
    });
  }).not.toThrow();
});

test('boolishPrefixRule falls back to literal evidence without type information', () => {
  expect(() => {
    jsRuleTester.run(RULE_NAME, boolishPrefixRule, {
      valid: [
        {
          name: 'boolean variable with prefix',
          code: 'const isReady = true;\n',
        },
        {
          name: 'non-boolean variable without prefix',
          code: 'const label = \'draft\';\n',
        },
        {
          name: 'boolean function with prefix',
          code: 'function isEnabled() { return true; }\n',
        },
        {
          name: 'directive command function',
          code: 'function doRun() { return null; }\n',
        },
        {
          name: 'comparison result with prefix',
          code: 'const isEqual = 1 === 2;\n',
        },
        {
          name: 'indirect value stays unchecked',
          code: 'const ready = compute();\n',
        },
        {
          name: 'coincidental substring',
          code: 'const island = \'x\';\n',
        },
        {
          name: 'arrow predicate with expression body',
          code: 'const isReady = () => true;\n',
        },
        {
          name: 'arrow predicate with block body',
          code: 'const isReady = () => { return 1 === 2; };\n',
        },
        {
          name: 'arrow command returning a non-boolean',
          code: 'const doLoad = () => [];\n',
        },
        {
          name: 'mutable boolean binding with prefix',
          code: 'let isDirty = false;\n',
        },
        {
          name: 'object literal keys are left alone',
          code: 'const config = { active: true };\n',
        },
      ],
      invalid: [
        {
          name: 'boolean variable without prefix',
          code: 'const ready = true;\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_PREFIX }],
        },
        {
          name: 'non-boolean variable with reserved prefix',
          code: 'const isLabel = \'draft\';\n',
          errors: [{ messageId: MESSAGE_ID_RESERVED_PREFIX }],
        },
        {
          name: 'boolean function without prefix',
          code: 'function checkEnabled() { return true; }\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_PREFIX }],
        },
        {
          name: 'non-boolean function with reserved prefix',
          code: 'function hasName() { return \'x\'; }\n',
          errors: [{ messageId: MESSAGE_ID_RESERVED_PREFIX }],
        },
        {
          name: 'boolean class property without prefix',
          code: 'class Box { active = false; }\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_PREFIX }],
        },
        {
          name: 'arrow predicate without prefix',
          code: 'const check = () => true;\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_PREFIX }],
        },
        {
          name: 'arrow returning a non-boolean with reserved prefix',
          code: 'const hasName = () => \'x\';\n',
          errors: [{ messageId: MESSAGE_ID_RESERVED_PREFIX }],
        },
        {
          name: 'mutable boolean binding without prefix',
          code: 'let dirty = false;\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_PREFIX }],
        },
        {
          name: 'boolean parameter default without prefix',
          code: 'function run(forced = false) {}\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_PREFIX }],
        },
      ],
    });
  }).not.toThrow();
});
