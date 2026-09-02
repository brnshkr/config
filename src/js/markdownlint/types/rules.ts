/**
 * @internal @brnshkr/config/markdownlint
 */

import type {
  FencedCodeFenceLengthConfig,
  HeadingSentenceCaseConfig,
  ListItemMarkerSpaceConfig,
  SetextHeadingBlankLinesConfig,
} from '@hongminhee/markdownlint-rules';

export type TableStyle = 'aligned' | 'compact' | 'tight' | 'any';
export type SearchScope = 'all' | 'code' | 'text';

export interface SearchReplaceRule {
  name: string;
  message: string;
  information?: string;
  search?: string | string[];
  searchPattern?: string | string[];
  replace?: string | null | (string | null)[];
  searchScope?: SearchScope;
}

// NOTICE: no plugin ships a JSON Schema, so none of these can be generated the way the built-in rules are
export interface CustomRules {
  'fenced-code-fence-length': boolean | FencedCodeFenceLengthConfig;
  'heading-sentence-case': boolean | HeadingSentenceCaseConfig;
  'list-item-marker-space': boolean | ListItemMarkerSpaceConfig;
  'no-default-alt-text': boolean;
  'no-empty-alt-text': boolean;
  'no-generic-link-text': boolean;
  'no-trailing-slash-in-links': boolean;
  'reference-link-section-placement': boolean;
  'relative-links': boolean | Partial<Record<'root_path' | 'fragment-index-divider', string>>;
  'search-replace': boolean | {
    rules: SearchReplaceRule[];
  };
  'setext-heading-blank-lines': boolean | SetextHeadingBlankLinesConfig;
  'table-format': boolean
    | Partial<Record<'style', TableStyle> & Record<'aligned_delimiter' | 'fix' | 'fixApplicator', boolean>>;
}
