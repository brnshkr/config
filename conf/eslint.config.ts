import { getConfig } from '../src/js/eslint';
import { log } from '../src/js/shared/utils/log';
import { packageOrganization } from '../src/js/shared/utils/package-json';

/* eslint-disable ts/no-unnecessary-condition -- Optional chaining is required here since some IDE runtimes _might_ still not define import.meta.env */
const isInEditor = Boolean(import.meta.env?.['VSCODE_PID']
  ?? import.meta.env?.['VSCODE_CWD']
  ?? import.meta.env?.['JETBRAINS_IDE']
  ?? import.meta.env?.['VIM']
  ?? import.meta.env?.['NVIM']);
/* eslint-enable ts/no-unnecessary-condition -- Restore rule */

if (isInEditor) {
  log('log', `ESLint is running in an editor.`);
}

export default getConfig({
  ignores: [
    'src/js/eslint/types/declarations/typegen.d.ts',
    'src/js/markdownlint/types/declarations/typegen.d.ts',
  ],
}, {
  rules: {
    // NOTICE: This rule has quite a significant performance impact so we turn it off in the editor
    'import/no-cycle': isInEditor ? 'off' : 'error',
    'test/expect-expect': ['error', {
      assertFunctionNames: [
        'expect',
        'runJsRuleTests',
        'runRuleTests',
        'runTsRuleTests',
        'runTypeAwareRuleTests',
        'snapshotConfigs',
      ],
    }],
  },
}, {
  files: [
    'scripts/*',
  ],
  rules: {
    'no-magic-numbers': 'off',
  },
}, {
  files: [
    'scripts/typegen.ts',
  ],
  rules: {
    [<const>`${packageOrganization}/internal-usage`]: ['error', {
      allowedDeclaringNamespaces: [
        '@brnshkr/config/eslint',
      ],
    }],
    'import/no-extraneous-dependencies': 'off',
  },
}, {
  files: [
    'tests/**/*.?(c|m)[jt]s?(x)',
  ],
  ignores: [
    '**/tests/**/fixtures/**',
  ],
  rules: {
    [<const>`${packageOrganization}/internal-usage`]: ['error', {
      allowedCallingNamespaces: [
        '@brnshkr/config/tests',
      ],
    }],
  },
}, {
  files: [
    '**/tests/**/fixtures/**',
  ],
  rules: {
    'func-style': 'off',
    'import/unambiguous': 'off',
    'unicorn/no-empty-file': 'off',
    'yaml/file-extension': 'off',
  },
}, {
  files: [
    'src/js/eslint/index.ts',
    'src/js/markdownlint/index.ts',
    'src/js/stylelint/index.ts',
  ],
  rules: {
    complexity: 'off',
    'max-lines-per-function': 'off',
    'max-statements': 'off',
  },
}, {
  files: [
    'src/js/eslint/configs/**',
    'src/js/markdownlint/configs/**',
    'src/js/stylelint/configs/**',
  ],
  rules: {
    'max-lines': 'off',
    'max-lines-per-function': 'off',
    'import/max-dependencies': 'off',
  },
}, {
  files: [
    'src/js/shared/utils/module.ts',
  ],
  rules: {
    complexity: 'off',
    'max-lines-per-function': 'off',
  },
}, {
  files: [
    'src/js/shared/utils/package-resolvers.ts',
  ],
  rules: {
    'import/max-dependencies': 'off',
  },
}, {
  files: [
    'src/js/eslint/configs/builtin/resolvable-doc-reference.ts',
    'tests/js/eslint-rules/resolvable-doc-reference.test.ts',
  ],
  rules: {
    'unicorn/prevent-abbreviations': 'off',
  },
}, {
  files: [
    'docs/**/*.md/**',
  ],
  rules: {
    [<const>`${packageOrganization}/boolish-prefix`]: 'off',
    [<const>`${packageOrganization}/interface-suffix`]: 'off',
    [<const>`${packageOrganization}/require-import-attributes`]: 'off',
    [<const>`${packageOrganization}/resolvable-doc-reference`]: 'off',
    'class-methods-use-this': 'off',
    'max-classes-per-file': 'off',
    'import/export': 'off',
    'import/extensions': 'off',
    'import/no-duplicates': 'off',
    'import/no-unresolved': 'off',
    'import/order': 'off',
    'jsdoc/no-undefined-types': 'off',
    'jsdoc/require-param': 'off',
    'jsdoc/require-returns-check': 'off',
    'perfectionist/sort-heritage-clauses': 'off',
    'style/padding-line-between-statements': 'off',
    'ts/no-empty-function': 'off',
    'ts/no-empty-object-type': 'off',
    'ts/no-extraneous-class': 'off',
    'unicorn/prevent-abbreviations': 'off',
  },
});
