/**
 * @internal @brnshkr/config
 */

import { resolvePackagesSharedSynchronously } from '#shared/utils/module.ts';
import { PATTERN_PACKAGES } from '#shared/utils/package-resolvers.ts';

import type { Maybe } from '#shared/types/core.ts';
import type { PatternFlags } from '#shared/utils/pattern.ts';

interface PatternParts {
  source: string;
  flags: string;
}

const readPatternParts = (pattern: RegExp | string, bareSourceFlags?: PatternFlags): Maybe<PatternParts> => {
  if (pattern instanceof RegExp) {
    return {
      source: pattern.source,
      flags: pattern.flags,
    };
  }

  const groups = /^(?<delimiter>[^\w\\])(?<source>.*)\k<delimiter>(?<flags>[A-Za-z]*)$/sv.exec(pattern)?.groups;

  if (groups !== undefined) {
    return {
      source: groups['source'] ?? '',
      flags: groups['flags'] ?? '',
    };
  }

  return bareSourceFlags === undefined
    ? undefined
    : {
      source: pattern,
      flags: bareSourceFlags,
    };
};

/**
 * @throws {Error}
 */
const assertLinearBacktracking = ({ source, flags }: PatternParts): void => {
  const [regexpp, scslre] = resolvePackagesSharedSynchronously(<const>{
    name: 'patterns',
    packages: {
      requiredAll: [
        PATTERN_PACKAGES.REGEXPP,
        PATTERN_PACKAGES.SCSLRE,
      ],
    },
  }, 'requiredAll');

  if (!regexpp || !scslre) {
    throw new Error(`Unable to check the configured pattern "${source}" for super-linear backtracking.`);
  }

  const parser = new regexpp.RegExpParser();
  const parsedFlags = parser.parseFlags(flags);

  const pattern = parser.parsePattern(source, 0, source.length, {
    unicode: parsedFlags.unicode,
    unicodeSets: parsedFlags.unicodeSets,
  });

  if (scslre.analyse({ pattern, flags: parsedFlags }).reports.length > 0) {
    throw new Error(`The configured pattern "${source}" can backtrack super-linearly.`);
  }
};

export const compileConfiguredPattern = (
  pattern: RegExp | string,
  bareSourceFlags?: PatternFlags,
): Maybe<RegExp> => {
  const patternParts = readPatternParts(pattern, bareSourceFlags);

  if (patternParts === undefined) {
    return undefined;
  }

  const matchingParts = {
    source: patternParts.source,
    flags: patternParts.flags.replaceAll(/[gy]/gv, ''),
  };

  assertLinearBacktracking(matchingParts);

  // eslint-disable-next-line security/detect-non-literal-regexp -- Checked for super-linear backtracking above
  return new RegExp(matchingParts.source, matchingParts.flags);
};
