import { INDENT, MAX_LEN } from '../../shared/utils/constants';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName, renameRules } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const style = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginStyle],
  } = await resolvePackages(MODULES.style);

  if (!pluginStyle) {
    return [];
  }

  const styleConfig = pluginStyle.configs.customize({
    jsx: true,
    semi: true,
    indent: INDENT,
    quotes: 'single',
    quoteProps: 'as-needed',
    arrowParens: true,
    blockSpacing: true,
    braceStyle: '1tbs',
    commaDangle: 'always-multiline',
  });

  /* eslint-disable no-magic-numbers -- Index 2 refers to the options of the rule here ('style/indent': ['error', <indent>, <options>]) */
  const indentRuleConfig = (Array.isArray(styleConfig.rules?.['@stylistic/indent'])
    && typeof styleConfig.rules['@stylistic/indent'][2] === 'object'
    && styleConfig.rules['@stylistic/indent'][2] !== null)
    ? styleConfig.rules['@stylistic/indent'][2]
    : undefined;
  /* eslint-enable no-magic-numbers -- Restore rule */

  return [
    {
      name: buildConfigName(MAIN_SCOPES.STYLE, SUB_SCOPES.SETUP),
      plugins: {
        style: pluginStyle,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.STYLE, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        ...renameRules(styleConfig.rules, { '@stylistic': 'style' }),
        'style/array-bracket-newline': ['error', 'consistent'],
        'style/array-element-newline': ['error', 'consistent'],
        'style/function-call-argument-newline': ['error', 'consistent'],
        'style/function-call-spacing': 'error',
        'style/function-paren-newline': ['error', 'multiline-arguments'],
        'style/generator-star-spacing': ['error', {
          before: false,
          after: true,
          method: 'before',
          shorthand: 'neither',
        }],
        'style/implicit-arrow-linebreak': 'error',
        'style/indent': ['error', INDENT, {
          ...indentRuleConfig,
          offsetTernaryExpressions: false,
        }],
        'style/jsx-child-element-spacing': 'error',
        'style/jsx-pascal-case': 'error',
        'style/jsx-self-closing-comp': 'error',
        'style/line-comment-position': 'error',
        'style/linebreak-style': 'error',
        'style/lines-around-comment': ['error', {
          beforeBlockComment: true,
          afterHashbangComment: true,
          allowBlockStart: true,
          allowObjectStart: true,
          allowArrayStart: true,
          allowClassStart: true,
          allowEnumStart: true,
          allowInterfaceStart: true,
          allowModuleStart: true,
          allowTypeStart: true,
        }],
        'style/max-len': ['error', {
          code: MAX_LEN,
          tabWidth: INDENT,
          ignoreUrls: true,
          ignoreStrings: true,
          ignoreComments: true,
        }],
        'style/newline-per-chained-call': 'error',
        'style/no-confusing-arrow': 'error',
        'style/no-extra-parens': [
          'error',
          'all',
          {
            ignoredNodes: [
              'ArrowFunctionExpression[body.type=ConditionalExpression]',
              'SpreadElement[argument.type=ConditionalExpression]',
              'SpreadElement[argument.type=LogicalExpression]',
              'SpreadElement[argument.type=AwaitExpression]',
            ],
            nestedBinaryExpressions: false,
            nestedConditionalExpressions: false,
            ternaryOperandBinaryExpressions: false,
          },
        ],
        'style/no-extra-semi': 'error',
        'style/no-mixed-operators': ['error', {
          allowSamePrecedence: false,
          groups: [
            ['+', '-', '*', '/', '%', '**', '??'],
            ['&', '|', '^', '~', '<<', '>>', '>>>', '??'],
            ['==', '!=', '===', '!==', '>', '>=', '<', '<=', '??'],
            ['&&', '||', '?:', '??'],
            ['in', 'instanceof', '??'],
          ],
        }],
        'style/nonblock-statement-body-position': ['error', 'below'],
        'style/object-curly-newline': ['error', {
          /* eslint-disable ts/naming-convention -- Options need to be cased like this */
          ObjectExpression: {
            minProperties: 4,
            multiline: true,
            consistent: true,
          },
          ObjectPattern: {
            minProperties: 4,
            multiline: true,
            consistent: true,
          },
          ImportDeclaration: {
            minProperties: 4,
            multiline: true,
            consistent: true,
          },
          ExportDeclaration: {
            minProperties: 4,
            multiline: true,
            consistent: true,
          },
          /* eslint-enable ts/naming-convention -- Restore rule */
        }],
        'style/object-property-newline': ['error', {
          allowAllPropertiesOnSameLine: true,
        }],
        'style/one-var-declaration-per-line': ['error', 'always'],
        'style/operator-linebreak': ['error', 'before', {
          overrides: {
            '=': 'none',
          },
        }],
        'style/padding-line-between-statements': [
          'error',
          {
            blankLine: 'always',
            prev: '*',
            next: [
              'block-like',
              'block',
              'break',
              'class',
              'continue',
              'enum',
              'expression',
              'function-overload',
              'interface',
              'multiline-return',
              'multiline-const',
              'multiline-export',
              'multiline-expression',
              'multiline-let',
              'multiline-var',
              'return',
              'singleline-const',
              'singleline-export',
              'singleline-let',
              'singleline-var',
              'throw',
              'type',
            ],
          },
          {
            blankLine: 'always',
            next: '*',
            prev: [
              'block-like',
              'block',
              'class',
              'directive',
              'enum',
              'expression',
              'interface',
              'multiline-const',
              'multiline-export',
              'multiline-expression',
              'multiline-let',
              'multiline-var',
              'singleline-const',
              'singleline-export',
              'singleline-let',
              'singleline-var',
            ],
          },
          {
            blankLine: 'never',
            prev: ['singleline-const', 'singleline-let', 'singleline-var'],
            next: ['singleline-const', 'singleline-let', 'singleline-var'],
          },
          {
            blankLine: 'never',
            prev: ['singleline-export'],
            next: ['singleline-export'],
          },
          {
            blankLine: 'never',
            prev: 'function-overload',
            next: ['function', 'function-overload'],
          },
          {
            blankLine: 'never',
            prev: 'directive',
            next: 'directive',
          },
          {
            blankLine: 'any',
            prev: 'expression',
            next: 'expression',
          },
          {
            blankLine: 'always',
            prev: '*',
            next: 'multiline-expression',
          },
          {
            blankLine: 'always',
            prev: 'multiline-expression',
            next: '*',
          },
          {
            blankLine: 'never',
            prev: 'type',
            next: 'type',
          },
          {
            blankLine: 'always',
            prev: 'multiline-type',
            next: 'type',
          },
          {
            blankLine: 'always',
            prev: 'type',
            next: 'multiline-type',
          },
          {
            blankLine: 'always',
            prev: 'multiline-type',
            next: 'multiline-type',
          },
          {
            blankLine: 'always',
            prev: 'cjs-import',
            next: ['*'],
          },
          {
            blankLine: 'any',
            prev: 'cjs-import',
            next: 'cjs-import',
          },
          {
            blankLine: 'always',
            next: '*',
            prev: 'cjs-export',
          },
        ],
        'style/semi-style': 'error',
        'style/switch-colon-spacing': 'error',
        'style/yield-star-spacing': ['error', {
          before: false,
          after: true,
        }],
      },
    },
  ];
};
