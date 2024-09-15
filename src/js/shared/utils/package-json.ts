// eslint-disable-next-line brnshkr/require-import-attributes -- Allow imported json properties to get inlined
import { name } from '../../../../package.json';

import type { Maybe } from '../types/core';

export {
  name as packageFullName,
  version as packageVersion,
} from '../../../../package.json';

export const packageOrganizationInternal = <Maybe<'brnshkr'>>name
  .split('/')
  .at(0)
  ?.replace(/^@/v, '');

if (packageOrganizationInternal === undefined || packageOrganizationInternal.length === 0) {
  throw new Error('Failed to read package organization from package.json file.');
}

export const packageOrganization = packageOrganizationInternal;

export const packageOrganizationUpper = <Uppercase<typeof packageOrganizationInternal>>(
  packageOrganizationInternal.toUpperCase()
);
