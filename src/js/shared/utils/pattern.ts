/**
 * @internal @brnshkr/config
 */

export type PatternFlags = `${'' | 'd'}${'' | 'g'}${'' | 'i'}${'' | 'm'}${'' | 's'}${'u' | 'v'}${'' | 'y'}`;

type PatternValue = string | readonly string[];

const escapePatternValue = (value: PatternValue): string => (typeof value === 'string'
  ? RegExp.escape(value)
  : value.map((entry) => RegExp.escape(entry)).join('|'));

export const createPattern = (flags: PatternFlags) => (
  literals: TemplateStringsArray,
  ...values: PatternValue[]
): RegExp => {
  const source = String.raw({ raw: literals.raw }, ...values.map((value) => escapePatternValue(value)));

  // eslint-disable-next-line security/detect-non-literal-regexp -- Every interpolated value is escaped
  return new RegExp(source, flags);
};
