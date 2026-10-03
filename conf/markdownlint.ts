import { getConfig } from '../src/js/markdownlint';
import { packageOrganization } from '../src/js/shared/utils/package-json';

export default getConfig(undefined, {
  overrides: [
    {
      filter: [
        'docs/js/markdownlint/rules/no-warning-comments.md',
      ],
      config: {
        [<const>`${packageOrganization}/no-warning-comments`]: false,
      },
    },
  ],
});
