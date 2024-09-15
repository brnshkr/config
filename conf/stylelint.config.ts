import { getConfig } from '../src/js/stylelint';

export default getConfig({
  ignoreFiles: [
    '../tests/**/fixtures/**',
  ],
});
