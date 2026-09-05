import { expect, test } from 'vitest';

import {
  boolishPrefixRule,
  MESSAGE_ID_MISSING_PREFIX,
  MESSAGE_ID_UNEXPECTED_PREFIX,
} from '../../../src/js/eslint/configs/builtin/boolish-prefix';

import {
  FLAG_PREFIXES,
  PREDICATE_PREFIXES,
  RESERVED_METHOD_PREFIXES,
  RESERVED_VALUE_PREFIXES,
} from '../../../src/js/eslint/utils/boolish-prefixes';

import { extractPhpListConstant, readPhpRuleSource } from '../utils/php-rule';

import {

  createRuleCaseBuilders,
  runJsRuleTests,
  runTypeAwareRuleTests,
  TYPE_AWARE_FIXTURE_FILE,
} from '../utils/rule-tester';

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders({
  filename: TYPE_AWARE_FIXTURE_FILE,
});

const {
  buildInvalidCase: buildInvalidLiteralCase,
  buildValidCase: buildValidLiteralCase,
} = createRuleCaseBuilders();

const extractPhpPrefixes = (constantName: string): string[] => extractPhpListConstant(
  readPhpRuleSource('BoolishPrefixRule.php'),
  constantName,
);

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
  runTypeAwareRuleTests(boolishPrefixRule, {
    valid: [
      buildValidCase('boolean method with auxiliary prefix', 'class Box { public isValid(): boolean { return true; } }\n'),
      buildValidCase('boolean method with capability prefix', 'class Box { public supportsHttps(): boolean { return true; } }\n'),
      buildValidCase('boolean method with relational prefix', 'class Box { public containsKey(): boolean { return true; } }\n'),
      buildValidCase('boolean method with directive prefix', 'class Box { public doProcess(): boolean { return true; } }\n'),
      buildValidCase('boolean converter methods', 'class Box { public asBoolean(): boolean { return true; }\npublic toBool(): boolean { return true; } }\n'),
      buildValidCase('boolean property with relational prefix', 'class Box { public allowsNull: boolean = false; }\n'),
      buildValidCase('boolean parameter with representation flag', 'class Box { public toText(asUpperCase: boolean): string { return String(asUpperCase); } }\n'),
      buildValidCase('boolean promise return', 'class Box { public async isReady(): Promise<boolean> { return true; } }\n'),
      buildValidCase('boolean getter', 'class Box { public get isActive(): boolean { return true; } }\n'),
      buildValidCase('boolean variable', 'const isReady: boolean = true;\n'),
      buildValidCase('boolean function', 'function isEnabled(): boolean { return true; }\n'),
      buildValidCase('boolean interface method', 'interface Box { isValid(): boolean }\n'),
      buildValidCase('nullable boolean property', 'class Box { public isOptional: boolean | undefined = undefined; }\n'),
      buildValidCase('type predicate return', 'function isText(value: unknown): value is string { return typeof value === \'string\'; }\n'),
    ],
    invalid: [
      buildInvalidCase('boolean method without prefix', 'class Box { public validate(): boolean { return true; } }\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean method with non-boolish converter target', 'class Box { public asArray(): boolean { return true; } }\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean property without prefix', 'class Box { public active: boolean = false; }\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean parameter without prefix', 'function run(enabled: boolean): void {}\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean promoted property without prefix', 'class Box { public constructor(public ready: boolean) {} }\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean variable without prefix', 'const ready: boolean = true;\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean function without prefix', 'function checkEnabled(): boolean { return true; }\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean interface method without prefix', 'interface Box { compute(): boolean }\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean promise return without prefix', 'class Box { public async check(): Promise<boolean> { return true; } }\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean getter without prefix', 'class Box { public get active(): boolean { return true; } }\n', [MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('boolean closure parameter without prefix', 'const check = (force: boolean): boolean => force;\n', [MESSAGE_ID_MISSING_PREFIX, MESSAGE_ID_MISSING_PREFIX]),
      buildInvalidCase('coincidental substrings are not prefixes', 'function island(assigned: boolean): boolean { return assigned; }\n', [MESSAGE_ID_MISSING_PREFIX, MESSAGE_ID_MISSING_PREFIX]),
    ],
  });
});

