import { getConfig } from '#markdownlint/index.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

export default getConfig(undefined, {
  overrides: [
    {
      filter: [
        'docs/js/markdownlint/rules/no-php-open-tag.md',
      ],
      config: {
        [<const>`${packageOrganization}/no-php-open-tag`]: false,
      },
    },
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
