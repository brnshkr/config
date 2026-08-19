import path from 'node:path';

import { beforeEach, test } from 'vitest';

import {
  MESSAGE_ID_MISSING_DESCRIPTION,
  MESSAGE_ID_MISSING_EXAMPLE,
  MESSAGE_ID_MISSING_FILE_DESCRIPTION,
  MESSAGE_ID_MISSING_PARAM,
  MESSAGE_ID_MISSING_RETURNS,
  publicApiDocumentationRule,
} from '../../../src/js/eslint/configs/builtin/public-api-documentation';

import { clearPublicApiResolutionCache } from '../../../src/js/eslint/utils/public-api';
import { createRuleCaseBuilders, runJsRuleTests, runTsRuleTests } from '../utils/rule-tester';

const FIXTURE_ROOT = path.resolve(import.meta.dirname, '../fixtures/eslint-rules');
const FIXTURE_INDEX = path.join(FIXTURE_ROOT, 'src/index.ts');
const FIXTURE_UNLISTED = path.join(FIXTURE_ROOT, 'src/unlisted.ts');

const RULE_OPTIONS = <const>{
  packageJsonPath: path.join(FIXTURE_ROOT, 'package.json'),
  distRoot: './dist',
  srcRoot: './src',
};

const wrap = (body: string): string => `/** @file Fixture file. */\n${body}\n`;

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders({
  filename: FIXTURE_INDEX,
  options: [RULE_OPTIONS],
});

beforeEach(() => {
  clearPublicApiResolutionCache();
});

test('publicApiDocumentationRule scenarios', () => {
  runTsRuleTests(publicApiDocumentationRule, {
    valid: [
      buildValidCase(
        'documented @api function',
        wrap(`
          /**
           * Adds two numbers.
           *
           * @api
           *
           * @param a - First operand.
           * @param b - Second operand.
           *
           * @returns The sum of the operands.
           *
           * @example
           * add(1, 2);
           */
          export const add = (a: number, b: number): number => a + b;
        `),
      ),
      buildValidCase(
        '@internal symbol is skipped',
        wrap(`
          /** @internal */
          export const helper = (value: string): string => value;
        `),
      ),
      buildValidCase(
        'non-public-API file is untouched',
        'export const undocumented = 1;\n',
        {
          filename: FIXTURE_UNLISTED,
        },
      ),
    ],
    invalid: [
      buildInvalidCase(
        'missing @file description',
        'export {};\n',
        [MESSAGE_ID_MISSING_FILE_DESCRIPTION],
      ),
      buildInvalidCase(
        '@api function missing description, returns prose, example',
        wrap(`
          /**
           * @api
           *
           * @param value
           */
          export const compute = (value: number): number => value;
        `),
        [
          MESSAGE_ID_MISSING_DESCRIPTION,
          MESSAGE_ID_MISSING_PARAM,
          MESSAGE_ID_MISSING_RETURNS,
          MESSAGE_ID_MISSING_EXAMPLE,
        ],
      ),
      buildInvalidCase(
        '@api class with undocumented public method',
        wrap(`
          /**
           * Public widget.
           *
           * @api
           */
          export class Widget {
            public render(target: string): void {
              void target;
            }
          }
        `),
        [
          MESSAGE_ID_MISSING_DESCRIPTION,
          MESSAGE_ID_MISSING_PARAM,
          MESSAGE_ID_MISSING_EXAMPLE,
        ],
      ),
      buildInvalidCase(
        '@api interface with undocumented method',
        wrap(`
          /**
           * Public shape.
           *
           * @api
           */
          export interface Shape {
            render(target: string): void;
          }
        `),
        [
          MESSAGE_ID_MISSING_DESCRIPTION,
          MESSAGE_ID_MISSING_PARAM,
        ],
      ),
      buildInvalidCase(
        '@api type alias without description',
        wrap(`
          /** @api */
          export type Id = string;
        `),
        [MESSAGE_ID_MISSING_DESCRIPTION],
      ),
      buildInvalidCase(
        '@api enum without description',
        wrap(`
          /** @api */
          export enum Color { Red, Blue }
        `),
        [MESSAGE_ID_MISSING_DESCRIPTION],
      ),
      buildInvalidCase(
        '@api default arrow missing description',
        wrap(`
          /** @api */
          export default (value: number): number => value;
        `),
        [
          MESSAGE_ID_MISSING_DESCRIPTION,
          MESSAGE_ID_MISSING_PARAM,
          MESSAGE_ID_MISSING_RETURNS,
          MESSAGE_ID_MISSING_EXAMPLE,
        ],
      ),
      buildInvalidCase(
        '@api default constant expression without description',
        wrap(`
          /** @api */
          export default { value: 1 };
        `),
        [MESSAGE_ID_MISSING_DESCRIPTION],
      ),
      buildInvalidCase(
        '@api const arrow factory requires full function checks',
        wrap(`
          /** @api */
          export const factory = (input: number): string => String(input);
        `),
        [
          MESSAGE_ID_MISSING_DESCRIPTION,
          MESSAGE_ID_MISSING_PARAM,
          MESSAGE_ID_MISSING_RETURNS,
          MESSAGE_ID_MISSING_EXAMPLE,
        ],
      ),
      buildInvalidCase(
        '@api const object literal needs description only',
        wrap(`
          /** @api */
          export const config = { value: 1, label: 'x' };
        `),
        [MESSAGE_ID_MISSING_DESCRIPTION],
      ),
    ],
  });
});

