/**
 * @internal @brnshkr/config/eslint
 */

import { getTsEslintParserIfExists } from '#eslint/configs/typescript.ts';
import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName } from '#eslint/utils/config.ts';
import { GLOB_SCRIPT_FILES, GLOB_SCRIPT_FILES_WITHOUT_TS, GLOB_TS } from '#eslint/utils/globs.ts';
import { isModuleEnabled, MODULES, resolvePackages } from '#eslint/utils/module.ts';

import {
  objectEntries,
  objectFromEntries,
  objectKeys,
  readOwnValue,
} from '#shared/utils/object.ts';

import type { Config } from '#eslint/types/config.ts';

const TAGS_BY_MODULE = <const>{
  test: [
    'vitest-environment',
    'vitest-environment-options',
  ],
} satisfies Partial<Record<keyof typeof MODULES, readonly string[]>>;

const EXAMPLE_CODE_REGEX = '/```(?:js|javascript|ts|typescript)\\s*\\n([\\s\\S]*?)\\n\\s*```/gv';

export const DESCRIPTION_DASHES = <const>{
  param: 'always',
  property: 'always',
  template: 'always',
  returns: 'never',
  throws: 'never',
} satisfies Record<string, 'always' | 'never'>;

export const THROWS_DESCRIPTION_WORDS = <const>[
  'when',
  'unless',
];

const FRAGMENT_DESCRIPTION_TAGS = objectKeys(DESCRIPTION_DASHES).filter((tag) => tag !== 'throws');
const DESCRIPTION_FRAGMENT_PATTERN = String.raw`/^(?!\p{Lu}[\p{Ll}\s])[\s\S]*(?<!\.)$/v`;
const THROWS_DESCRIPTION_PATTERN = String.raw`/^(?:$|(?:${THROWS_DESCRIPTION_WORDS.join('|')})\s[\s\S]*(?<!\.)$)/v`;

const THROWING_FUNCTION_CONTEXTS = <const>[
  'ArrowFunctionExpression:not([async=true]):has(ThrowStatement)',
  'FunctionDeclaration:not([async=true]):has(ThrowStatement)',
  'FunctionExpression:not([async=true]):has(ThrowStatement)',
] satisfies string[];

export const TAG_SEQUENCE = [
  {
    tags: [
      'file',
      'fileoverview',
      'overview',
      'module',
    ],
  },
  {
    tags: [
      'api',
      'internal',
    ],
  },
  {
    tags: [
      'deprecated',
      'ignore',
      'since',
      'version',
      ['to', 'do'].join(''),
    ],
  },
  {
    tags: [
      'author',
      'copyright',
      'license',
    ],
  },
  {
    tags: [
      'summary',
      'typeSummary',
      'desc',
      'description',
      'classdesc',
    ],
  },
  {
    tags: [
      'namespace',
      'category',
      'package',
    ],
  },
  {
    tags: [
      'import',
    ],
  },
  {
    tags: [
      'typedef',
    ],
  },
  {
    tags: [
      'template',
    ],
  },
  {
    tags: [
      'augments',
      'extends',
      'implements',
    ],
  },
  {
    tags: [
      'readonly',
    ],
  },
  {
    tags: [
      'override',
      'requires',
      'mixes',
      'mixin',
      'mixinClass',
      'mixinFunction',
      'borrows',
      'constructs',
      'lends',
      'final',
      'global',
      'abstract',
      'virtual',
      'static',
      'private',
      'protected',
      'public',
      'access',
      'const',
      'constant',
      'variation',
      'var',
      'member',
      'memberof',
      'inner',
      'instance',
      'inheritdoc',
      'inheritDoc',
      'hideconstructor',
    ],
  },
  {
    tags: [
      'name',
    ],
  },
  {
    tags: [
      'this',
      'interface',
      'enum',
      'event',
      'kind',
      'type',
      'alias',
      'external',
      'host',
      'async',
      'callback',
      'func',
      'function',
      'method',
      'class',
      'constructor',
      'generator',
      'fires',
      'emits',
      'listens',
    ],
  },
  {
    tags: [
      'prop',
      'property',
    ],
  },
  {
    tags: [
      'param',
      'arg',
      'argument',
    ],
  },
  {
    tags: [
      'return',
      'returns',
    ],
  },
  {
    tags: [
      'yield',
      'yields',
    ],
  },
  {
    tags: [
      'throws',
      'exception',
    ],
  },
  {
    tags: [
      'satisfies',
    ],
  },
  {
    tags: [
      'default',
      'defaultvalue',
    ],
  },
  {
    tags: [
      'exports',
    ],
  },
  {
    tags: [
      'link',
      'see',
      'tutorial',
    ],
  },
  {
    tags: [
      'example',
    ],
  },
];

