import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_MD } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';
import type { MarkdownOptions } from '../types/options';

const extractRelevantValues = <
  TIdentifier extends keyof TConfig,
  TConfig extends Config,
>(
  identifier: TIdentifier,
  configs: TConfig[],
  key: string,
): NonNullable<TConfig[TIdentifier]> => {
  for (const config of configs) {
    if (config.name === `markdown/${key}`
      && config[identifier] !== undefined
      && config[identifier] !== null) {
      return config[identifier];
    }
  }

  throw new Error(`Expected key "${key}" to be contained in given config.`);
};

export const markdown = async (options?: Partial<MarkdownOptions>): Promise<Config[]> => {
  const {
    requiredAll: [pluginMarkdown, eslintMergeProcessors],
  } = await resolvePackages(MODULES.markdown);

  if (!pluginMarkdown || !eslintMergeProcessors) {
    return [];
  }

  const language = options?.language ?? 'markdown/commonmark';
  const frontmatter = options?.frontmatter ?? false;
  const { mergeProcessors, processorPassThrough } = eslintMergeProcessors;

  return [
    {
      name: buildConfigName(MAIN_SCOPES.MARKDOWN, SUB_SCOPES.SETUP),
      plugins: {
        markdown: pluginMarkdown,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.MARKDOWN, SUB_SCOPES.PROCESSOR),
      files: [GLOB_MD],
      processor: mergeProcessors([
        pluginMarkdown.processors.markdown,
        processorPassThrough,
      ]),
    },
    {
      name: buildConfigName(MAIN_SCOPES.MARKDOWN, SUB_SCOPES.RULES),
      files: [GLOB_MD],
      language,
      languageOptions: {
        frontmatter,
      },
      rules: {
        ...extractRelevantValues('rules', pluginMarkdown.configs.recommended, 'recommended'),
        'markdown/no-bare-urls': 'error',
        'markdown/no-duplicate-headings': ['error', {
          checkSiblingsOnly: true,
        }],
        'markdown/no-html': ['error', {
          allowed: [
            'a',
            'br',
            'dd',
            'del',
            'details',
            'div',
            'dl',
            'dt',
            'h1',
            'img',
            'ins',
            'kbd',
            'p',
            'q',
            'rp',
            'rt',
            'ruby',
            'samp',
            'sub',
            'summary',
            'sup',
            'var',
          ],
        }],
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.MARKDOWN, `${SUB_SCOPES.RULES}-code-blocks`),
      files: extractRelevantValues('files', pluginMarkdown.configs.processor, 'recommended/code-blocks'),
      languageOptions: extractRelevantValues('languageOptions', pluginMarkdown.configs.processor, 'recommended/code-blocks'),
      rules: {
        ...extractRelevantValues('rules', pluginMarkdown.configs.processor, 'recommended/code-blocks'),
        'no-alert': 'off',
        'no-console': 'off',
        'no-inline-comments': 'off',
        'import/no-default-export': 'off',
        'import/unambiguous': 'off',
        'node/no-missing-import': 'off',
        'ts/no-redeclare': 'off',
        'ts/no-unused-vars': 'off',
        'unused/no-unused-imports': 'off',
        'unused/no-unused-vars': 'off',
      },
    },
  ];
};
