# `brnshkr/require-import-attributes` [🔍](../../../../src/js/eslint/configs/builtin/require-import-attributes.ts 'Go to source')

Imports of non-JavaScript files (such as `.json` or `.css`) must declare a matching `with { type: '...' }` import attribute. This makes the runtime contract explicit and keeps the build tooling, the runtime, and the type system in agreement about how the file should be loaded. The expected `type` value is derived from the extension — `.json` maps to `json`, `.css` to `css`, `.svg` to `svg`, image extensions to `image`, and so on. JavaScript and unknown extensions are left untouched.

```js
// ❌ Bad — missing import attributes
import emailTemplates from './email-templates.json';
import userListStyles from './user-list.css';

// ❌ Bad — attributes object present but missing the 'type' key
import emailTemplates from './email-templates.json' with { foo: 'bar' };

// ❌ Bad — 'type' value does not match the extension
import emailTemplates from './email-templates.json' with { type: 'css' };

// ✅ Good
import emailTemplates from './email-templates.json' with { type: 'json' };
import userListStyles from './user-list.css' with { type: 'css' };
```
