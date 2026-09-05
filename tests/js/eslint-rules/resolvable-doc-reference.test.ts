import { test } from 'vitest';

import {
  MESSAGE_ID_MISSING_REFERENCE,
  resolvableDocReferenceRule,
} from '../../../src/js/eslint/configs/builtin/resolvable-doc-reference';

import {
  createRuleCaseBuilders,
  runTsRuleTests,
  runTypeAwareRuleTests,
  TYPE_AWARE_FIXTURE_FILE,
} from '../utils/rule-tester';

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders();

const {
  buildInvalidCase: buildInvalidTypeAwareCase,
  buildValidCase: buildValidTypeAwareCase,
} = createRuleCaseBuilders({ filename: TYPE_AWARE_FIXTURE_FILE });

test('resolvableDocReferenceRule scenarios', () => {
  runTsRuleTests(resolvableDocReferenceRule, {
    valid: [
      buildValidCase(
        'target declared in the same file',
        'class Mailer {}\n\n/**\n * {@link Mailer}\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'target reached through an import',
        'import { Mailer } from \'./mailer\';\n\n/**\n * {@link Mailer}\n */\nexport const send = (): Mailer => new Mailer();\n',
      ),
      buildValidCase(
        'target reached through a type-only import',
        'import type { Mailer } from \'./mailer\';\n\n/**\n * {@see Mailer}\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'member path on a declared target',
        'class Mailer {}\n\n/**\n * {@link Mailer.sendNow}\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'interface declaration is in scope',
        'interface Mailer {}\n\n/**\n * {@link Mailer}\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'block tag with a url',
        '/**\n * @see https://example.com/reference\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'lowercase module path is not a local reference',
        '/**\n * {@link eslint.Linter}\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'import type expression is not a local reference',
        '/**\n * {@link import(\'eslint\').Linter}\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'prose is left alone',
        '/**\n * {@link the registration flow}\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'path is left alone',
        '/**\n * @see Docs/reference.md\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'schemeless host is left alone',
        '/**\n * {@see www.example.com}\n */\nexport const send = (): void => {};\n',
      ),
      buildValidCase(
        'line comment is ignored',
        '// {@link Mailer}\nexport const send = (): void => {};\n',
      ),
    ],
    invalid: [
      buildInvalidCase(
        'inline link to an undeclared target',
        '/**\n * {@link Mailer}\n */\nexport const send = (): void => {};\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidCase(
        'inline see to an undeclared target',
        '/**\n * {@see Mailer}\n */\nexport const send = (): void => {};\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidCase(
        'block tag to an undeclared target',
        '/**\n * @see Mailer\n */\nexport const send = (): void => {};\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidCase(
        'linkcode to an undeclared target',
        '/**\n * {@linkcode Mailer}\n */\nexport const send = (): void => {};\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidCase(
        'linkplain to an undeclared target',
        '/**\n * {@linkplain Mailer}\n */\nexport const send = (): void => {};\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidCase(
        'member path on an undeclared target',
        '/**\n * {@link Mailer.sendNow}\n */\nexport const send = (): void => {};\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidCase(
        'description after the target is ignored',
        '/**\n * {@link Mailer the transport this delegates to}\n */\nexport const send = (): void => {};\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidCase(
        'two targets on one line',
        '/**\n * {@link Mailer} and {@link Transport}\n */\nexport const send = (): void => {};\n',
        [MESSAGE_ID_MISSING_REFERENCE, MESSAGE_ID_MISSING_REFERENCE],
      ),
    ],
  });
});

