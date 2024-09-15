import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const FILE_NAMES_TO_IGNORE = <const>[
  'ACKNOWLEDGMENTS.md',
  'ADOPTERS.md',
  'AGENTS.md',
  'API_REFERENCE.md',
  'ARCHITECTURE.md',
  'AUTHORS.md',
  'BUILD.md',
  'CHANGELOG.md',
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
  'SPEC.md',
  'SECURITY_POLICY.md',
  'SECURITY.md',
  'STYLE_GUIDE.md',
  'SUPPORT.md',
  'TESTING.md',
  'THIRD_PARTY.md',
  'THREAT_MODEL.md',
  'TROUBLESHOOTING.md',
  'UPGRADE_NOTES.md',
  'VERSIONING.md',
] satisfies string[];

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
        'unicorn/better-regex': 'error',
        'unicorn/consistent-destructuring': 'error',
        'unicorn/custom-error-definition': 'error',
        'unicorn/filename-case': ['error', {
          case: 'kebabCase',
          ignore: FILE_NAMES_TO_IGNORE,
        }],
        'unicorn/require-post-message-target-origin': 'error',
        'unicorn/no-keyword-prefix': 'error',
        'unicorn/no-nested-ternay': 'off',
        'no-nested-ternary': 'error',
        'unicorn/no-unused-properties': 'error',
        'unicorn/prefer-json-parse-buffer': 'error',
        'unicorn/prefer-switch': 'off',
        'unicorn/string-content': ['error', {
          patterns: {
            '\\.\\.\\.': '…',
            '^http:\\/\\/': String.raw`^https:\/\/`,
          },
        }],
      },
    },
  ];
};
