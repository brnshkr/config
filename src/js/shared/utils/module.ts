/**
 * @internal @brnshkr/config
 */

import { isPackageExists } from 'local-pkg';

import { log } from './log';
import { objectEntries } from './object';

import {
  COMMITLINT_PACKAGE_RESOLVERS,
  ESLINT_PACKAGE_RESOLVERS,
  MARKDOWNLINT_PACKAGE_RESOLVERS,
  STYLELINT_PACKAGE_RESOLVERS,
  VITEST_PACKAGE_RESOLVERS,
} from './package-resolvers';

import { joinAsQuotedList } from './string';

import type { Maybe, Simplify } from '../types/core';

const PACKAGE_RESOLVERS = <const>{
  ...COMMITLINT_PACKAGE_RESOLVERS,
  ...ESLINT_PACKAGE_RESOLVERS,
  ...MARKDOWNLINT_PACKAGE_RESOLVERS,
  ...STYLELINT_PACKAGE_RESOLVERS,
  ...VITEST_PACKAGE_RESOLVERS,
};

type PackageResolvers = typeof PACKAGE_RESOLVERS;

export type Package = keyof PackageResolvers;

type ResolvedPackage<TPackage extends Package> = ReturnType<PackageResolvers[TPackage]> extends boolean
  ? Awaited<ReturnType<PackageResolvers[TPackage]>>
  : Maybe<Awaited<ReturnType<PackageResolvers[TPackage]>>>;

type ResolvedPackagesImplWithType<
  TModuleInfo extends ModuleInfo,
  TType extends keyof TModuleInfo['packages'],
  TPackages extends TModuleInfo['packages'][TType] = TModuleInfo['packages'][TType],
> = {
  [TKey in keyof TPackages]: TPackages[TKey] extends Package
    ? ResolvedPackage<TPackages[TKey]>
    : never;
};

type ResolvedPackagesImplWithNoType<TModuleInfo extends ModuleInfo> = {
  [TType in keyof TModuleInfo['packages']]: TModuleInfo['packages'][TType] extends object
    ? ResolvedPackagesImplWithType<TModuleInfo, TType>
    : never;
};

export type ResolvedPackages<
  TModuleInfo extends ModuleInfo,
  TType extends Maybe<keyof TModuleInfo['packages']>,
> = TType extends undefined
  ? Simplify<ResolvedPackagesImplWithNoType<TModuleInfo>>
  : Simplify<ResolvedPackagesImplWithType<TModuleInfo, NonNullable<TType>>>;

export interface ModuleInfo<TPackages extends readonly Package[] = readonly Package[]> {
  name: string;
  packages?: {
    optional?: TPackages;
    requiredAll?: TPackages;
    requiredAny?: TPackages;
  };
}

const warnMissingPackages = (
  moduleInfo: ModuleInfo,
  packages: readonly Package[],
  type: 'requiredAll' | 'requiredAny',
): void => {
  log(
    'error',
    `Failed resolving required dependencies for module "${
      moduleInfo.name
    }". Please install ${joinAsQuotedList(
      [...packages],
      type === 'requiredAny' ? 'disjunction' : 'conjunction',
    )} or disable the ${
      moduleInfo.name
    } module in the config.`,
  );

  log('log', `Run \`bun a -D -E ${packages.join(' ')}\` to install.`);
};

const packageCache: Record<string, unknown> = {};

type PackageType = keyof NonNullable<ModuleInfo['packages']>;

const loadPackageAsynchronously = async (thePackage: Package): Promise<unknown> => {
  try {
    return await PACKAGE_RESOLVERS[thePackage]();
  } catch {
    return false;
  }
};

const loadPackageSynchronously = (thePackage: Package): unknown => {
  try {
    return PACKAGE_RESOLVERS[thePackage]();
  } catch {
    return false;
  }
};

const collectPackagesAsynchronously = async (
  moduleInfo: ModuleInfo,
  type: PackageType,
  packages: readonly Package[],
): Promise<unknown[]> => {
  const collectedPackages: unknown[] = [];

  for (const thePackage of packages) {
    // eslint-disable-next-line no-await-in-loop -- Sequential resolving is desired here
    const resolvedPackage = packageCache[thePackage] ?? await loadPackageAsynchronously(thePackage);

    packageCache[thePackage] ??= resolvedPackage;

    if (type === 'requiredAll' && resolvedPackage === false) {
      warnMissingPackages(moduleInfo, packages, type);

      break;
    }

    collectedPackages.push(resolvedPackage === false ? undefined : resolvedPackage);
  }

  return collectedPackages;
};

const collectPackagesSynchronously = (
  moduleInfo: ModuleInfo,
  type: PackageType,
  packages: readonly Package[],
): unknown[] => {
  const collectedPackages: unknown[] = [];

  for (const thePackage of packages) {
    const resolvedPackage = packageCache[thePackage] ?? loadPackageSynchronously(thePackage);

    packageCache[thePackage] ??= resolvedPackage;

    if (type === 'requiredAll' && resolvedPackage === false) {
      warnMissingPackages(moduleInfo, packages, type);

      break;
    }

    collectedPackages.push(resolvedPackage === false ? undefined : resolvedPackage);
  }

  return collectedPackages;
};

