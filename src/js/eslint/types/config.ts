import type { TSESLint } from '@typescript-eslint/utils';
import type { Linter } from 'eslint';
import type { ResolvableFlatConfig } from 'eslint-flat-config-utils';
import type { ArrayElement } from 'type-fest';
import type tsEslintType from 'typescript-eslint';
import type { RuleOptions } from './declarations/typegen';

export type { ConfigNames } from './declarations/typegen';
export type Config = Linter.Config<Linter.RulesRecord & RuleOptions>;
export type ResolvableConfig = ResolvableFlatConfig<Config>;
export type TsEslintParser = typeof tsEslintType.parser;
export type TsEslintConfigArray = TSESLint.FlatConfig.ConfigArray;
export type TsEslintParserOptions = NonNullable<ArrayElement<TsEslintConfigArray>['languageOptions']>['parserOptions'];
