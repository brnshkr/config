import { packageOrganizationUpper } from '../../shared/utils/package-json';

export const MAIN_SCOPES = <const>{
  [packageOrganizationUpper]: 'builtin',
  COMMENTS: 'comments',
  CSS: 'css',
  IGNORES: 'ignores',
  IMPORT: 'import',
  JAVASCRIPT: 'javascript',
  JSDOC: 'jsdoc',
  JSON: 'json',
  MARKDOWN: 'markdown',
  NODE: 'node',
  OVERRIDES: 'overrides',
  PERFECTIONIST: 'perfectionist',
  REGEXP: 'regexp',
  STYLE: 'style',
  SVELTE: 'svelte',
  TEST: 'test',
  TOML: 'toml',
  TYPESCRIPT: 'typescript',
  UNICORN: 'unicorn',
  USERLAND: 'userland',
  YAML: 'yaml',
};

export type MainScope = typeof MAIN_SCOPES[keyof typeof MAIN_SCOPES];

export const SUB_SCOPES = <const>{
  BASE: 'base',
  DEVELOPMENT: 'development',
  EXAMPLES: 'examples',
  GIT: 'git',
  GLOBAL: 'global',
  PARSER: 'parser',
  PROCESSOR: 'processor',
  RULES: 'rules',
  SETUP: 'setup',
  UNNAMED: 'unnamed',
};

type SubScopeRaw = typeof SUB_SCOPES[keyof typeof SUB_SCOPES];

type SuffixableSubScope = typeof SUB_SCOPES.SETUP
  | typeof SUB_SCOPES.PARSER
  | typeof SUB_SCOPES.RULES
  | typeof SUB_SCOPES.UNNAMED;

export type SubScope = Exclude<SubScopeRaw, typeof SUB_SCOPES.UNNAMED>
  | `${SuffixableSubScope}-${string}`
  | `${Exclude<MainScope, typeof MAIN_SCOPES.OVERRIDES>}/${string}`
  | `${typeof SUB_SCOPES.EXAMPLES}/${typeof SUB_SCOPES.SETUP | typeof SUB_SCOPES.PROCESSOR}`;
