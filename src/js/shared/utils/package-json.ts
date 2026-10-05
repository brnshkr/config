/**
 * @internal @brnshkr/config
 */

import manifest from '#package.json' with { type: 'json' };

import type { Maybe } from '#shared/types/core.ts';

export const packageFullName = manifest.name;
export const packageVersion = manifest.version;

export const packageOrganizationInternal = <Maybe<'brnshkr'>>manifest.name
  .split('/', 1)
  .at(0)
  ?.replace(/^@/v, '');

if (packageOrganizationInternal === undefined || packageOrganizationInternal.length === 0) {
  throw new Error('Failed to read package organization from package.json file.');
}

export const packageOrganization = packageOrganizationInternal;

export const packageOrganizationUpper = <Uppercase<typeof packageOrganizationInternal>>(
  packageOrganizationInternal.toUpperCase()
);
