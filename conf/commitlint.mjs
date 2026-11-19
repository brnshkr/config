import { createJiti } from 'jiti';

const jiti = createJiti(import.meta.url);

export default jiti.import('./commitlint.ts', {
  default: true,
});