test('boolishPrefixRule inverse direction', () => {
  runTypeAwareRuleTests(boolishPrefixRule, {
    valid: [
      buildValidCase('collider on a value-holder', 'class Box { public matches: string[] = [];\npublic startsAt: number = 0; }\n'),
      buildValidCase('collider getter', 'class Box { public get matches(): string[] { return []; } }\n'),
      buildValidCase('directive command method', 'class Box { public doRun(): void {} }\n'),
      buildValidCase('converter naming another target', 'class Box { public asArray(): string[] { return []; } }\n'),
      buildValidCase('plain accessor', 'class Box { public getStatus(): string { return \'ok\'; } }\n'),
      buildValidCase('coincidental substring', 'const island: string = \'x\';\n'),
      buildValidCase('ambiguous union stays unchecked', 'class Box { public isAmbiguous: boolean | number = 0; }\n'),
      buildValidCase('generic return stays unchecked', 'function isWrapped<TValue>(value: TValue): TValue { return value; }\n'),
      buildValidCase('any stays unchecked', 'const isLoose: any = 1;\n'),
      buildValidCase('setter is judged on its getter', 'class Box { public set isActive(value: string) {} }\n'),
      buildValidCase('external override is skipped', 'class Numbers extends Array<number> { public some(): boolean { return false; } }\n'),
    ],
    invalid: [
      buildInvalidCase('non-boolean method with reserved prefix', 'class Box { public hasName(): string { return \'x\'; } }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean method with capability prefix', 'class Box { public requiresList(): string[] { return []; } }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean method with relational prefix', 'class Box { public allowsAccess(): string[] { return []; } }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean method with collider prefix', 'class Box { public startsWith(): string[] { return []; } }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean converter method', 'class Box { public asBoolean(): string { return \'x\'; } }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean property with reserved prefix', 'class Box { public hasCount: number = 0; }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean promoted property with reserved prefix', 'class Box { public constructor(public isName: string) {} }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean parameter with reserved prefix', 'function run(shouldLabel: string): void {}\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean variable with reserved prefix', 'const isLabel: string = \'draft\';\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean interface property with reserved prefix', 'interface Box { isMixed: number }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('non-boolean enum member with reserved prefix', 'enum Box { IS_LABEL = \'draft\' }\n', [MESSAGE_ID_UNEXPECTED_PREFIX]),
      buildInvalidCase('project-owned override is checked', 'class Base { public verify(): boolean { return true; } }\nclass Child extends Base { public verify(): boolean { return false; } }\n', [MESSAGE_ID_MISSING_PREFIX, MESSAGE_ID_MISSING_PREFIX]),
    ],
  });
});

test('boolishPrefixRule falls back to literal evidence without type information', () => {
  runJsRuleTests(boolishPrefixRule, {
    valid: [
      buildValidLiteralCase(
        'boolean variable with prefix',
        'const isReady = true;\n',
      ),
      buildValidLiteralCase(
        'non-boolean variable without prefix',
        'const label = \'draft\';\n',
      ),
      buildValidLiteralCase(
        'boolean function with prefix',
        'function isEnabled() { return true; }\n',
      ),
      buildValidLiteralCase(
        'directive command function',
        'function doRun() { return null; }\n',
      ),
      buildValidLiteralCase(
        'comparison result with prefix',
        'const isEqual = 1 === 2;\n',
      ),
      buildValidLiteralCase(
        'indirect value stays unchecked',
        'const ready = compute();\n',
      ),
      buildValidLiteralCase(
        'coincidental substring',
        'const island = \'x\';\n',
      ),
      buildValidLiteralCase(
        'arrow predicate with expression body',
        'const isReady = () => true;\n',
      ),
      buildValidLiteralCase(
        'arrow predicate with block body',
        'const isReady = () => { return 1 === 2; };\n',
      ),
      buildValidLiteralCase(
        'arrow command returning a non-boolean',
        'const doLoad = () => [];\n',
      ),
      buildValidLiteralCase(
        'mutable boolean binding with prefix',
        'let isDirty = false;\n',
      ),
      buildValidLiteralCase(
        'object literal keys are left alone',
        'const config = { active: true };\n',
      ),
    ],
    invalid: [
      buildInvalidLiteralCase(
        'boolean variable without prefix',
        'const ready = true;\n',
        [MESSAGE_ID_MISSING_PREFIX],
      ),
      buildInvalidLiteralCase(
        'non-boolean variable with reserved prefix',
        'const isLabel = \'draft\';\n',
        [MESSAGE_ID_UNEXPECTED_PREFIX],
      ),
      buildInvalidLiteralCase(
        'boolean function without prefix',
        'function checkEnabled() { return true; }\n',
        [MESSAGE_ID_MISSING_PREFIX],
      ),
      buildInvalidLiteralCase(
        'non-boolean function with reserved prefix',
        'function hasName() { return \'x\'; }\n',
        [MESSAGE_ID_UNEXPECTED_PREFIX],
      ),
      buildInvalidLiteralCase(
        'boolean class property without prefix',
        'class Box { active = false; }\n',
        [MESSAGE_ID_MISSING_PREFIX],
      ),
      buildInvalidLiteralCase(
        'arrow predicate without prefix',
        'const check = () => true;\n',
        [MESSAGE_ID_MISSING_PREFIX],
      ),
      buildInvalidLiteralCase(
        'arrow returning a non-boolean with reserved prefix',
        'const hasName = () => \'x\';\n',
        [MESSAGE_ID_UNEXPECTED_PREFIX],
      ),
      buildInvalidLiteralCase(
        'mutable boolean binding without prefix',
        'let dirty = false;\n',
        [MESSAGE_ID_MISSING_PREFIX],
      ),
      buildInvalidLiteralCase(
        'boolean parameter default without prefix',
        'function run(forced = false) {}\n',
        [MESSAGE_ID_MISSING_PREFIX],
      ),
    ],
  });
});
