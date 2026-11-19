/**
 * @internal @brnshkr/config
 */

import { getEnvironmentValue } from '#shared/utils/environment.ts';

const getConfiguredDirectory = (variableName: string, defaultDirectory: string): string => {
  const configuredDirectory = (getEnvironmentValue(variableName) ?? '').replace(/^\.\//v, '');

  return configuredDirectory === '' ? defaultDirectory : configuredDirectory;
};

export const getConfigDirectory = (): string => getConfiguredDirectory('BRNSHKR_CONFIG_DIR', 'conf');
export const getCacheDirectory = (): string => getConfiguredDirectory('BRNSHKR_CACHE_DIR', '.cache');
export const getLocalDirectory = (): string => getConfiguredDirectory('BRNSHKR_LOCAL_DIR', '.local');
