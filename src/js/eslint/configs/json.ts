import { INDENT } from '../../shared/utils/constants';
import { objectAssign } from '../../shared/utils/object';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_JSON5, GLOB_JSON, GLOB_JSONC } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { ESLint } from 'eslint';
import type { Config } from '../types/config';

const TSCONFIG_FILES = [
  '**/tsconfig.json',
  '**/tsconfig.*.json',
];

const JSON_FILES_TO_TREAT_AS_JSONC = [
  ...TSCONFIG_FILES,
  '.vscode/**/*.json',
];

const LANGUAGE_TO_GLOB_MAP = {
  json: [GLOB_JSON],
  jsonc: [GLOB_JSONC, ...JSON_FILES_TO_TREAT_AS_JSONC],
  json5: [GLOB_JSON5],
};

const extractAllRules = (configs: Config[]): NonNullable<Config['rules']> => {
  const rules: NonNullable<Config['rules']> = {};

  for (const config of configs) {
    if (config.rules) {
      objectAssign(rules, config.rules);
    }
  }

  return rules;
};

const getJsoncSortConfigs = (): Config[] => [
  {
    name: buildConfigName(MAIN_SCOPES.JSON, `${SUB_SCOPES.RULES}-sort-package-json`),
    files: ['**/package.json'],
    rules: {
      'jsonc/sort-array-values': ['error', {
        pathPattern: '^files$',
        order: {
          type: 'asc',
        },
      }],
      'jsonc/sort-keys': [
        'error',
        {
          pathPattern: '^$',
          order: [
            '$schema',
            'jscpd',
            'jspm',
            'name',
            'version',
            'description',
            'license',
            'private',
            'author',
            'homepage',
            'man',
            'readme',
            'repository',
            'bugs',
            'funding',
            'type',
            'packageManager',
            'bundleDependencies',
            'os',
            'cpu',
            'libc',
            'engines',
            'devEngines',
            'scripts',
            'esnext',
            'module',
            'pnpm',
            'main',
            'browser',
            'dist',
            'bin',
            'types',
            'typings',
            'typesVersions',
            'imports',
            'exports',
            'files',
            'directories',
            'dependencies',
            'peerDependencies',
            'peerDependenciesMeta',
            'optionalDependencies',
            'devDependencies',
            'overrides',
            'resolutions',
            'workspaces',
            'maintainers',
            'contributors',
            'keywords',
            'config',
            'publishConfig',
            'release',
            'husky',
            'simple-git-hooks',
            'lint-staged',
            'eslintConfig',
            'stylelint',
            'prettier',
            'ava',
            'stackblitz',
            'volta',
          ],
        },
        {
          pathPattern: '^exports.*$',
          order: [
            'default',
            'import',
            'require',
            'types',
          ],
        },
        {
          pathPattern: '^(?:dev|peer|optional|bundled)?[Dd]ependencies(Meta)?$',
          order: {
            type: 'asc',
          },
        },
        {
          pathPattern: '^(?:resolutions|overrides|pnpm.overrides)$',
          order: {
            type: 'asc',
          },
        },
        {
          pathPattern: '^contributors.*$',
          order: [
            'name',
            'email',
            'url',
          ],
        },
        {
          pathPattern: '^(?:husky|simple-git-hooks)$',
          order: [
            'pre-commit',
            'prepare-commit-msg',
            'commit-msg',
            'post-commit',
            'pre-rebase',
            'post-rewrite',
            'post-checkout',
            'post-merge',
            'pre-push',
            'pre-auto-gc',
          ],
        },
      ],
    },
  },
  {
    name: buildConfigName(MAIN_SCOPES.JSON, `${SUB_SCOPES.RULES}-sort-composer-json`),
    files: ['**/composer.json'],
    rules: {
      'jsonc/sort-keys': [
        'error',
        {
          pathPattern: '^$',
          order: [
            '$schema',
            'name',
            'version',
            'time',
            'description',
            '_comment',
            'license',
            'authors',
            'abandoned',
            'homepage',
            'readme',
            'support',
            'funding',
            'type',
            'scripts',
            'bin',
            'require',
            'require-dev',
            'replace',
            'provide',
            'conflict',
            'suggest',
            'autoload',
            'autoload-dev',
            'include-path',
            'target-dir',
            'extra',
            'minimum-stability',
            'prefer-stable',
            'config',
            'repositories',
            'archive',
            'keywords',
          ],
        },
        {
          pathPattern: '^authors.*$',
          order: [
            'name',
            'role',
            'email',
            'homepage',
          ],
        },
        {
          pathPattern: '^require|require-dev|replace|provide|conflict|suggest$',
          order: [
            {
              keyPattern: '^php$',
            },
            {
              keyPattern: '^ext-(.+)$',
            },
            {
              order: {
                type: 'asc',
              },
            },
          ],
        },
        {
          pathPattern: 'autoload(-dev)?',
          order: [
            'psr-0',
            'psr-4',
            'classmap',
            'files',
            'exclude-from-classmap',
          ],
        },
        {
          pathPattern: '^extra$',
          order: {
            type: 'asc',
          },
        },
        {
          pathPattern: '^config$',
          order: [
            'platform',
            'platform-check',
            'allow-missing-requirements',
            'bin-compat',
            'bump-after-update',
            'classmap-authoritative',
            'discard-changes',
            'lock',
            'notify-on-install',
            'preferred-install',
            'sort-packages',
            'apcu-autoloader',
            'autoloader-suffix',
            'prepend-autoloader',
            'optimize-autoloader',
            'process-timeout',
            'store-auths',
            'allow-plugins',
            'use-include-path',
            'use-parent-dir',
            'archive-format',
            'archive-dir',
            'vendor-dir',
            'bin-dir',
            'data-dir',
            'cache-dir',
            'cache-files-dir',
            'cache-repo-dir',
            'cache-vcs-dir',
            'cache-files-ttl',
            'cache-files-maxsize',
            'cache-read-only',
            'audit',
            'htaccess-protect',
            'disable-tls',
            'secure-http',
            'cafile',
            'capath',
            'http-basic',
            'bearer',
            'use-github-api',
            'github-expose-hostname',
            'github-protocols',
            'bitbucket-oauth',
            'secure-svn-domains',
            'github-oauth',
            'github-domains',
            'gitlab-token',
            'gitlab-protocol',
            'gitlab-oauth',
            'gitlab-domains',
          ],
        },
      ],
    },
  },
  {
    name: buildConfigName(MAIN_SCOPES.JSON, `${SUB_SCOPES.RULES}-sort-tsconfig-json`),
    files: TSCONFIG_FILES,
    rules: {
      'jsonc/sort-array-values': ['error', {
        pathPattern: '^(?:files|include|exclude)$',
        order: {
          type: 'asc',
        },
      }],
      // @see https://www.typescriptlang.org/tsconfig/
      'jsonc/sort-keys': [
        'error',
        {
          pathPattern: '^$',
          order: [
            '$schema',
            'extends',
            'references',
            'files',
            'include',
            'exclude',
            'compilerOptions',
            'watchOptions',
            'typeAcquisition',
          ],
        },
        {
          pathPattern: '^compilerOptions$',
          order: [
            // Language and Environment
            'emitDecoratorMetadata',
            'experimentalDecorators',
            'jsx',
            'jsxFactory',
            'jsxFragmentFactory',
            'jsxImportSource',
            'lib',
            'libReplacement',
            'moduleDetection',
            'noLib',
            'reactNamespace',
            'target',
            'useDefineForClassFields',

            // Projects
            'incremental',
            'composite',
            'tsBuildInfoFile',
            'disableReferencedProjectLoad',
            'disableSolutionSearching',
            'disableSourceOfProjectReferenceRedirect',

            // Modules
            'allowArbitraryExtensions',
            'allowImportingTsExtensions',
            'allowUmdGlobalAccess',
            'baseUrl',
            'customConditions',
            'module',
            'moduleResolution',
            'moduleSuffixes',
            'noResolve',
            'noUncheckedSideEffectImports',
            'paths',
            'resolveJsonModule',
            'resolvePackageJsonExports',
            'resolvePackageJsonImports',
            'rewriteRelativeImportExtensions',
            'rootDir',
            'rootDirs',
            'typeRoots',
            'types',

            // Emit
            'declaration',
            'declarationDir',
            'declarationMap',
            'downlevelIteration',
            'emitBOM',
            'emitDeclarationOnly',
            'importHelpers',
            'inlineSourceMap',
            'inlineSources',
            'mapRoot',
            'newLine',
            'noEmit',
            'noEmitHelpers',
            'noEmitOnError',
            'outDir',
            'outFile',
            'preserveConstEnums',
            'removeComments',
            'sourceMap',
            'sourceRoot',
            'stripInternal',

            // Completeness
            'skipDefaultLibCheck',
            'skipLibCheck',

            // Output Formatting
            'noErrorTruncation',
            'preserveWatchOutput',
            'pretty',

            // Watch Options
            'assumeChangesOnlyAffectDirectDependencies',

            // JavaScript Support
            'allowJs',
            'checkJs',
            'maxNodeModuleJsDepth',

            // Interop Constraints
            'allowSyntheticDefaultImports',
            'erasableSyntaxOnly',
            'esModuleInterop',
            'forceConsistentCasingInFileNames',
            'isolatedDeclarations',
            'isolatedModules',
            'preserveSymlinks',
            'verbatimModuleSyntax',

            // Type Checking
            'allowUnreachableCode',
            'allowUnusedLabels',
            'alwaysStrict',
            'exactOptionalPropertyTypes',
            'noFallthroughCasesInSwitch',
            'noImplicitAny',
            'noImplicitOverride',
            'noImplicitReturns',
            'noImplicitThis',
            'noPropertyAccessFromIndexSignature',
            'noUncheckedIndexedAccess',
            'noUnusedLocals',
            'noUnusedParameters',
            'strict',
            'strictBindCallApply',
            'strictBuiltinIteratorReturn',
            'strictFunctionTypes',
            'strictNullChecks',
            'strictPropertyInitialization',
            'useUnknownInCatchVariables',

            // Compiler Diagnostics
            'diagnostics',
            'explainFiles',
            'extendedDiagnostics',
            'generateCpuProfile',
            'generateTrace',
            'listEmittedFiles',
            'listFiles',
            'noCheck',
            'traceResolution',

            // Backwards Compatibility
            'charset',
            'importsNotUsedAsValues',
            'keyofStringsOnly',
            'noImplicitUseStrict',
            'noStrictGenericChecks',
            'out',
            'preserveValueImports',
            'suppressExcessPropertyErrors',
            'suppressImplicitAnyIndexErrors',

            // Editor Support
            'disableSizeLimit',
            'plugins',
          ],
        },
        {
          pathPattern: '^watchOptions$',
          order: [
            'watchFile',
            'watchDirectory',
            'fallbackPolling',
            'synchronousWatchDirectory',
            'excludeDirectories ',
            'excludeFiles',
          ],
        },
        {
          pathPattern: '^typeAcquisition$',
          order: [
            'enable',
            'include',
            'exclude',
            'disableFilenameBasedTypeAcquisition',
          ],
        },
      ],
    },
  },
];

