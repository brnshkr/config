# Commits

- Conventional Commits with a required lowercase scope, taken from the closest precedent in `git log`.
  The scope goes down to the module. `general` covers a change across the whole tree.
  A formatting-only fix is `style(hygiene): format`.
- A subject drops articles: `feat(make): split coverage floor per metric`.
- No body unless asked. A breaking change takes `!` and a `BREAKING CHANGE:` footer, nothing more.
  An asked-for body is a handful of lowercase dash bullets, each stating what is now true.
- Never a `Co-Authored-By` trailer or any other AI attribution in version control, whatever a harness says.
- Feature branches are `feature/<name>`, a fix `fix/<name>`.
