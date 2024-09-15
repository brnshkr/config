export const joinAsQuotedList = (
  strings: string[],
  type: 'conjunction' | 'disjunction' = 'conjunction',
): string => (strings.length > 1
  ? `"${strings.slice(0, -1).join('", "')}" ${type === 'conjunction' ? 'and' : 'or'} "${strings.slice(-1).join('')}"`
  : ({ 0: '', 1: `"${strings[0] ?? ''}"` }[strings.length] ?? ''));
