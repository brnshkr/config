/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const FILE_NAMES_TO_IGNORE = <const>[
  '__mocks__',
  '__tests__',
  'ACKNOWLEDGMENTS.md',
  'ADOPTERS.md',
  'AGENT_INSTRUCTIONS.md',
  'AGENTS.md',
  'API_REFERENCE.md',
  'ARCHITECTURE.md',
  'AUTHORS.md',
  'BUILD.md',
  'CHANGELOG.md',
  'CLAUDE.md',
  'CODE_OF_CONDUCT.md',
  'CODEOWNERS.md',
  'CODING_STANDARDS.md',
  'COMMUNITY_GUIDELINES.md',
  'CONFIGURATION.md',
  'CONTRIBUTING.md',
  'CONTRIBUTORS.md',
  'DATA_CARD.md',
  'DATA_MODEL.md',
  'DATA_PRIVACY.md',
  'DEPENDENCIES.md',
  'DESIGN.md',
  'DEVELOPMENT.md',
  'ETHICS.md',
  'EVALUATION.md',
  'FAQ.md',
  'GOVERNANCE.md',
  'INSTALL.md',
  'INSTRUCTIONS.md',
  'ISSUE_TEMPLATE.md',
  'LICENSE.md',
  'LLMS.md',
  'LOCALIZATION.md',
  'MAINTAINERS.md',
  'MEETING_NOTES.md',
  'MIGRATIONS.md',
  'ML_LIFECYCLE.md',
  'ML_PIPELINE.md',
  'MLOPS.md',
  'MODEL_CARD.md',
  'MODEL_MONITORING.md',
  'NFR.md',
  'OVERVIEW.md',
  'PERFORMANCE.md',
  'PROJECT_METADATA.md',
  'PULL_REQUEST_TEMPLATE.md',
  'README.md',
  'REFERENCES.md',
  'RELEASE_PROCESS.md',
  'RELEASING.md',
  'RESEARCH.md',
  'ROADMAP.md',
  'SECURITY_POLICY.md',
  'SECURITY.md',
  'SKILL.md',
  'SPEC.md',
  'STYLE_GUIDE.md',
  'SUPPORT.md',
  'TESTING.md',
  'THIRD_PARTY.md',
  'THREAT_MODEL.md',
  'TROUBLESHOOTING.md',
  'UPGRADE_NOTES.md',
  'VERSIONING.md',
] satisfies string[];

const COMMENT_TERMS = <const>[
  'commitlint',
  'JSDoc',
  'PHP',
  'PHPStan',
  'PHPUnit',
  'pnpm',
  'PostCSS',
  'SCSS',
  'Stylelint',
  'Symfony',
  'TOML',
  'TSDoc',
  'Vitest',
];

export const unicorn = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginUnicorn],
  } = await resolvePackages(MODULES.unicorn);

  if (!pluginUnicorn) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.UNICORN, SUB_SCOPES.SETUP),
      plugins: {
        unicorn: pluginUnicorn,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.UNICORN, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        ...pluginUnicorn.configs.recommended.rules,
        'no-else-return': 'off',
        'no-useless-concat': 'off',
        'operator-assignment': 'off',
        'unicorn/comment-content': ['error', {
          replacements: {
            [String.raw`\bapplication\b(?!/)`]: false,
            [String.raw`\bapplications\b`]: false,
            ...Object.fromEntries(COMMENT_TERMS.map((term) => [
              String.raw`\b${term}\b`,
              {
                replacement: term,
                caseSensitive: false,
              },
            ])),
          },
        }],
        'unicorn/consistent-boolean-name': 'off',
        'unicorn/consistent-class-member-order': ['error', {
          order: [
            'public-field',
            'static-field',
            'private-field',
            'static-block',
            'constructor',
            'public-method',
            'static-method',
            'private-method',
          ],
        }],
        'unicorn/consistent-conditional-object-spread': ['error', 'ternary'],
        'unicorn/consistent-destructuring': 'error',
        'func-style': 'off',
        'unicorn/consistent-function-style': ['error', {
          defaultExport: 'arrow-function',
          namedExports: 'arrow-function',
          namedFunctions: 'arrow-function',
          objectProperties: 'arrow-function',
          reassignedVariables: 'arrow-function',
          typedVariables: 'arrow-function',
        }],
        'unicorn/custom-error-definition': 'error',
        'unicorn/filename-case': ['error', {
          case: 'kebabCase',
          ignore: FILE_NAMES_TO_IGNORE,
        }],
        'unicorn/require-post-message-target-origin': 'error',
        'unicorn/iteration-fallback-style': ['error', 'fallback'],
        'unicorn/name-replacements': ['error', {
          replacements: {
            application: false,
            applications: false,
            repository: false,
          },
          ignore: [
            '[Ii]nheritDoc',
            String.raw`\.dist$`,
          ],
        }],
        'unicorn/no-accidental-bitwise-operator': 'off',
        'unicorn/no-array-front-mutation': 'error',
        'unicorn/no-array-reduce': ['error', {
          allowSimpleOperations: false,
        }],
        'unicorn/no-array-reverse': ['error', {
          allowExpressionStatement: false,
        }],
        'unicorn/no-array-sort': ['error', {
          allowExpressionStatement: false,
        }],
        'unicorn/no-barrel-files': 'error',
        'unicorn/no-instanceof-builtins': ['error', {
          useErrorIsError: true,
        }],
        'unicorn/no-invalid-file-input-accept': 'error',
        'unicorn/no-keyword-prefix': 'error',
        'unicorn/no-manually-wrapped-comments': 'error',
        'unicorn/no-missing-local-resource': 'error',
        'unicorn/no-negated-comparison': ['error', {
          checkLogicalExpressions: true,
        }],
        'unicorn/no-null': ['error', {
          checkStrictEquality: true,
        }],
        'unicorn/no-typeof-undefined': ['error', {
          checkGlobalVariables: true,
        }],
        'unicorn/no-unsafe-dom-html': 'error',
        'unicorn/no-unused-properties': 'error',
        'unicorn/numeric-separators-style': ['error', {
          binary: {
            minimumDigits: 9,
            groupLength: 8,
          },
          hexadecimal: {
            minimumDigits: 3,
            groupLength: 2,
          },
          number: {
            minimumDigits: 4,
            groupLength: 3,
            fractionGroupLength: 3,
          },
          octal: {
            minimumDigits: 4,
            groupLength: 3,
          },
        }],
        'unicorn/prefer-dispose': 'error',
        'unicorn/prefer-error-is-error': 'error',
        'unicorn/prefer-minimal-ternary': ['error', {
          checkComputedMemberAccess: true,
          checkVaryingBase: true,
        }],
        'unicorn/prefer-queue-microtask': ['error', {
          checkSetImmediate: true,
          checkSetTimeout: true,
        }],
        'unicorn/prefer-regexp-escape': 'error',
        'unicorn/prefer-short-arrow-method': 'error',
        'unicorn/prefer-switch': 'off',
        'unicorn/require-css-escape': ['error', {
          checkAllSelectors: true,
        }],
        'unicorn/string-content': ['error', {
          patterns: {
            '\\.\\.\\.': '…',
          },
        }],
        'unicorn/text-encoding-identifier-case': ['error', {
          withDash: true,
        }],
        'unicorn/try-complexity': 'error',
      },
    },
  ];
};