export const jsdoc = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginJsdoc],
    optional: [jsdocProcessorModule],
  } = await resolvePackages(MODULES.jsdoc);

  if (!pluginJsdoc) {
    return [];
  }

  const definedTags = [
    'api',
    ...isModuleEnabled(MODULES.test) ? TAGS_BY_MODULE.test : [],
  ];

  const createSetupConfig = (isForTypescript: boolean): Config => ({
    name: buildConfigName(MAIN_SCOPES.JSDOC, `${SUB_SCOPES.SETUP}${isForTypescript ? '-typescript' : ''}`),
    ...(isForTypescript
      ? undefined
      : {
        plugins: {
          jsdoc: pluginJsdoc,
        },
      }),
    settings: {
      jsdoc: {
        mode: isForTypescript ? 'typescript' : 'jsdoc',
      },
    },
  });

  const createRulesConfig = (isForTypescript: boolean): Config => ({
    name: buildConfigName(MAIN_SCOPES.JSDOC, `${SUB_SCOPES.RULES}${isForTypescript ? '-typescript' : ''}`),
    files: isForTypescript ? [GLOB_TS] : [GLOB_TS, ...GLOB_SCRIPT_FILES_WITHOUT_TS],
    rules: {
      ...(isForTypescript
        ? {
          ...(<Config['rules']>Object.fromEntries(
            objectEntries(pluginJsdoc.configs['flat/recommended-typescript-error'].rules ?? {})
              .map(([key, value]) => (
                Object.is(readOwnValue(pluginJsdoc.configs['flat/recommended-error'].rules ?? {}, key), value)
                  ? undefined
                  : [key, value]
              ))
              .filter(Boolean),
          )),
          'jsdoc/check-tag-names': ['error', {
            definedTags,
            typed: true,
          }],
          'jsdoc/require-param': 'off',
          'jsdoc/require-returns': 'off',
        }
        : {
          ...pluginJsdoc.configs['flat/recommended-error'].rules,
          'jsdoc/check-tag-names': ['error', {
            definedTags,
          }],
          'jsdoc/check-indentation': ['error', {
            allowIndentedSections: true,
          }],
          'jsdoc/check-line-alignment': 'error',
          'jsdoc/check-syntax': 'error',
          'jsdoc/check-template-names': 'error',
          'jsdoc/convert-to-jsdoc-comments': ['error', {
            lineOrBlockStyle: 'block',
          }],
          'jsdoc/imports-as-dependencies': 'error',
          'jsdoc/informative-docs': ['error', {
            excludedTags: [
              'default',
            ],
          }],
          'jsdoc/match-description': ['error', {
            tags: {
              ...objectFromEntries(FRAGMENT_DESCRIPTION_TAGS.map((tag) => [tag, {
                match: DESCRIPTION_FRAGMENT_PATTERN,
                message: `The \`@${tag}\` description starts lowercase and ends without a period.`,
              }])),
              throws: {
                match: THROWS_DESCRIPTION_PATTERN,
                message: `The \`@throws\` description starts with \`${THROWS_DESCRIPTION_WORDS.join('` or `')}\`.`,
              },
            },
          }],
          'jsdoc/multiline-blocks': ['error', {
            noSingleLineBlocks: true,
            singleLineTags: [],
          }],
          'jsdoc/no-bad-blocks': ['error', {
            preventAllMultiAsteriskBlocks: true,
          }],
          'jsdoc/no-blank-block-descriptions': 'error',
          'jsdoc/no-blank-blocks': 'error',
          'jsdoc/no-unnecessary-type-assertion': ['error', {
            preferConstToLiteralTuples: true,
          }],
          'jsdoc/normalize-see-links': 'error',
          'jsdoc/prefer-import-tag': 'error',
          'jsdoc/require-asterisk-prefix': 'error',
          'jsdoc/require-hyphen-before-param-description': ['error', DESCRIPTION_DASHES.param, {
            tags: objectFromEntries(objectEntries(DESCRIPTION_DASHES).filter(([tag]) => tag !== 'param')),
          }],
          'jsdoc/require-param-description': 'off',
          'jsdoc/require-property-description': 'off',
          'jsdoc/require-returns-description': 'off',
          'jsdoc/require-jsdoc': ['error', {
            contexts: THROWING_FUNCTION_CONTEXTS,
            require: {
              // eslint-disable-next-line ts/naming-convention -- Option needs to be cased like this
              FunctionDeclaration: false,
            },
          }],
          'jsdoc/require-template': 'error',
          'jsdoc/require-throws': 'error',
          'jsdoc/sort-tags': ['error', {
            tagSequence: TAG_SEQUENCE,
          }],
          'jsdoc/tag-lines': ['error', 'any', {
            maxBlockLines: 1,
            startLines: 1,
            startLinesWithNoTags: 0,
          }],
          'jsdoc/text-escaping': ['error', {
            // eslint-disable-next-line ts/naming-convention -- Option needs to be cased like this
            escapeHTML: true,
          }],
          'jsdoc/ts-method-signature-style': 'error',
          'jsdoc/ts-no-unnecessary-template-expression': 'error',
          'jsdoc/ts-prefer-function-type': 'error',
          // eslint-disable-next-line no-warning-comments -- (jsdoc)
          // TODO: Re-add and fine-tune rule when it more mature (still experimental, see: https://github.com/gajus/eslint-plugin-jsdoc/blob/main/docs/rules/type-formatting.md)
          // 'jsdoc/type-formatting': ['error', {
          //   functionOrClassPostReturnMarkerSpacing: ' ',
          //   objectFieldIndent: ' '.repeat(INDENT),
          //   objectFieldSeparator: 'semicolon-and-linebreak',
          //   objectFieldSeparatorTrailingPunctuation: true,
          //   trailingPunctuationMultilineOnly: true,
          //   separatorForSingleObjectField: false,
          //   stringQuotes: QUOTES,
          // }],
        }),
    },
  });

  const parser = await getTsEslintParserIfExists();

  const examplePlugin = jsdocProcessorModule
    ? jsdocProcessorModule.getJsdocProcessorPlugin({
      checkDefaults: true,
      checkExamples: true,
      checkParams: true,
      checkProperties: true,
      exampleCodeRegex: EXAMPLE_CODE_REGEX,
      matchingFileNameDefaults: 'dummy.jsdoc-defaults.md/*.js',
      matchingFileNameParams: 'dummy.jsdoc-params.md/*.js',
      matchingFileNameProperties: 'dummy.jsdoc-properties.md/*.js',
      parser,
    })
    : undefined;

  return [
    createSetupConfig(false),
    parser ? createSetupConfig(true) : undefined,
    createRulesConfig(false),
    parser ? createRulesConfig(true) : undefined,
    ...(examplePlugin
      ? [
        {
          name: buildConfigName(MAIN_SCOPES.JSDOC, `${SUB_SCOPES.EXAMPLES}/${SUB_SCOPES.SETUP}`),
          plugins: {
            examples: examplePlugin,
          },
        },
        {
          name: buildConfigName(MAIN_SCOPES.JSDOC, `${SUB_SCOPES.EXAMPLES}/${SUB_SCOPES.PROCESSOR}`),
          files: GLOB_SCRIPT_FILES,
          processor: examplePlugin.processors?.['examples'],
        },
      ]
      : []),
  ].filter(Boolean);
};
