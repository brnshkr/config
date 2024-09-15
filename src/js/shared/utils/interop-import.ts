import type { Awaitable } from '../types/core';

export const interopImport = async <TModule>(
  thePackage: Awaitable<TModule>,
): Promise<TModule extends { default: infer TEsModule } ? TEsModule : TModule> => {
  const resolved = await thePackage;

  return <TModule extends { default: infer TEsModule } ? TEsModule : TModule>(
    (typeof resolved === 'object' && resolved !== null && 'default' in resolved)
      ? resolved.default
      : resolved
  );
};