test('publicApiDocumentationRule valid extras', () => {
  runTsRuleTests(publicApiDocumentationRule, {
    valid: [
      buildValidCase(
        '@api fluent setter skips returns/example',
        wrap(`
          /**
           * Builder with fluent setters.
           *
           * @api
           */
          export class Builder {
            /**
             * Sets the name.
             *
             * @param name - Human-readable label.
             */
            public withName(name: string): this {
              void name;
              return this;
            }
          }
        `),
      ),
      buildValidCase(
        '@api class skips @internal method',
        wrap(`
          /**
           * Container.
           *
           * @api
           */
          export class Container {
            /** @internal */
            public touchMe(value: string): string {
              return value;
            }
          }
        `),
      ),
      buildValidCase(
        '@api class skips private/protected methods',
        wrap(`
          /**
           * Container.
           *
           * @api
           */
          export class Container {
            private value = 0;
            private bump(): void {
              this.value += 1;
            }
            protected helper(): number {
              return this.value;
            }
          }
        `),
      ),
      buildValidCase(
        '@api class constructor needs params/example but not description',
        wrap(`
          /**
           * Greeter.
           *
           * @api
           */
          export class Greeter {
            /**
             * @param name - Recipient name.
             *
             * @example
             * new Greeter('world');
             */
            public constructor(public readonly name: string) {}
          }
        `),
      ),
      buildValidCase(
        '@api void method skips returns',
        wrap(`
          /**
           * Logger.
           *
           * @api
           */
          export class Logger {
            /**
             * Writes a line.
             *
             * @param line - Message body.
             *
             * @example
             * logger.write('hi');
             */
            public write(line: string): void {
              void line;
            }
          }
        `),
      ),
      buildValidCase(
        '@api interface abstract methods skip example',
        wrap(`
          /**
           * Renderer contract.
           *
           * @api
           */
          export interface Renderer {
            /**
             * Renders target.
             *
             * @param target - Element to render.
             *
             * @returns Rendered string.
             */
            render(target: string): string;
          }
        `),
      ),
      buildValidCase(
        '@api documented type alias',
        wrap(`
          /**
           * Identifier alias.
           *
           * @api
           */
          export type Id = string;
        `),
      ),
      buildValidCase(
        '@api documented enum',
        wrap(`
          /**
           * Available colors.
           *
           * @api
           */
          export enum Color { Red, Blue }
        `),
      ),
      buildValidCase(
        '@api default identifier passes description from binding',
        wrap(`
          /** Underlying. @api */
          const underlying = 1;
          /**
           * Re-exported.
           *
           * @api
           */
          export default underlying;
        `),
      ),
    ],
    invalid: [],
  });
});

test('publicApiDocumentationRule works with default ESLint parser', () => {
  runJsRuleTests(publicApiDocumentationRule, {
    valid: [
      buildValidCase(
        'documented @api JS function',
        wrap(`
          /**
           * Adds two numbers.
           *
           * @api
           *
           * @param {number} a - First operand.
           * @param {number} b - Second operand.
           *
           * @returns {number} The sum of the operands.
           *
           * @example
           * add(1, 2);
           */
          export const add = (a, b) => a + b;
        `),
      ),
      buildValidCase(
        '@internal JS const is skipped',
        wrap(`
          /** @internal */
          export const helper = (value) => value;
        `),
      ),
    ],
    invalid: [
      buildInvalidCase(
        'missing @file description on JS file',
        'export {};\n',
        [MESSAGE_ID_MISSING_FILE_DESCRIPTION],
      ),
      buildInvalidCase(
        '@api JS function missing description, returns prose, example',
        wrap(`
          /**
           * @api
           *
           * @param {number} value
           */
          export const compute = (value) => value;
        `),
        [
          MESSAGE_ID_MISSING_DESCRIPTION,
          MESSAGE_ID_MISSING_PARAM,
          MESSAGE_ID_MISSING_EXAMPLE,
        ],
      ),
    ],
  });
});
