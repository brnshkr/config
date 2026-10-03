/**
 * @internal @brnshkr/config/markdownlint
 */

import { createModuleState, resolvePackagesSharedSynchronously } from '../../shared/utils/module';
import { packageOrganization } from '../../shared/utils/package-json';
import { MARKDOWNLINT_PACKAGES } from '../../shared/utils/package-resolvers';

import type { ModuleInfo, PackageResolver } from '../../shared/utils/module';
import type { MarkdownlintPackage } from '../../shared/utils/package-resolvers';
import type { configs } from '../configs';

export { MARKDOWNLINT_PACKAGES as PACKAGES } from '../../shared/utils/package-resolvers';

export const MODULES = <const>{
  [packageOrganization]: {
    name: packageOrganization,
  },
  github: {
    name: 'github',
    packages: {
      requiredAll: [
        MARKDOWNLINT_PACKAGES.MARKDOWNLINT_GITHUB,
      ],
    },
  },
  links: {
    name: 'links',
    packages: {
      requiredAny: [
        MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_RELATIVE_LINKS,
        MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_NO_TRAILING_SLASH_IN_LINKS,
      ],
    },
  },
  search: {
    name: 'search',
    packages: {
      requiredAll: [
        MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_SEARCH_REPLACE,
      ],
    },
  },
  style: {
    name: 'style',
    packages: {
      requiredAll: [
        MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULES,
      ],
    },
  },
  tables: {
    name: 'tables',
    packages: {
      requiredAll: [
        MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_TABLE_FORMAT,
      ],
    },
  },
} satisfies Partial<Record<keyof typeof configs, ModuleInfo<readonly MarkdownlintPackage[]>>>;

// eslint-disable-next-line security/detect-object-injection -- The key is the package's own organization name
export const BUILTIN_MODULE = MODULES[packageOrganization];
export const resolvePackages: PackageResolver<MarkdownlintPackage> = resolvePackagesSharedSynchronously;
export const { isModuleEnabled, setModuleEnabled } = createModuleState();
