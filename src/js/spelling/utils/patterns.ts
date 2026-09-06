/**
 * @internal @brnshkr/config/spelling
 */

import { objectEntries } from '../../shared/utils/object';

import type { SpellingPattern, SpellingSettings } from '../types/options';

export const buildPatterns = (settings: SpellingSettings): SpellingPattern[] => {
  const stemSuffixGroup = `(?:${settings.stemSuffixes.join('|')})`;

  return [
    ...objectEntries(settings.britishSpellings).map(([britishSpelling, americanSpelling]) => (<const>{
      pattern: new RegExp(String.raw`\b${britishSpelling}\w*`, 'giv'),
      toAmericanSpelling: (word) => americanSpelling + word.slice(britishSpelling.length),
    } satisfies SpellingPattern)),
    ...settings.britishStems.map((britishStem) => (<const>{
      pattern: new RegExp(String.raw`\b${britishStem}${stemSuffixGroup}\b`, 'giv'),
      toAmericanSpelling: (word) => `${britishStem.slice(0, -1)}z${word.slice(britishStem.length)}`,
    } satisfies SpellingPattern)),
  ];
};
