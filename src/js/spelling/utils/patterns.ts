/**
 * @internal @brnshkr/config/spelling
 */

import { objectEntries } from '../../shared/utils/object';
import { createPattern } from '../../shared/utils/pattern';

import type { SpellingPattern, SpellingSettings } from '../types/options';

export const buildPatterns = (settings: SpellingSettings): SpellingPattern[] => [
  ...objectEntries(settings.britishSpellings).map(([britishSpelling, americanSpelling]) => (<const>{
    pattern: createPattern('giv')`\b${britishSpelling}\w*`,
    toAmericanSpelling: (word) => americanSpelling + word.slice(britishSpelling.length),
  } satisfies SpellingPattern)),
  ...settings.britishStems.map((britishStem) => (<const>{
    pattern: createPattern('giv')`\b${britishStem}(?:${settings.stemSuffixes})\b`,
    toAmericanSpelling: (word) => `${britishStem.slice(0, -1)}z${word.slice(britishStem.length)}`,
  } satisfies SpellingPattern)),
];
