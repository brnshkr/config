/**
 * @internal @brnshkr/config
 */

import { createPattern } from '#shared/utils/pattern.ts';
import { getLiteralsAllowedIn } from '#spelling/utils/settings.ts';

import type {
  AllowedLiteral,
  Allowlist,
  SpellingFinding,
  SpellingPattern,
} from '#spelling/types/options.ts';

const maskAllowedLiterals = (
  line: string,
  allowedLiterals: AllowedLiteral[],
  lineNumber: number,
): string => {
  let maskedLine = line;

  for (const { text, lineNumbers } of allowedLiterals) {
    if (lineNumbers === undefined || lineNumbers.includes(lineNumber)) {
      maskedLine = maskedLine.replaceAll(
        createPattern('giu')`${text}`,
        (matchedText) => '.'.repeat(matchedText.length),
      );
    }
  }

  return maskedLine;
};

export const findInFile = (
  fileContents: string,
  filePath: string,
  patterns: SpellingPattern[],
  allowlist: Allowlist,
): SpellingFinding[] => {
  const allowedLiterals = getLiteralsAllowedIn(allowlist, filePath);
  const findings: SpellingFinding[] = [];

  for (const [index, line] of fileContents.split('\n').entries()) {
    const lineNumber = index + 1;
    const maskedLine = maskAllowedLiterals(line, allowedLiterals, lineNumber);

    for (const { pattern, toAmericanSpelling } of patterns) {
      for (const [word] of maskedLine.matchAll(pattern)) {
        findings.push({
          path: filePath,
          line: lineNumber,
          word,
          suggestion: toAmericanSpelling(word),
        });
      }
    }
  }

  return findings;
};