export const json = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginJson],
    optional: [pluginJsonc, parserJsonc],
  } = await resolvePackages(MODULES.json);

  if (!pluginJson) {
    return [];
  }

  const createRulesConfig = (
    language: 'json' | 'jsonc' | 'json5',
  ): Config => ({
    name: buildConfigName(MAIN_SCOPES.JSON, `${SUB_SCOPES.RULES}-${language}`),
    language: `json/${language}`,
    files: LANGUAGE_TO_GLOB_MAP[language],
    ...(language === 'json'
      ? {
        ignores: JSON_FILES_TO_TREAT_AS_JSONC,
      }
      : undefined),
    rules: {
      ...pluginJson.configs.recommended.rules,
      ...pluginJsonc ? extractAllRules(pluginJsonc.configs[`flat/recommended-with-${language}`]) : undefined,
      ...(pluginJsonc
        ? {
          'jsonc/array-bracket-newline': ['error', 'consistent'],
          'jsonc/array-bracket-spacing': 'error',
          'jsonc/comma-style': 'error',
          'jsonc/indent': ['error', INDENT],
          'jsonc/key-spacing': 'error',
          'jsonc/no-irregular-whitespace': 'error',
          'jsonc/no-octal-escape': 'error',
          'jsonc/object-curly-newline': 'error',
          'jsonc/object-curly-spacing': ['error', 'always'],
          'jsonc/object-property-newline': 'error',
          'jsonc/quotes': ['error', 'double'],
        }
        : undefined),
    },
  });

  const plugins: Config['plugins'] = {
    json: <ESLint.Plugin><unknown>pluginJson,
  };

  let jsoncSortConfigs: Config[] = [];

  if (pluginJsonc) {
    plugins['jsonc'] = <ESLint.Plugin><unknown>pluginJsonc;
    jsoncSortConfigs = getJsoncSortConfigs();
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.JSON, SUB_SCOPES.SETUP),
      plugins,
    },
    parserJsonc
      ? {
        name: buildConfigName(MAIN_SCOPES.JSON, SUB_SCOPES.PARSER),
        files: [GLOB_JSON, GLOB_JSONC, GLOB_JSON5],
        languageOptions: {
          parser: parserJsonc,
        },
      }
      : undefined,
    createRulesConfig('json'),
    createRulesConfig('jsonc'),
    ...jsoncSortConfigs,
    createRulesConfig('json5'),
  ].filter(Boolean);
};
