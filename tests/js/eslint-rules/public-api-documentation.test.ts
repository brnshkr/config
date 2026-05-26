import path from 'node:path';

import { beforeEach, expect, test } from 'vitest';

import {
  MESSAGE_ID_MISSING_DESCRIPTION,
  MESSAGE_ID_MISSING_EXAMPLE,
  MESSAGE_ID_MISSING_FILE_DESCRIPTION,
  MESSAGE_ID_MISSING_PARAM,
  MESSAGE_ID_MISSING_RETURNS,
  publicApiDocumentationRule,
} from '../../../src/js/eslint/configs/builtin/public-api-documentation';

import { clearPublicApiResolutionCache } from '../../../src/js/eslint/utils/public-api';
import { jsRuleTester, tsRuleTester } from '../utils/rule-tester';

const FIXTURE_ROOT = path.resolve(import.meta.dirname, '../fixtures/eslint-rules');
const FIXTURE_INDEX = path.join(FIXTURE_ROOT, 'src/index.ts');
const FIXTURE_UNLISTED = path.join(FIXTURE_ROOT, 'src/unlisted.ts');

const RULE_OPTIONS = <const>{
  packageJsonPath: path.join(FIXTURE_ROOT, 'package.json'),
  distRoot: './dist',
  srcRoot: './src',
};

const wrap = (body: string): string => `/** @file Fixture file. */\n${body}\n`;

beforeEach(() => {
  clearPublicApiResolutionCache();
});

test('publicApiDocumentationRule scenarios', () => {
  expect(() => {
    tsRuleTester.run('public-api-documentation', publicApiDocumentationRule, {
      valid: [
        {
          name: 'documented @api function',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
        },
        {
          name: '@internal symbol is skipped',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** @internal */
            export const helper = (value: string): string => value;
          `),
        },
        {
          name: 'non-public-API file is untouched',
          filename: FIXTURE_UNLISTED,
          options: [RULE_OPTIONS],
          code: 'export const undocumented = 1;\n',
        },
      ],
      invalid: [
        {
          name: 'missing @file description',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export {};\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_FILE_DESCRIPTION }],
        },
        {
          name: '@api function missing description, returns prose, example',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /**
             * @api
             *
             * @param value
             */
            export const compute = (value: number): number => value;
          `),
          errors: [
            { messageId: MESSAGE_ID_MISSING_DESCRIPTION },
            { messageId: MESSAGE_ID_MISSING_PARAM },
            { messageId: MESSAGE_ID_MISSING_RETURNS },
            { messageId: MESSAGE_ID_MISSING_EXAMPLE },
          ],
        },
        {
          name: '@api class with undocumented public method',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
          errors: [
            { messageId: MESSAGE_ID_MISSING_DESCRIPTION },
            { messageId: MESSAGE_ID_MISSING_PARAM },
            { messageId: MESSAGE_ID_MISSING_EXAMPLE },
          ],
        },
        {
          name: '@api interface with undocumented method',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /**
             * Public shape.
             *
             * @api
             */
            export interface Shape {
              render(target: string): void;
            }
          `),
          errors: [
            { messageId: MESSAGE_ID_MISSING_DESCRIPTION },
            { messageId: MESSAGE_ID_MISSING_PARAM },
          ],
        },
        {
          name: '@api type alias without description',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** @api */
            export type Id = string;
          `),
          errors: [{ messageId: MESSAGE_ID_MISSING_DESCRIPTION }],
        },
        {
          name: '@api enum without description',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** @api */
            export enum Color { Red, Blue }
          `),
          errors: [{ messageId: MESSAGE_ID_MISSING_DESCRIPTION }],
        },
        {
          name: '@api default arrow missing description',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** @api */
            export default (value: number): number => value;
          `),
          errors: [
            { messageId: MESSAGE_ID_MISSING_DESCRIPTION },
            { messageId: MESSAGE_ID_MISSING_PARAM },
            { messageId: MESSAGE_ID_MISSING_RETURNS },
            { messageId: MESSAGE_ID_MISSING_EXAMPLE },
          ],
        },
        {
          name: '@api default constant expression without description',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** @api */
            export default { value: 1 };
          `),
          errors: [{ messageId: MESSAGE_ID_MISSING_DESCRIPTION }],
        },
        {
          name: '@api const arrow factory requires full function checks',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** @api */
            export const factory = (input: number): string => String(input);
          `),
          errors: [
            { messageId: MESSAGE_ID_MISSING_DESCRIPTION },
            { messageId: MESSAGE_ID_MISSING_PARAM },
            { messageId: MESSAGE_ID_MISSING_RETURNS },
            { messageId: MESSAGE_ID_MISSING_EXAMPLE },
          ],
        },
        {
          name: '@api const object literal needs description only',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** @api */
            export const config = { value: 1, label: 'x' };
          `),
          errors: [{ messageId: MESSAGE_ID_MISSING_DESCRIPTION }],
        },
      ],
    });
  }).not.toThrow();
});

test('publicApiDocumentationRule valid extras', () => {
  expect(() => {
    tsRuleTester.run('public-api-documentation', publicApiDocumentationRule, {
      valid: [
        {
          name: '@api fluent setter skips returns/example',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
        },
        {
          name: '@api class skips @internal method',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
        },
        {
          name: '@api class skips private/protected methods',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
        },
        {
          name: '@api class constructor needs params/example but not description',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
        },
        {
          name: '@api void method skips returns',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
        },
        {
          name: '@api interface abstract methods skip example',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
        },
        {
          name: '@api documented type alias',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /**
             * Identifier alias.
             *
             * @api
             */
            export type Id = string;
          `),
        },
        {
          name: '@api documented enum',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /**
             * Available colors.
             *
             * @api
             */
            export enum Color { Red, Blue }
          `),
        },
        {
          name: '@api default identifier passes description from binding',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** Underlying. @api */
            const underlying = 1;
            /**
             * Re-exported.
             *
             * @api
             */
            export default underlying;
          `),
        },
      ],
      invalid: [],
    });
  }).not.toThrow();
});

test('publicApiDocumentationRule works with default ESLint parser', () => {
  expect(() => {
    jsRuleTester.run('public-api-documentation', publicApiDocumentationRule, {
      valid: [
        {
          name: 'documented @api JS function',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
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
        },
        {
          name: '@internal JS const is skipped',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /** @internal */
            export const helper = (value) => value;
          `),
        },
      ],
      invalid: [
        {
          name: 'missing @file description on JS file',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export {};\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_FILE_DESCRIPTION }],
        },
        {
          name: '@api JS function missing description, returns prose, example',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: wrap(`
            /**
             * @api
             *
             * @param {number} value
             */
            export const compute = (value) => value;
          `),
          errors: [
            { messageId: MESSAGE_ID_MISSING_DESCRIPTION },
            { messageId: MESSAGE_ID_MISSING_PARAM },
            { messageId: MESSAGE_ID_MISSING_EXAMPLE },
          ],
        },
      ],
    });
  }).not.toThrow();
});