export const resolvePackagesSharedAsynchronously = async <
  TModuleInfo extends ModuleInfo,
  TType extends Maybe<keyof TModuleInfo['packages']> = undefined,
>(
  moduleInfo: TModuleInfo,
  type?: TType,
): Promise<ResolvedPackages<TModuleInfo, TType>> => {
  if (moduleInfo.packages === undefined) {
    return <ResolvedPackages<TModuleInfo, TType>>{};
  }

  const resolvedPackages: Record<string, unknown[]> = {};

  const packages = type === undefined
    ? moduleInfo.packages
    : { [type]: moduleInfo.packages[<keyof typeof moduleInfo.packages>type] };

  for (const [currentType, currentPackages] of objectEntries(packages)) {
    if (!currentPackages || currentPackages.length === 0) {
      continue;
    }

    // eslint-disable-next-line no-await-in-loop -- Sequential resolving is desired here
    resolvedPackages[currentType] = await collectPackagesAsynchronously(moduleInfo, currentType, currentPackages);

    if (currentType === 'requiredAny'
      && resolvedPackages[currentType].filter((thePackage) => thePackage !== undefined).length === 0) {
      warnMissingPackages(moduleInfo, currentPackages, currentType);
    }
  }

  return <ResolvedPackages<TModuleInfo, TType>>(type === undefined
    ? resolvedPackages
    : (resolvedPackages[<keyof typeof moduleInfo.packages>type] ?? []));
};

export const resolvePackagesSharedSynchronously = <
  TModuleInfo extends ModuleInfo,
  TType extends Maybe<keyof TModuleInfo['packages']> = undefined,
>(
  moduleInfo: TModuleInfo,
  type?: TType,
): ResolvedPackages<TModuleInfo, TType> => {
  if (moduleInfo.packages === undefined) {
    return <ResolvedPackages<TModuleInfo, TType>>{};
  }

  const resolvedPackages: Record<string, unknown[]> = {};

  const packages = type === undefined
    ? moduleInfo.packages
    : { [type]: moduleInfo.packages[<keyof typeof moduleInfo.packages>type] };

  for (const [currentType, currentPackages] of objectEntries(packages)) {
    if (!currentPackages || currentPackages.length === 0) {
      continue;
    }

    resolvedPackages[currentType] = collectPackagesSynchronously(moduleInfo, currentType, currentPackages);

    if (currentType === 'requiredAny'
      && resolvedPackages[currentType].filter((thePackage) => thePackage !== undefined).length === 0) {
      warnMissingPackages(moduleInfo, currentPackages, currentType);
    }
  }

  return <ResolvedPackages<TModuleInfo, TType>>(type === undefined
    ? resolvedPackages
    : (resolvedPackages[<keyof typeof moduleInfo.packages>type] ?? []));
};

export const doAllPackagesExist = (
  packages: readonly Package[],
): boolean => packages.every((thePackage) => isPackageExists(thePackage));

export const doesAnyPackageExist = (
  packages: readonly Package[],
): boolean => packages.some((thePackage) => isPackageExists(thePackage));

export const isModuleEnabledByDefault = (moduleInfo: ModuleInfo): boolean => {
  const { packages } = moduleInfo;

  if (packages === undefined) {
    return true;
  }

  let isEnabled = false;

  for (const [currentType, currentPackages] of objectEntries(packages)) {
    if (currentType === 'optional') {
      continue;
    }

    if (currentPackages && currentPackages.length > 0) {
      isEnabled = (currentType === 'requiredAll' ? doAllPackagesExist : doesAnyPackageExist)(currentPackages);
    }

    if (isEnabled) {
      break;
    }
  }

  return isEnabled;
};

export type PackageResolver<TPackage extends Package> = <
  TModuleInfo extends ModuleInfo<readonly TPackage[]>,
  TType extends Maybe<keyof TModuleInfo['packages']> = undefined,
>(
  moduleInfo: TModuleInfo,
  type?: TType,
) => ResolvedPackages<TModuleInfo, TType>;

export type AsyncPackageResolver<TPackage extends Package> = <
  TModuleInfo extends ModuleInfo<readonly TPackage[]>,
  TType extends Maybe<keyof TModuleInfo['packages']> = undefined,
>(
  moduleInfo: TModuleInfo,
  type?: TType,
) => Promise<ResolvedPackages<TModuleInfo, TType>>;

export interface ModuleState {
  isModuleEnabled: (moduleInfo: ModuleInfo) => boolean;
  setModuleEnabled: (moduleInfo: ModuleInfo, isEnabled: boolean) => void;
}

export const createModuleState = (): ModuleState => {
  const enabledStates: Record<string, boolean> = {};

  return {
    isModuleEnabled: (moduleInfo) => enabledStates[moduleInfo.name] ?? isModuleEnabledByDefault(moduleInfo),
    setModuleEnabled: (moduleInfo, isEnabled): void => {
      enabledStates[moduleInfo.name] = isEnabled;
    },
  };
};