test('resolvableDocReferenceRule resolves members when type information is available', () => {
  runTypeAwareRuleTests(resolvableDocReferenceRule, {
    valid: [
      buildValidTypeAwareCase(
        'member of an imported namespace',
        'import type eslint from \'eslint\';\n\n/**\n * {@link eslint.Linter}\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'block tag naming a member of an imported namespace',
        'import type eslint from \'eslint\';\n\n/**\n * @see eslint.Linter\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'method of a local class',
        'class Mailer {\n  public send(): void {}\n}\n\n/**\n * {@link Mailer.send}\n */\nexport const value = new Mailer();\n',
      ),
      buildValidTypeAwareCase(
        'block tag with a url',
        '/**\n * @see https://example.com/reference\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'instance member through the hash form',
        'class Mailer {\n  public send(): void {}\n}\n\n/**\n * {@link Mailer#send}\n */\nexport const value = new Mailer();\n',
      ),
      buildValidTypeAwareCase(
        'chained namepath through both separators',
        'class Idea {\n  public consider(): void {}\n}\n\nclass Person {\n  public idea = new Idea();\n}\n\n/**\n * {@link Person#idea.consider}\n */\nexport const value = new Person();\n',
      ),
      buildValidTypeAwareCase(
        'see wrapping an inline link',
        'class Mailer {}\n\n/**\n * @see {@link Mailer}\n */\nexport const value = new Mailer();\n',
      ),
      buildValidTypeAwareCase(
        'link text after a pipe',
        'class Mailer {\n  public send(): void {}\n}\n\n/**\n * {@link Mailer|the transport}\n */\nexport const value = new Mailer();\n',
      ),
      buildValidTypeAwareCase(
        'inner member is not a typescript symbol',
        'class Person {}\n\n/**\n * {@link Person~say}\n */\nexport const value = new Person();\n',
      ),
      buildValidTypeAwareCase(
        'module namepath',
        '/**\n * {@link module:foo}\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'external namepath',
        '/**\n * {@link external:String}\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'event namepath',
        '/**\n * {@link event:MyEvent}\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'interface target',
        'interface Mailer {\n  send: () => void;\n}\n\n/**\n * {@link Mailer}\n */\nexport const value = (mailer: Mailer): void => mailer.send();\n',
      ),
      buildValidTypeAwareCase(
        'type alias target',
        'type Transport = string;\n\n/**\n * {@link Transport}\n */\nexport const value = (transport: Transport): string => transport;\n',
      ),
      buildValidTypeAwareCase(
        'linkcode target that exists',
        'class Mailer {\n  public send(): void {}\n}\n\n/**\n * {@linkcode Mailer.send}\n */\nexport const value = new Mailer();\n',
      ),
      buildValidTypeAwareCase(
        'block tag with a lowercase unresolved target reads as free text',
        '/**\n * @see somethingEntirelyAbsent\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'lowercase function that exists',
        'export const sendMail = (): void => {};\n\n/**\n * {@link sendMail}\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'schemeless host is left alone',
        '/**\n * {@see www.example.com}\n */\nexport const value = true;\n',
      ),
      buildValidTypeAwareCase(
        'prose is left alone',
        '/**\n * @see the registration flow\n */\nexport const value = true;\n',
      ),
    ],
    invalid: [
      buildInvalidTypeAwareCase(
        'misspelled member of an imported namespace',
        'import type eslint from \'eslint\';\n\n/**\n * {@link eslint.Lintr}\n */\nexport const value = true;\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'block tag naming a misspelled member',
        'import type eslint from \'eslint\';\n\n/**\n * @see eslint.Lintr\n */\nexport const value = true;\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'misspelled method of a local class',
        'class Mailer {\n  public send(): void {}\n}\n\n/**\n * {@link Mailer.sendNow}\n */\nexport const value = new Mailer();\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      {
        name: 'type import expression is not a namepath',
        code: '/**\n * {@link import(\'eslint\').Linter}\n */\nexport const value = true;\n',
        filename: TYPE_AWARE_FIXTURE_FILE,
        errors: [{ message: 'Reference `import(\'eslint\').Linter` does not exist.' }],
      },
      buildInvalidTypeAwareCase(
        'broken link in a chained namepath',
        'class Idea {\n  public consider(): void {}\n}\n\nclass Person {\n  public idea = new Idea();\n}\n\n/**\n * {@link Person#idea#missing}\n */\nexport const value = new Person();\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'misspelled instance member through the hash form',
        'class Mailer {\n  public send(): void {}\n}\n\n/**\n * {@link Mailer#sendNow}\n */\nexport const value = new Mailer();\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      {
        name: 'undeclared target with link text after a pipe',
        code: '/**\n * {@link Missing|the transport}\n */\nexport const value = true;\n',
        filename: TYPE_AWARE_FIXTURE_FILE,
        errors: [{ message: 'Reference `Missing` does not exist.' }],
      },
      buildInvalidTypeAwareCase(
        'block tag naming an undeclared target',
        '/**\n * @see Mailer\n */\nexport const value = true;\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'linkcode naming a misspelled member',
        'class Mailer {\n  public send(): void {}\n}\n\n/**\n * {@linkcode Mailer.sendNow}\n */\nexport const value = new Mailer();\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'the same target twice on one line',
        '/**\n * {@link Mailer} and {@link Mailer}\n */\nexport const value = true;\n',
        [MESSAGE_ID_MISSING_REFERENCE, MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'two targets on one line',
        '/**\n * {@link Mailer} and {@link Transport}\n */\nexport const value = true;\n',
        [MESSAGE_ID_MISSING_REFERENCE, MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'misspelled lowercase function in a link tag',
        'export const sendMail = (): void => {};\n\n/**\n * {@link sendMial}\n */\nexport const value = true;\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'inline see is checked even though typescript does not model it',
        '/**\n * {@see Mailer}\n */\nexport const value = true;\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
      buildInvalidTypeAwareCase(
        'undeclared target',
        '/**\n * {@link Mailer}\n */\nexport const value = true;\n',
        [MESSAGE_ID_MISSING_REFERENCE],
      ),
    ],
  });
});
