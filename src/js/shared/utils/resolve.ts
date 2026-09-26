/**
 * @internal @brnshkr/config
 */

import { fileURLToPath } from 'node:url';

import type { Maybe } from '../types/core';

export const resolveModulePath = (specifier: string): Maybe<string> => {
  try {
    return fileURLToPath(import.meta.resolve(specifier));
  } catch {
    return undefined;
  }
};

export const isPackageInstalled = (name: string): boolean => (
  resolveModulePath(`${name}/package.json`) ?? resolveModulePath(name)
) !== undefined;
