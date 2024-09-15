import { getConfig } from '../src/js/eslint';
import { log } from '../src/js/shared/utils/log';

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

export default getConfig(undefined, {
  rules: {
    // NOTICE: This rule has quite a significant performance impact so we turn it off in the editor
    'import/no-cycle': isInEditor ? 'off' : 'error',
  },
}, {
  files: [
    'scripts/*',
  ],
  rules: {
    'no-magic-numbers': 'off',
    'import/no-extraneous-dependencies': 'off',
  },
}, {
  files: [
    '**/tests/**/fixtures/**',
  ],
  rules: {
    'import/unambiguous': 'off',
    'unicorn/no-empty-file': 'off',
    'yaml/file-extension': 'off',
  },
}, {
  files: [
    'src/js/eslint/index.ts',
    'src/js/stylelint/index.ts',
  ],
  rules: {
    complexity: 'off',
    'max-lines-per-function': 'off',
    'max-statements': 'off',
  },
}, {
  files: [
    'src/js/eslint/configs/*',
    'src/js/stylelint/configs/*',
  ],
  rules: {
    'max-lines': 'off',
    'max-lines-per-function': 'off',
    'import/max-dependencies': 'off',
  },
}, {
  files: [
    'src/js/eslint/utils/module.ts',
    'src/js/shared/utils/module.ts',
  ],
  rules: {
    complexity: 'off',
    'max-lines-per-function': 'off',
    'import/max-dependencies': 'off',
  },
});
