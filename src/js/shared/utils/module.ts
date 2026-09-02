/**
 * @internal @brnshkr/config
 */

import { isPackageExists } from 'local-pkg';

import { log } from './log';
import { objectEntries } from './object';

import {
  ESLINT_PACKAGE_RESOLVERS,
  MARKDOWNLINT_PACKAGE_RESOLVERS,
  STYLELINT_PACKAGE_RESOLVERS,
} from './package-resolvers';

import { joinAsQuotedList } from './string';

import type { Simplify } from 'type-fest';
import type { Maybe } from '../types/core';

const PACKAGE_RESOLVERS = <const>{
  ...ESLINT_PACKAGE_RESOLVERS,
  ...MARKDOWNLINT_PACKAGE_RESOLVERS,
  ...STYLELINT_PACKAGE_RESOLVERS,
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
  [TType in keyof TModuleInfo['packages']]: TModuleInfo['packages'][TType] extends NonNullable<unknown>
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

    resolvedPackages[currentType] ??= [];

    for (const currentPackage of currentPackages) {
      try {
        // eslint-disable-next-line no-await-in-loop -- Sequential resolving is desired here
        const resolvedPackage = packageCache[currentPackage] ?? await PACKAGE_RESOLVERS[currentPackage]();

        packageCache[currentPackage] ??= resolvedPackage;

        if (resolvedPackage === false) {
          throw new Error('Skip to catch block.');
        }

        resolvedPackages[currentType].push(resolvedPackage);
      } catch {
        if (currentType === 'requiredAll') {
          warnMissingPackages(moduleInfo, currentPackages, currentType);

          break;
        }

        resolvedPackages[currentType].push(undefined);
      }
    }

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

    resolvedPackages[currentType] ??= [];

    for (const currentPackage of currentPackages) {
      try {
        const resolvedPackage = packageCache[currentPackage] ?? PACKAGE_RESOLVERS[currentPackage]();

        packageCache[currentPackage] ??= resolvedPackage;

        if (resolvedPackage === false) {
          throw new Error('Skip to catch block.');
        }

        resolvedPackages[currentType].push(resolvedPackage);
      } catch {
        if (currentType === 'requiredAll') {
          warnMissingPackages(moduleInfo, currentPackages, currentType);

          break;
        }

        resolvedPackages[currentType].push(undefined);
      }
    }

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
      isEnabled = currentType === 'requiredAll'
        ? doAllPackagesExist(currentPackages)
        : doesAnyPackageExist(currentPackages);
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
