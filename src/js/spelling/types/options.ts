/**
 * @internal @brnshkr/config/spelling
 */

import type { Maybe } from '../../shared/types/core';

export interface SpellingSettings {
  fileExtensions: string[];
  fileNames: string[];
  ignorePatterns: string[];
  britishSpellings: Record<string, string>;
  britishStems: string[];
  stemSuffixes: string[];
  allowlist?: Record<string, string[]>;
}

export interface AllowedLiteral {
  text: string;
  lineNumbers: Maybe<number[]>;
}

export type Allowlist = Record<string, AllowedLiteral[]>;

export interface SpellingPattern {
  pattern: RegExp;
  toAmericanSpelling: (word: string) => string;
}

export interface SpellingOptions {
  rootDirectory?: string;
  configPath?: string;
  paths?: string[];
}

export interface SpellingFinding {
  path: string;
  line: number;
  word: string;
  suggestion: string;
}
