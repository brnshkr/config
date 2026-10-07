/**
 * @internal @brnshkr/config/commitlint
 */

import type { RuleConfigSeverity } from '@commitlint/types';

// eslint-disable-next-line ts/no-unsafe-enum-assignment -- Avoid a runtime dependency on commitlint's optional types package
export const ERROR: RuleConfigSeverity.Error = 2;
