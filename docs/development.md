# 💻 Development

Setup and day-to-day commands for working **on** @brnshkr/config itself.
If you only consume the package, you want the [README](../README.md) and the per-tool docs instead.

## Setup

With Docker and Make installed, run this on a fresh clone:

```sh
make startup
```

It starts [Forge's `cli` container](https://github.com/brnshkr/forge/blob/HEAD/docs/compose/cli.md),
installs the dependencies inside it, writes the tool configs this repository does not track,
builds `./dist/`, and installs the Git hooks.
`make help` lists every target. The [Makefile docs](./Makefile.md) explain the shared ones.

## 🤖 AI tooling

This repo ships project-aware tools ([Symfony AI Mate](https://github.com/symfony/ai-mate)) for AI assistants,
wired up under [`./conf/ai/`](../conf/ai). They install themselves on `composer install` (the `mate/` directory is generated),
and an agent runs them as `make mate -- tools:call <tool>`.
Agents must read [`AGENTS.md`](../AGENTS.md) first;
how the setup works and how to extend it lives in [`./conf/ai/docs/ai.md`](../conf/ai/docs/ai.md).
