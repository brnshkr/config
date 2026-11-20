# Docs

**Less prose is better prose.** A table row beats a sentence, a sentence beats a paragraph.

## Voice

- Short plain sentences, one idea each. The example comes first, then a line or two on it.
- Plain second person: what a user types and what they get.
  No internal vocabulary, no marketing, no hedging, no "simply" or "just".
- Never a trailing `, which` clause, a semicolon or an em dash joining clauses, or an abstract noun for the thing.
  Never a setting as the actor of a verb ("the folder `APP_ENV` names"). Write the concrete path or value.
- No rationale unless the reader must act on it, and no history: what changed, was renamed or used to be.
- A prose fix keeps every fact and is checked sentence by sentence against the real behavior.
  Read each rewritten sentence together with its neighbors. Rewrite from facts, never by rewording sentences in place.

## Content

- Durable facts only: what a reader needs to use the thing.
- Agent pages (`conf/ai/docs/`, `make.md`) are terse rules: no reasons, no history.
- A docs addition is one lead line and an example showing every form the setting takes.
  It lives in the Acme universe (vendor `Acme`, modules `User` and `Email`), never the consumer case that prompted it.
- An example replaces prose. It never joins it.
- No versions, distro names, defaults or limits in prose. They rot.
  Write a placeholder (`ubuntu-<version>`), name the setting and link the file. Values inside an example are fine.
- Never enumerate a set that grows ("all rules"). Describe the intent and give concrete items as "e.g.".
- Examples must run, setup included.
- The README never restates a docs page. It points there in one line.
- Do not name sibling tools a reader may not have installed.
- Env blocks are `ini`, every value double-quoted: `MINIMUM_RELEASE_AGE="1209600"`.
- A customizing table lists each variable with what it falls back to and what it changes,
  then links the file that holds the defaults.

## Form

- A page title is an emoji, the name, and a source link: `# 🔬 PHPStan [🔍](…)`.
- A tool page is `## Usage`, the one line a consumer writes, then `## Customizing`, the call that changes it.
- A development page is the lead, `## Setup`, then what is specific to the repository.
- Terms are written out ("regular expression"). Product names keep their casing in prose ("Compose", "Make").
  Our projects are words in prose ("Config", "Forge", etc.) and lowercase as packages, paths and commands.
- Lists take the serial comma. A parenthetical aside is lowercase and continues the sentence.
- Emphasis is `_underscores_` and strong emphasis `**asterisks**`. Unordered lists use dashes.
  Tables are compact, with a leading and a trailing pipe.
- Lines wrap at 120. A line breaks at the end of a sentence where one fits,
  else at other punctuation, never after a function word, and leaves no orphan tail.
- A list item that defines or continues starts lowercase without a period.
  A standalone fact is a sentence.

## Upstream issues

- Reproduce on the newest release first, and search the tracker, open and closed.
- Follow the repository's issue template, or mirror the maintainer's latest issue there:
  plain sentences, the smallest repro, permalinks at a commit.
- A draft is an untracked `ISSUE-<topic>.md` in the repository root, deleted once filed.
