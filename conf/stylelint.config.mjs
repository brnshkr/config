import { createJiti } from 'jiti';

const jiti = createJiti(import.meta.url);

export default jiti.import('./stylelint.config.ts', {
  default: true,
});
