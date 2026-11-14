/**
 * @internal @brnshkr/config
 */

import manifest from '#package.json' with { type: 'json' };
import { readOwnValue } from '#shared/utils/object.ts';

import type { Maybe } from '#shared/types/core.ts';

const manifestVersion = readOwnValue(<Readonly<Record<string, unknown>>>manifest, 'version');

export const packageFullName = manifest.name;

export const packageVersion = typeof manifestVersion === 'string'
  ? manifestVersion
  : '0.0.0-dev';

// eslint-disable-next-line ts/no-unsafe-type-assertion -- The package name is `@brnshkr/config`
export const packageOrganizationInternal = <Maybe<'brnshkr'>>manifest.name
  .split('/', 1)
  .at(0)
  ?.replace(/^@/v, '');

if (packageOrganizationInternal === undefined || packageOrganizationInternal.length === 0) {
  throw new Error('Package organization not found in "package.json".');
}

export const packageOrganization = packageOrganizationInternal;

// eslint-disable-next-line ts/no-unsafe-type-assertion -- Uppercasing a literal keeps it a literal
export const packageOrganizationUpper = <Uppercase<typeof packageOrganizationInternal>>(
  packageOrganizationInternal.toUpperCase()
);
