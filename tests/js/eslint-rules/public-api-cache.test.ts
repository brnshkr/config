import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

import { expect, test } from 'vitest';

import { isPublicApiFile } from '../../../src/js/eslint/utils/public-api';

test('isPublicApiFile invalidates cache when package.json mtime advances', () => {
  const temporaryRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'brnshkr-public-api-'));
  const packageJsonPath = path.join(temporaryRoot, 'package.json');
  const targetFile = path.join(temporaryRoot, 'src/included.ts');
  const alternateFile = path.join(temporaryRoot, 'src/excluded.ts');

  fs.mkdirSync(path.join(temporaryRoot, 'src'));
  fs.writeFileSync(targetFile, 'export {};\n');
  fs.writeFileSync(alternateFile, 'export {};\n');

  fs.writeFileSync(packageJsonPath, JSON.stringify({
    name: 'mtime-fixture',
    exports: {
      '.': './dist/included.js',
    },
  }));

  const lookupOptions = {
    packageJsonPath,
    distRoot: './dist',
    srcRoot: './src',
  };

  expect(isPublicApiFile(lookupOptions, temporaryRoot, targetFile)).toBe(true);
  expect(isPublicApiFile(lookupOptions, temporaryRoot, alternateFile)).toBe(false);

  fs.writeFileSync(packageJsonPath, JSON.stringify({
    name: 'mtime-fixture',
    exports: {
      '.': './dist/excluded.js',
    },
  }));

  const millisecondsPerSecond = 1000;
  const futureDeltaSeconds = 5;
  const future = (Date.now() / millisecondsPerSecond) + futureDeltaSeconds;

  fs.utimesSync(packageJsonPath, future, future);

  expect(isPublicApiFile(lookupOptions, temporaryRoot, targetFile)).toBe(false);
  expect(isPublicApiFile(lookupOptions, temporaryRoot, alternateFile)).toBe(true);

  fs.rmSync(temporaryRoot, {
    recursive: true,
    force: true,
  });
});
