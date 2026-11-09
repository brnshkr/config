import path from 'node:path';

import { expect, test } from 'vitest';

import { findTailwindEntryPoint } from '#eslint/utils/tailwind-entry-point.ts';

const FIXTURES_DIRECTORY = path.join(process.cwd(), 'tests/js/fixtures/tailwind');

test('takes the stylesheet importing tailwindcss that no other stylesheet imports', async () => {
  await expect(findTailwindEntryPoint(path.join(FIXTURES_DIRECTORY, 'single'))).resolves.toBe('assets/app.css');
});

test('skips stylesheets in ignored paths', async () => {
  await expect(findTailwindEntryPoint(path.join(FIXTURES_DIRECTORY, 'ignored'))).resolves.toBe('assets/app.css');
});

test('fails without a stylesheet importing tailwindcss', async () => {
  await expect(findTailwindEntryPoint(path.join(FIXTURES_DIRECTORY, 'none'))).rejects.toThrow(
    'Unable to find a stylesheet importing "tailwindcss", set "tailwind.entryPoint".',
  );
});

test('fails with several stylesheets importing tailwindcss', async () => {
  await expect(findTailwindEntryPoint(path.join(FIXTURES_DIRECTORY, 'several'))).rejects.toThrow(
    'Several stylesheets import "tailwindcss": "assets/admin.css" and "assets/app.css", set "tailwind.entryPoint".',
  );
});
