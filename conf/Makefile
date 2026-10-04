#==
# brnshkr/config Makefile
# ‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾
# This Makefile provides shared tooling, conventions, and utilities that can be
# included and extended by project-specific Makefiles. It implements a small
# framework around GNU Make to provide:
#   - structured CLI-style commands
#   - argument forwarding to targets
#   - automatic help generation
#   - terminal formatting helpers
#
# Variables and macros prefixed with an underscore ('_') are considered private
# implementation details and are not part of the public API.
#
# Macros that are written in UPPER_SNAKE_CASE are treated as variables.
# Macros that are written in lower_snake_case are treated as functions.
#
# This Makefile implements a custom argument parsing system that allows
# arguments to be passed to specific targets while preserving Make's standard behavior:
#
#   Rules:
#     - Arguments that do NOT match an existing target are treated as arguments
#     - Arguments before the first valid target are passed to ALL invoked targets
#     - Arguments between two targets are passed only to the preceding target
#     - Runs that contain no valid targets trigger the error defined in .DEFAULT target
#     - Arguments shaped 'name=value' are taken by make itself, unless the name starts with a dash
#     - Make claims a dash-prefixed word as its own option, so flags follow a '--' separator
#
#   Examples of what the above-mentioned looks like:
#     make a               -> runs target a
#     make a b             -> runs targets a and b
#     make a a1 a2 b b1 b2 -> runs target a with arguments a1 and a2 and target b with arguments b1 and b2 (assuming a1, a2, b1 and b2 are not targets by themselves)
#     make ab1 ab2 a a1 b  -> runs target a with arguments ab1, ab2 and a1 and target b with arguments ab1 and ab2 (assuming ab1, ab2 and a1 are not targets by themselves)
#     make no arg exists   -> throws error defined in .DEFAULT target
#
# Any target can be prepended with the DEBUG flag to echo the commands as they run,
# or the TRACE flag to echo the guards along with them:
#   - make DEBUG=1 phpstan
#   - DEBUG=1 make phpstan
#
# A flag is off when it is empty or one of '0', 'false', 'off' and 'no', and on for any other value.
#
# Output is colored on a terminal or in CI. NO_COLOR (https://no-color.org) turns it off,
# FORCE_COLOR (https://force-color.org) turns it on:
#   - make NO_COLOR=1 help
#   - FORCE_COLOR=1 make help | less -R
#
# The `make help` command automatically generates documentation based on specially formatted comments:
#   Scope headers (control grouping and nesting in the help output):
#     #-- name      —  top-level group (depth 0); all subsequent symbols belong to this group
#     #--- name     —  section (depth 1, nested inside the nearest depth-0 group)
#     #---- name    —  subsection (depth 2)
#     #-----… name  —  subsection (depth N)
#
#     Append 'v' characters to set a minimum verbosity level for everything inside the scope.
#     Arbitrary depth is supported — each additional 'v' raises the threshold by one:
#       #---v name    —  contents hidden unless verbosity >= 1
#       #---vv name   —  contents hidden unless verbosity >= 2
#       #---vvv name  —  contents hidden unless verbosity >= 3
#       (and so on)
#
#   Overrides (a scope or symbol another file declares; the dashes match the scope's depth):
#     #---! brnshkr.theming    —  show the scope at verbosity 0
#     #---vv! brnshkr.theming  —  show it from verbosity 2
#     #---!! brnshkr.theming   —  hide it unless named
#     #~~! phpstan-debug       —  the same for a command, variable or function
#
#   Inline description comments (attach metadata to a specific symbol):
#     VARIABLE ?= value#~~ description  —  annotate a variable with a description
#     command: #~~ description          —  annotate a command with a description
#
#     Append 'v' characters after '#' to make the line itself require higher verbosity (also arbitrary depth):
#       command: #~~ description #v  —  hide the command entry unless verbosity >= 1
#       VARIABLE ?= value#vv #~~ …   —  hide the variable entry unless verbosity >= 2
#       VARIABLE ?= value#~~ … #vvv  —  hide the variable entry unless verbosity >= 3
#
#   Description block (shown at the top of the help output under "Description:"):
#     #!! text  —  emit a free-form description line; multiple lines are supported
#
#   Documentation blocks (shown for function macros at higher verbosities):
#     #**      —  open a documentation block
#     #* text  —  body line inside the block (leading '#* ' is stripped)
#     The first non-comment, non-empty line after the block must be a 'define' statement.
#
#   Filtering by scope(s) is possible:
#     make help brnshkr
#     make help brnshkr.phpstan
#     make help brnshkr.phpstan brnshkr.rector
#     make help brnshkr app.foo -- -v
#
#   Selecting verbosity at the command line (arbitrary depth):
#     make help v       —  verbosity 1
#     make help vvv     —  verbosity 3
#     make help vvvv    —  verbosity 4
#     make help -- -vv  —  verbosity 2 (alias form)
#     make help V=10    —  verbosity 10 (numeric form via env var)
#
#   Listing the scope dot-paths with something to show at the chosen verbosity:
#     make help list-scopes
#     make help ls
#     make help ls vvv
#     make help ls brnshkr
#     make help ls brnshkr.theming app
#
#   Printing what each variable expands to, and where a value from outside the Makefiles came from:
#     make help resolve
#     make help r
#
#   Printing what the Makefiles export, then what each environment file set:
#     make help env
#     make help e
#     make help -- --env
#     make help env vvv  —  private exports as well
#
#   Header overrides (LOGO, PACKAGE, VERSION, EDITOR):
#     <VAR> = <unset>  —  fall back to the default (built-in logo, auto-detected editor)
#     <VAR> = <value>  —  use the given value (EDITOR rejects values outside the supported list)
#     <VAR> =          —  disable the feature (no logo, no clickable hyperlinks)
#
#   EDITOR is the standard shell variable, so one inherited from the environment naming anything else
#   is ignored rather than rejected; only a value set in a Makefile or on the command line is an error.
#==

#-- brnshkr

#!! Shared targets from the `brnshkr/config` package

_BRNSHKR_CONFIG_MAKEFILE := $(lastword $(MAKEFILE_LIST))
_BRNSHKR_CONFIG_DIR      := $(patsubst %/,%,$(dir $(_BRNSHKR_CONFIG_MAKEFILE)))

_ARG_WORDS = $(_HELP_WORD) $(patsubst -%,%,$(patsubst --%,%,$(_ARGS)))

_HELP_VERBOSITY_ARGS       = $(strip $(foreach ARG_WORD,$(_ARG_WORDS),$(if $(subst v,,$(ARG_WORD)),,$(ARG_WORD))))
_HELP_VERBOSITY            = $(words $(subst v,v ,$(lastword $(sort $(_HELP_VERBOSITY_ARGS)))))
_HELP_WORDS               := list-scopes ls resolve r env e
_HELP_WORD_TARGETS        := $(_HELP_WORDS) v vv vvv
_HELP_RESERVED_ARGS        = $(_HELP_WORDS) $(_HELP_VERBOSITY_ARGS)
_HELP_SCOPES               = $(strip $(filter-out $(_HELP_RESERVED_ARGS),$(_ARG_WORDS)))
_HAS_LIST_SCOPES_ARGUMENT  = $(if $(filter list-scopes ls,$(_ARG_WORDS)),1)
_HAS_RESOLVE_ARGUMENT      = $(if $(filter resolve r,$(_ARG_WORDS)),1)
_HAS_ENV_ARGUMENT          = $(if $(filter env e,$(_ARG_WORDS)),1)

_DEFAULT_GOAL := $(.DEFAULT_GOAL)

#--- includes

CONFIG_DIR                ?= ./conf#v #~~ directory holding the project's own tool configs
LOCAL_DIR                 ?= ./.local#v #~~ directory holding a developer's private files
_CONFIG_DIR                = $(patsubst ./%,%,$(CONFIG_DIR))
_LOCAL_DIR                 = $(patsubst ./%,%,$(LOCAL_DIR))
export BRNSHKR_CONFIG_DIR  = $(_CONFIG_DIR)
export BRNSHKR_LOCAL_DIR   = $(_LOCAL_DIR)

define _include_makefiles_in_directory
$(eval include $(filter-out $(abspath $(MAKEFILE_LIST)),$(wildcard $1/Makefile $1/*.mk)))
endef

_SEARCH_BASES := \
	$(_CONFIG_DIR) \
	$(_LOCAL_DIR) \
	$(_LOCAL_DIR)/$(_CONFIG_DIR)

_INCLUDE_DIRECTORIES := $(CURDIR) \
	$(foreach BASE,$(_SEARCH_BASES), \
		$(CURDIR)/$(BASE) \
		$(CURDIR)/$(BASE)/make \
	)

$(foreach INCLUDE_DIRECTORY,$(_INCLUDE_DIRECTORIES), \
	$(call _include_makefiles_in_directory,$(INCLUDE_DIRECTORY)) \
)

_CONSUMER_MAKEFILES := $(filter-out $(_BRNSHKR_CONFIG_MAKEFILE),$(MAKEFILE_LIST))

#---vvv tools

#---- runtimes

SHELL          := $(if $(filter-out default,$(origin SHELL)),$(SHELL),/bin/sh)#~~ shell the recipes run in
PHP            ?= $(RUN) php#~~ command that runs `php`
DOCKER         ?= docker#~~ command that runs `docker`
DOCKER_COMPOSE ?= $(DOCKER) compose#~~ command that runs `compose`

#---- utilities

AWK    ?= awk#~~ path to `awk` binary
CAT    ?= cat#~~ path to `cat` binary
CHMOD  ?= chmod#~~ path to `chmod` binary
CMP    ?= cmp#~~ path to `cmp` binary
CP     ?= cp#~~ path to `cp` binary
CURL   ?= curl#~~ path to `curl` binary
GIT    ?= git#~~ path to `git` binary
LN     ?= ln#~~ path to `ln` binary
MKDIR  ?= mkdir#~~ path to `mkdir` binary
MKTEMP ?= mktemp#~~ path to `mktemp` binary
MV     ?= mv#~~ path to `mv` binary
PRINTF ?= printf#~~ path to `printf` binary
PS     ?= ps#~~ path to `ps` binary
RM     := $(if $(filter-out default,$(origin RM)),$(RM),rm)#~~ path to `rm` binary
SCRIPT ?= script#~~ path to `script` binary
TAR    ?= tar#~~ path to `tar` binary
TR     ?= tr#~~ path to `tr` binary

#--- settings

COMPOSE_FILE     ?= #v #~~ compose files, colon-separated, instead of this project's
APP_SERVICE      ?= app#v #~~ compose service the tools run in, empty for this machine
APP_SERVICE_MODE ?= exec#v #~~ `run` starts a throwaway container instead of entering one
IS_IN_CONTAINER  ?= $(if $(wildcard /.dockerenv /run/.containerenv),1)#v #~~ whether the tools already run in a container
RUN              ?= $(call run_in,$(APP_SERVICE))#v #~~ command the tools run through, empty on this machine
APP_DIR          ?= $(or $(_SERVICE_DIRECTORY),$(CURDIR))#v #~~ where the tools see the sources, not `CURDIR` in a container
HOST_UID         ?= $(or $(word 1,$(_HOST_IDS)),1000)#vv #~~ user a container writes the bind mount as
HOST_GID         ?= $(or $(word 2,$(_HOST_IDS)),1000)#vv #~~ group a container writes the bind mount as
HOST_APP_DIR     ?= $(CURDIR)#vv #~~ where this project sits on this machine, bound by compose

_HOST_IDS = $(eval _HOST_IDS := $$(shell id -u 2>/dev/null; id -g 2>/dev/null))$(_HOST_IDS)

_HOST_ENVIRONMENT = $(_DOTENV_ENVIRONMENT) \
	HOST_UID='$(HOST_UID)' \
	HOST_GID='$(HOST_GID)' \
	HOST_APP_DIR='$(HOST_APP_DIR)'

#**
#* Whether a command is on `PATH`, or exists where it names a path, looked up without starting a shell.
#*
#* parameters:
#*   command: string
#*
#* returns: string
#*
define _is_on_path
$(firstword $(wildcard $(if $(findstring /,$1),$1,$(addsuffix /$1,$(subst :, ,$(PATH))))))
endef

_IS_DETECTED_BY_PARENT := $(filter $(CURDIR),$(_BRNSHKR_DETECTED_IN))

_COMPOSE_CANDIDATES := compose.yaml \
	compose.yml \
	docker-compose.yaml \
	docker-compose.yml

_COMPOSE_FILES = $(or \
	$(subst :, ,$(COMPOSE_FILE)), \
	$(firstword $(wildcard \
		$(addprefix $(CURDIR)/,$(_COMPOSE_CANDIDATES)) \
		$(addprefix $(CURDIR)/$(_CONFIG_DIR)/,$(_COMPOSE_CANDIDATES)) \
	)) \
)

_COMPOSE         = $(DOCKER_COMPOSE) $(addprefix -f ,$(_COMPOSE_FILES))
_HAS_DOCKER     := $(if $(_IS_DETECTED_BY_PARENT),$(_HAS_DOCKER),$(if $(_COMPOSE_FILES),$(call _is_on_path,$(firstword $(DOCKER)))))
_HAS_CONTAINER   = $(if $(IS_IN_CONTAINER),,$(if $(APP_SERVICE),$(if $(_COMPOSE_FILES),$(_HAS_DOCKER))))
_IS_SERVICE_RUN  = $(filter run,$(APP_SERVICE_MODE))

define _SERVICE_AWK
/^services:/ { in_services = 1; next }
/^[^[:space:]]/ { in_services = 0 }
in_services && /^  [^[:space:]]/ { service = $$1; sub(/:$$/, "", service) }
in_services && service == _SERVICE && /^    (working_dir|image):/ { \
	value = $$2; \
	gsub(/["'"'"']/, "", value); \
	if ($$1 == "image:") image = value; else directory = value \
}
END { if (directory != "") print directory, image }
endef


_CONTAINER_KEYS := APP_DIR \
	APP_SERVICE \
	APP_SERVICE_MODE \
	COMPOSE_FILE \
	DOCKER \
	DOCKER_COMPOSE \
	IS_IN_CONTAINER \
	RUN

_TERMINAL_KEYS := BRNSHKR_CACHE_DIR \
	BRNSHKR_CONFIG_DIR \
	BRNSHKR_DEBUG \
	BRNSHKR_LOCAL_DIR \
	CI \
	CLICOLOR \
	CLICOLOR_FORCE \
	COLUMNS \
	EDITOR_URL \
	FORCE_COLOR \
	NO_COLOR \
	TERM

_COMMAND_LINE_KEYS = $(foreach VARIABLE,$(.VARIABLES),$(if $(filter command line,$(origin $(VARIABLE))),$(VARIABLE)))
_FORWARDED_KEYS    = $(filter-out $(_CONTAINER_KEYS) $(_DOTENV_KEYS),$(sort $(_TERMINAL_KEYS) $(_COMMAND_LINE_KEYS)))
_DECLARED_KEYS     = $(filter-out $(_CONTAINER_KEYS),$(_DOTENV_KEYS))

TARGET_PREFIX  ?= #v #~~ prefixes every target, so `brnshkr` gives `brnshkr-lint`
_TARGET_PREFIX  = $(if $(TARGET_PREFIX),$(TARGET_PREFIX)$(if $(filter %- %.,$(TARGET_PREFIX)),,-))

COLLISION_PREFIX  ?= brnshkr#v #~~ namespace for a shared target whose name this project uses
_COLLISION_PREFIX  = $(if $(COLLISION_PREFIX),$(COLLISION_PREFIX)$(if $(filter %- %.,$(COLLISION_PREFIX)),,-))

define _MANIFEST_NAME_AWK
/^[ \t]*[{]?[ \t]*"name"[ \t]*:/ {
	match($$0, /^[ \t]*/)
	if (found_name == "" || RLENGTH < indent) { \
		indent = RLENGTH; split($$0, parts, "\""); \
		found_name = parts[4] \
	}
}
END { print found_name }
endef

ifneq ($(filter undefined,$(origin VENDOR) $(origin PACKAGE)),) # manifest
_MANIFEST      := $(firstword $(wildcard $(CURDIR)/package.json $(CURDIR)/composer.json))
_MANIFEST_NAME := $(if $(_MANIFEST),$(shell $(AWK) '$(_MANIFEST_NAME_AWK)' $(_MANIFEST)))
endif # /manifest

VENDOR  ?= $(if $(findstring /,$(_MANIFEST_NAME)),$(firstword $(subst /, ,$(_MANIFEST_NAME))))#v #~~ vendor name, taken from the manifest when unset
PACKAGE ?= $(or $(lastword $(subst /, ,$(_MANIFEST_NAME))),$(notdir $(CURDIR)))#v #~~ package name, from the manifest or directory when unset
VERSION ?= 0.0.0-dev#vv #~~ package version
_LABEL   = $(VENDOR)$(if $(and $(VENDOR),$(PACKAGE)),/)$(PACKAGE)

_UNQUOTABLE_CHARACTERS := ' " $$ ` \ %

_UNQUOTABLE_NAME := $(or $(if $(findstring $(subst ,, ),$(CURDIR)),$(CURDIR)),$(firstword $(foreach \
	NAME,$(CURDIR) $(VENDOR) $(PACKAGE),$(if $(strip $(foreach \
	CHARACTER,$(_UNQUOTABLE_CHARACTERS),$(findstring $(CHARACTER),$(NAME)))),$(NAME)))))

ifneq ($(_UNQUOTABLE_NAME),)
$(error [$(_LABEL)] `$(_UNQUOTABLE_NAME)` holds a character the recipes cannot quote, so rename it)
endif

ifneq ($(filter . ..,$(VENDOR) $(PACKAGE)),)
$(error [$(_LABEL)] `$(_LABEL)` cannot name a package, so fix the manifest's `name`)
endif

DEBUG               ?= #~~ whether each command is echoed
unexport DEBUG       # NOTICE: the `debug` package reads this from the environment
DEBUG_PREFIX        ?= $(_ANNOUNCE)$(if $(or $(_IS_DEBUG),$(_IS_TRACE)),,@)#vvv #~~ prefix for a command
_IS_DEBUG            = $(filter-out $(_FALSE),$(DEBUG))
export BRNSHKR_DEBUG = $(if $(_IS_DEBUG),1)

TRACE        ?= #~~ whether every recipe line is echoed
TRACE_PREFIX ?= $(if $(_IS_TRACE),,@)$(_ANNOUNCE_IN_SHELL)#vvv #~~ prefix for a line only `TRACE` echoes
_IS_TRACE     = $(filter-out $(_FALSE),$(TRACE))

ANNOUNCEMENT       ?= Running `%s`#v #~~ message before each target a verb runs, `%s` the target
_IS_UNANNOUNCED     = $(and $(_IS_ANNOUNCING),$(ANNOUNCEMENT),$(filter-out _% %-group $(_ANNOUNCED),$(_GOAL)))
_ANNOUNCE           = $(if $(_IS_UNANNOUNCED),$(eval _ANNOUNCED += $(_GOAL))$(info $(shell $(_ANNOUNCEMENT_LOG))))
_ANNOUNCE_IN_SHELL  = $(if $(_IS_UNANNOUNCED),$(eval _ANNOUNCED += $(_GOAL))$(_ANNOUNCEMENT_LOG);)
_ANNOUNCEMENT_LOG   = $(call log,$(ANNOUNCEMENT),$(COLOR_NOTICE),$(call _quoted,$(_GOAL)))

_FALSE := 0 false off no
_TRUE  := 1 true on yes
_IS_CI  = $(filter-out $(_FALSE),$(CI))

_MAKE_FLAGS  = $(if $(or $(_IS_DEBUG),$(_IS_TRACE)),--no-print-directory,-s) _IS_ANNOUNCING=
_VERB_FLAGS  = $(_MAKE_FLAGS) $(_INHERITED_FLAGS) $(if $(_IS_PARALLEL),$(if $(filter 3.%,$(MAKE_VERSION)),,--output-sync=recurse) $(if $(_IS_NO_COLOR),,FORCE_COLOR=1)) --keep-going _IS_ANNOUNCING=1
_IS_PARALLEL = $(filter-out -j1,$(filter -j%,$(MAKEFLAGS)))

_UNEXPORTED_VARIABLES = COMPOSER DEBUG ESLINT_FLAGS VITEST

_INHERITED_FLAGS = $(foreach VARIABLE,$(_UNEXPORTED_VARIABLES), \
	$(if $(filter environment,$(origin $(VARIABLE))),$(VARIABLE)=$(call _quoted,$($(VARIABLE)))))

BRNSHKR_CONFIG_ERROR_CODE ?= 69#vvv #~~ the error code for custom errors thrown by this Makefile

#---v dotenv

DOTENV             ?= 1#~~ base path of the environment files, `1` for `.env`, `0` none
DOTENV_ENV_KEY     ?= APP_ENV#~~ variable naming the current environment
DOTENV_DEFAULT_ENV ?= dev#~~ environment assumed when the variable is unset
DOTENV_TEST_ENVS   ?= test#~~ environments that skip the `.env.local` file
_DOTENV_BASE       := $(if $(filter-out $(_FALSE),$(DOTENV)),$(CURDIR)/$(or $(filter-out $(_TRUE),$(DOTENV)),.env))
_DOTENV_SEPARATOR  := @@brnshkr-dotenv-newline@@
_DOTENV_STAGES     := $(filter-out local %.local %.example,$(patsubst .env.%,%,$(notdir $(wildcard $(CURDIR)/.env.*))))

_DOTENV_REFUSED_STAGES := $(if $(wildcard $(CURDIR)/.env.*),$(shell for file in .env.*; do \
	case "$$file" in (*[!A-Za-z0-9._-]*) [ ! -e "$$file" ] || $(PRINTF) '%s\n' "$$file";; esac; \
done))

ifneq ($(_DOTENV_REFUSED_STAGES),)
$(error [$(_LABEL)] `$(firstword $(_DOTENV_REFUSED_STAGES))` does not name a stage, which takes only letters, digits, `.`, `_` and `-`)
endif

_DOTENV_RESET = $(if $(_DOTENV_FILE_KEYS),env $(foreach KEY,$(sort $(_DOTENV_FILE_KEYS)),-u $(KEY)) )

define _DOTENV_AWK
function fail(message) { if (_ENVIRONMENT) { print "[" _PREFIX "] " message > "/dev/stderr"; exit 1 } gsub(/\$$/, "$$$$", message); printf "$$(error [%s] %s)\n", _PREFIX, message; exit 1 }
function quote_for_shell(text,    quoted_text, position) {
	quoted_text = ""
	while ((position = index(text, SQ))) { quoted_text = quoted_text substr(text, 1, position - 1) SQ "\\" SQ SQ; text = substr(text, position + 1) }
	return SQ quoted_text text SQ
}
function unquote(value,    quote) {
	quote = substr(value, 1, 1)
	if ((quote == "\"" || quote == SQ) && length(value) > 1 && substr(value, length(value), 1) == quote) return substr(value, 2, length(value) - 2)
	return value
}
function interpolate(value,    out, name, start, stop) {
	out = ""
	while ((start = index(value, "$${")) > 0) {
		stop = index(substr(value, start), "}")
		if (stop == 0) break
		name = substr(value, start + 2, stop - 3)
		out = out substr(value, 1, start - 1) ((name in ENVIRON) ? ENVIRON[name] : (name in values) ? values[name] : "")
		value = substr(value, start + stop)
	}
	return out value
}
function load(path,    line, key, value, quote, continued_line, line_number, key_line_number) {
	if ((getline line < path) < 0) return 0
	do {
		key_line_number = ++line_number
		sub(/\r$$/, "", line)
		if (line ~ /^[ \t]*($$|#)/) continue
		sub(/^[ \t]*export[ \t]+/, "", line)
		if (line !~ /^[A-Za-z_][A-Za-z0-9_]*[ \t]*=/) continue
		key = line
		sub(/[ \t]*=.*$$/, "", key)
		value = substr(line, index(line, "=") + 1)
		sub(/^[ \t]+/, "", value)
		quote = substr(value, 1, 1)
		if (quote == "\"" || quote == SQ) {
			while (!(length(value) > 1 && substr(value, length(value), 1) == quote)) {
				if ((getline continued_line < path) <= 0) fail("unterminated quote in `" path "`")
				line_number += 1
				sub(/\r$$/, "", continued_line)
				value = value "\n" continued_line
			}
		} else {
			sub(/[ \t]+#.*$$/, "", value)
			sub(/[ \t]+$$/, "", value)
		}
		if (quote != SQ && value ~ /\$$\(/) fail("`$$(...)` in `" path "` is not supported, use `$${VAR}`")
		value = unquote(value)
		if (quote != SQ) value = interpolate(value)
		if (!(key in values)) order[++count] = key
		values[key] = value
		if (_REPORT) { record_file[++record_count] = path; record_key[record_count] = key; record_value[record_count] = value; record_line[record_count] = key_line_number; last_record_of_key[key] = record_count }
	} while ((getline line < path) > 0)
	close(path)
	return 1
}
function get_environment(fallback,    value) { value = _ENV_VALUE != "" ? _ENV_VALUE : (_ENV_KEY in values) ? values[_ENV_KEY] : ""; return value != "" ? value : fallback }
BEGIN {
	SQ = sprintf("%c", 39)
	if (!load(_BASE)) load(_BASE ".dist")
	env = get_environment(_DEFAULT_ENV)
	split(_TEST_ENVS, tests, /[ \t]+/)
	for (i in tests) if (tests[i] == env) is_test = 1
	if (!is_test) { load(_BASE ".local"); env = get_environment(env) }
	if (env != "local") { load(_BASE "." env); load(_BASE "." env ".local") }
	if (_REPORT) {
		for (record_index = 1; record_index <= record_count; record_index += 1) {
			key = record_key[record_index]
			value = record_value[record_index]
			gsub(/\n/, " ", value)
			printf "%s\037%s\037%s\037%s\037%s\n", record_file[record_index], key, value, (key in ENVIRON) ? "environment" : (last_record_of_key[key] != record_index) ? record_file[last_record_of_key[key]] : "", record_line[record_index]
		}
		exit
	}
	if (!(_ENV_KEY in values)) order[++count] = _ENV_KEY
	values[_ENV_KEY] = env
	if (_ENVIRONMENT) {
		for (i = 1; i <= count; i += 1) if (!(order[i] in ENVIRON)) printf "%s=%s ", order[i], quote_for_shell(values[order[i]])
		exit
	}
	for (i = 1; i <= count; i += 1) {
		key = order[i]
		value = values[key]
		gsub(/\$$/, "$$$$", value)
		gsub(/#/, "\\#", value)
		gsub(/\n/, "$$(_NEWLINE)", value)
		sub(/^[ \t]/, "$$()&", value)
		sub(/\\$$/, "&$$()", value)
		printf "%s ?= %s%s", key, value, _SEPARATOR
		printf "export %s%s", key, _SEPARATOR
		printf "_DOTENV_KEYS += %s%s", key, _SEPARATOR
		if (!(key in ENVIRON)) printf "_DOTENV_FILE_KEYS += %s%s", key, _SEPARATOR
	}
}
endef

_DOTENV_ENV_VALUE := $($(DOTENV_ENV_KEY))

_DOTENV_AWK_FLAGS = -v _BASE='$(_DOTENV_BASE)' \
	-v _ENV_KEY='$(DOTENV_ENV_KEY)' \
	-v _ENV_VALUE='$(_DOTENV_ENV_VALUE)' \
	-v _DEFAULT_ENV='$(DOTENV_DEFAULT_ENV)' \
	-v _TEST_ENVS='$(DOTENV_TEST_ENVS)' \
	-v _PREFIX='$(_LABEL)' \
	-v _SEPARATOR='$(_DOTENV_SEPARATOR)'

_DOTENV_ENVIRONMENT := $(if $(and $(_HAS_CONTAINER),$(if $(_IS_DETECTED_BY_PARENT),,1),$(wildcard $(_DOTENV_BASE) $(_DOTENV_BASE).dist)), \
	$(shell $(AWK) $(_DOTENV_AWK_FLAGS) -v _ENVIRONMENT=1 '$(_DOTENV_AWK)'))

ifneq ($(_HAS_CONTAINER),) # container

export HOST_UID
export HOST_GID
export HOST_APP_DIR

_SERVICE_CONFIG := $(if $(_IS_DETECTED_BY_PARENT),$(_SERVICE_DIRECTORY),$(shell \
	$(_HOST_ENVIRONMENT) $(_COMPOSE) config 2>/dev/null | $(AWK) -v _SERVICE='$(APP_SERVICE)' '$(_SERVICE_AWK)'))

_SERVICE_DIRECTORY := $(word 1,$(_SERVICE_CONFIG))

_SERVICE_TOOLS := $(if $(word 2,$(_SERVICE_CONFIG)),$(shell $(DOCKER) image inspect \
	--format '{{index .Config.Labels "dev.brnshkr.tools"}}' '$(word 2,$(_SERVICE_CONFIG))' </dev/null 2>/dev/null))

ifeq ($(_SERVICE_DIRECTORY),)
_COMPOSE_REFUSAL := $(shell $(_HOST_ENVIRONMENT) $(_COMPOSE) config --quiet 2>&1 >/dev/null \
	| $(AWK) 'NR == 1 { sub(/^error while interpolating [^:]*: /, ""); print }')

$(error [$(_LABEL)] $(or $(_COMPOSE_REFUSAL),`$(APP_SERVICE)` has no `working_dir`), so fix the compose file or empty `APP_SERVICE`)
endif
endif # /container

#---vv theming

INDENT      ?= 1#~~ indent used in `make help` output
VALUE_WIDTH ?= 48#~~ widest value column in `make help`, truncated past it

NO_COLOR            ?= #~~ whether color is off, on for any value per no-color.org
_IS_COLOR_FORCED     = $(or $(filter-out $(_FALSE),$(FORCE_COLOR)),$(filter-out $(_FALSE),$(CLICOLOR_FORCE)))
_CAN_SHOW_COLOR      = $(or $(_IS_CI),$(MAKE_TERMOUT),$(filter 3.% 4.0%,$(MAKE_VERSION)))
_ANSI_OPTION         = $(if $(_IS_NO_COLOR),--no-ansi,$(if $(_IS_PARALLEL),--ansi))
_IS_PROGRESS_HIDDEN  = $(or $(_IS_CI),$(_IS_NO_COLOR))

_IS_NO_COLOR = $(or \
	$(NO_COLOR), \
	$(if $(_IS_COLOR_FORCED),,$(or $(filter $(_FALSE),$(CLICOLOR)),$(filter dumb,$(TERM)),$(if $(_CAN_SHOW_COLOR),,1))) \
)

ifneq ($(_IS_NO_COLOR),)
export NO_COLOR := 1
else
unexport NO_COLOR
endif

THEME         ?= #~~ the theme to use
THEME_BRNSHKR := brnshkr
THEME_SYMFONY := symfony

_THEMES := $(THEME_BRNSHKR) \
	$(THEME_SYMFONY)

_THEME_DEFAULT := $(THEME_BRNSHKR)
_THEME         := $(if $(filter $(THEME),$(_THEMES)),$(THEME),$(_THEME_DEFAULT))

EDITOR_URL      ?= #~~ link template with `{cwd}` `{file}` `{line}` `{wslDistro}`
EDITOR_VSCODE   := vscode
EDITOR_PHPSTORM := phpstorm

_EDITORS := $(EDITOR_VSCODE) \
	$(EDITOR_PHPSTORM)

_EDITOR_URL_VSCODE     := vscode://file/{cwd}/{file}:{line}
_EDITOR_URL_VSCODE_WSL := vscode://vscode-remote/wsl+{wslDistro}{cwd}/{file}:{line}
_EDITOR_URL_PHPSTORM   := phpstorm://open?file={cwd}/{file}&line={line}

_DETECTED_EDITOR := $(if $(_IS_DETECTED_BY_PARENT),$(_DETECTED_EDITOR),$(if \
	$(filter vscode,$(TERM_PROGRAM))$(VSCODE_PID),$(EDITOR_VSCODE),$(if \
	$(filter JetBrains-JediTerm,$(TERMINAL_EMULATOR)),$(EDITOR_PHPSTORM),$(if \
	$(call _is_on_path,code),$(EDITOR_VSCODE),$(if \
	$(or $(call _is_on_path,pstorm),$(call _is_on_path,phpstorm)),$(EDITOR_PHPSTORM))))))

EDITOR          ?= $(_DETECTED_EDITOR)#~~ the editor to use, empty for no links
_EDITOR          = $(or $(filter $(EDITOR),$(_EDITORS)),$(if $(strip $(EDITOR)),$(if $(findstring environment,$(origin EDITOR)),$(_DETECTED_EDITOR))))
_EDITOR_UNKNOWN  = $(if $(_EDITOR)$(findstring environment,$(origin EDITOR)),,$(strip $(EDITOR)))

_EDITOR_URL_TEMPLATE = $(subst {wslDistro},$(WSL_DISTRO_NAME),$(or \
	$(EDITOR_URL), \
	$(if $(filter $(EDITOR_VSCODE),$(_EDITOR)),$(if $(WSL_DISTRO_NAME),$(_EDITOR_URL_VSCODE_WSL),$(_EDITOR_URL_VSCODE))), \
	$(if $(filter $(EDITOR_PHPSTORM),$(_EDITOR)),$(_EDITOR_URL_PHPSTORM)) \
))

_EDITOR_URL      = $(subst {cwd},$(CURDIR),$(_EDITOR_URL_TEMPLATE))
_INLINE_CODE_URL = $(if $(_IS_NO_COLOR),,$(_EDITOR_URL_TEMPLATE))

ifeq ($(origin LOGO),undefined) # logo
define LOGO
   ___               __   __
  / _ )_______  ____/ /  / /__ ____
 / _  / __/ _ \\(_--/ _ \\/  ´_// __/
/____/_/ /_//_/___/_//_/_/\\_\\/_/

endef
endif # /logo

#---- colors

COLOR_NORMAL  := normal
COLOR_BLACK   := black
COLOR_RED     := red
COLOR_GREEN   := green
COLOR_YELLOW  := yellow
COLOR_BLUE    := blue
COLOR_MAGENTA := magenta
COLOR_CYAN    := cyan
COLOR_WHITE   := white

ifeq ($(_THEME),$(THEME_BRNSHKR)) # theme
COLOR_PRIMARY         ?= $(COLOR_CYAN)
COLOR_SECONDARY       ?= $(COLOR_MAGENTA)
COLOR_TERTIARY        ?= $(COLOR_BLACK) $(MODIFIER_BRIGHT)
COLOR_ERROR           ?= $(COLOR_RED)
COLOR_WARNING         ?= $(COLOR_YELLOW)
COLOR_NOTICE          ?= $(COLOR_MAGENTA)
COLOR_SUCCESS         ?= $(COLOR_GREEN)
COLOR_LOGO            ?= $(COLOR_SECONDARY)
COLOR_TITLE           ?= $(COLOR_PRIMARY) $(MODIFIER_UNDERLINE)
COLOR_USAGE_ARGUMENTS ?= $(COLOR_PRIMARY)
COLOR_USAGE_PROGRAM   ?= $(COLOR_PRIMARY)
COLOR_SECTION_LABEL   ?= $(COLOR_SECONDARY)
COLOR_SCOPE           ?= $(COLOR_SECONDARY)
COLOR_ENTRY           ?= $(COLOR_PRIMARY)
COLOR_DESCRIPTION     ?= $(COLOR_TERTIARY)
COLOR_INLINE_CODE     ?= $(COLOR_SECONDARY)
COLOR_HIGHLIGHT       ?= $(COLOR_YELLOW)
else ifeq ($(_THEME),$(THEME_SYMFONY))
COLOR_PRIMARY         ?= $(COLOR_GREEN)
COLOR_SECONDARY       ?= $(COLOR_YELLOW)
COLOR_TERTIARY        ?= $(COLOR_NORMAL)
COLOR_ERROR           ?= $(COLOR_RED)
COLOR_WARNING         ?= $(COLOR_YELLOW)
COLOR_NOTICE          ?= $(COLOR_CYAN)
COLOR_SUCCESS         ?= $(COLOR_GREEN)
COLOR_LOGO            ?= $(COLOR_SECONDARY)
COLOR_TITLE           ?= $(COLOR_PRIMARY) $(MODIFIER_UNDERLINE)
COLOR_USAGE_ARGUMENTS ?= $(COLOR_TERTIARY)
COLOR_USAGE_PROGRAM   ?= $(COLOR_TERTIARY)
COLOR_SECTION_LABEL   ?= $(COLOR_SECONDARY)
COLOR_SCOPE           ?= $(COLOR_SECONDARY)
COLOR_ENTRY           ?= $(COLOR_PRIMARY)
COLOR_DESCRIPTION     ?= $(COLOR_TERTIARY)
COLOR_INLINE_CODE     ?= $(COLOR_SECONDARY)
COLOR_HIGHLIGHT       ?= $(COLOR_YELLOW)
endif # /theme

_COLORS := $(COLOR_NORMAL) \
	$(COLOR_WHITE) \
	$(COLOR_BLACK) \
	$(COLOR_RED) \
	$(COLOR_YELLOW) \
	$(COLOR_GREEN) \
	$(COLOR_CYAN) \
	$(COLOR_BLUE) \
	$(COLOR_MAGENTA)

#----vvv modifiers

MODIFIER_NORMAL    := normal
MODIFIER_UNDERLINE := underline
MODIFIER_BRIGHT    := bright
MODIFIER_BOLD      := bold
MODIFIER_REVERSE   := reverse

#---vvv features

_MASKS_AND_TRACKED_FILES := $(shell $(PRINTF) '\037 \036 '; $(GIT) ls-files -z 2>/dev/null | $(TR) ' \n\000' '\037\036 ')
_MASKED_SPACE            := $(word 1,$(_MASKS_AND_TRACKED_FILES))
_MASKED_NEWLINE          := $(word 2,$(_MASKS_AND_TRACKED_FILES))
_TRACKED_FILES           := $(wordlist 3,$(words $(_MASKS_AND_TRACKED_FILES)),$(_MASKS_AND_TRACKED_FILES))
_HADOLINT_FILES          := $(strip $(foreach FILE,$(_TRACKED_FILES),$(if $(or $(filter %Dockerfile,$(FILE)),$(findstring Dockerfile.,$(FILE))),$(FILE))))
_ACTIONLINT_FILES        := $(filter .github/workflows/%.yaml .github/workflows/%.yml,$(_TRACKED_FILES))

#**
#* Whether a tool this project does not install can run here.
#*
#* parameters:
#*   binary: string
#*
#* returns: string
#*
define _is_tool_available
$(if $(_HAS_CONTAINER),1,$(call _is_on_path,$1))
endef

define _COMPOSER_DIRECTORIES_AWK
match($$0, /"vendor-dir"[[:space:]]*:[[:space:]]*"[^"]*"/) { \
	vendor_directory = substr($$0, RSTART, RLENGTH); sub(/^[^:]*:[[:space:]]*"/, "", vendor_directory); sub(/"$$/, "", vendor_directory) \
} \
match($$0, /"bin-dir"[[:space:]]*:[[:space:]]*"[^"]*"/) { \
	bin_directory = substr($$0, RSTART, RLENGTH); sub(/^[^:]*:[[:space:]]*"/, "", bin_directory); sub(/"$$/, "", bin_directory) \
} \
END { \
	if (vendor_directory == "") vendor_directory = "vendor"; \
	if (bin_directory == "") bin_directory = vendor_directory "/bin"; \
	sub(/\{\$$vendor-dir\}/, vendor_directory, bin_directory); \
	print vendor_directory, bin_directory \
}
endef

_COMPOSER_DIRECTORIES := $(if $(wildcard $(CURDIR)/composer.json),$(shell $(AWK) '$(_COMPOSER_DIRECTORIES_AWK)' $(CURDIR)/composer.json),vendor vendor/bin)
_COMPOSER_VENDOR_DIR  := $(word 1,$(_COMPOSER_DIRECTORIES))
_COMPOSER_BIN_DIR     := $(word 2,$(_COMPOSER_DIRECTORIES))

_HAS_PHP             = $(wildcard $(CURDIR)/composer.json)
_HAS_PHPUNIT         = $(wildcard $(CURDIR)/$(_COMPOSER_BIN_DIR)/pest $(CURDIR)/$(_COMPOSER_BIN_DIR)/phpunit)
_HAS_PHPSTAN         = $(wildcard $(CURDIR)/$(_COMPOSER_BIN_DIR)/phpstan)
_HAS_PHP_CS_FIXER    = $(wildcard $(CURDIR)/$(_COMPOSER_BIN_DIR)/php-cs-fixer)
_HAS_RECTOR          = $(wildcard $(CURDIR)/$(_COMPOSER_BIN_DIR)/rector)
_HAS_TWIG_CS_FIXER   = $(wildcard $(CURDIR)/$(_COMPOSER_BIN_DIR)/twig-cs-fixer)
_HAS_JS              = $(wildcard $(CURDIR)/package.json)
_HAS_BUN_INSPECT     = $(wildcard $(CURDIR)/node_modules/.bin/node-modules-inspector)
_HAS_COMMITLINT      = $(wildcard $(CURDIR)/node_modules/.bin/commitlint)
_HAS_ESLINT          = $(wildcard $(CURDIR)/node_modules/.bin/eslint)
_HAS_ESLINT_INSPECT  = $(wildcard $(CURDIR)/node_modules/.bin/eslint-config-inspector)
_HAS_MARKDOWNLINT    = $(wildcard $(CURDIR)/node_modules/.bin/markdownlint-cli2)
_HAS_STYLELINT       = $(wildcard $(CURDIR)/node_modules/.bin/stylelint)
_HAS_TYPESCRIPT      = $(wildcard $(CURDIR)/node_modules/.bin/tsc)
_HAS_VITEST          = $(and $(wildcard $(CURDIR)/node_modules/.bin/vitest),$(wildcard $(CURDIR)/node_modules/vite/package.json))
_HAS_HADOLINT       := $(if $(_IS_DETECTED_BY_PARENT),$(_HAS_HADOLINT),$(and $(_HADOLINT_FILES),$(call _is_tool_available,hadolint)))
_HAS_ACTIONLINT     := $(if $(_IS_DETECTED_BY_PARENT),$(_HAS_ACTIONLINT),$(and $(_ACTIONLINT_FILES),$(call _is_tool_available,actionlint)))
_HAS_COMPOSER_AUDIT  = $(and $(_HAS_PHP),$(wildcard $(CURDIR)/composer.lock))
_HAS_BUN_AUDIT       = $(and $(_HAS_JS),$(wildcard $(CURDIR)/bun.lock))
_HAS_AUDIT           = $(or $(_HAS_COMPOSER_AUDIT),$(_HAS_BUN_AUDIT))

_HAS_VITEST_COVERAGE = $(firstword $(wildcard \
	$(CURDIR)/node_modules/@vitest/coverage-v8 \
	$(CURDIR)/node_modules/@vitest/coverage-istanbul \
))

_HAS_SEMGREP := $(strip $(if $(_IS_DETECTED_BY_PARENT),$(_HAS_SEMGREP),$(if $(_HAS_CONTAINER), \
	$(filter semgrep,$(_SERVICE_TOOLS)), \
	$(call _is_on_path,semgrep))))

_TRACKED_EXTENSIONS := $(sort $(suffix $(_TRACKED_FILES)))
_TESTS_DIRECTORY    := $(wildcard $(CURDIR)/tests)

#**
#* The value it is given when this project tracks a file the extensions name, or when git cannot say.
#*
#* parameters:
#*   value: list<string>
#*   extensions: list<string>
#*
#* returns: list<string>
#*
define _when_tracked
$(if $(_TRACKED_EXTENSIONS),$(if $(filter $(addprefix .,$2),$(_TRACKED_EXTENSIONS)),$1),$1)
endef

#**
#* The value it is given when any of the paths are there, so a list only claims what this project has.
#*
#* parameters:
#*   value: list<string>
#*   paths: list<string>
#*
#* returns: list<string>
#*
define _when_installed
$(if $(wildcard $(addprefix $(CURDIR)/,$2)),$1)
endef

#**
#* The tool's name where it is on and this project tracks a file it reads.
#*
#* parameters:
#*   name: string
#*   feature: string
#*   extensions_setting: string
#*
#* returns: string
#*
define _get_tracked_tool
$(if $2,$(call _when_tracked,$1,$(or $($3),$(_$3))))
endef

_PHP_EXTENSIONS           := php
_TWIG_CS_FIXER_EXTENSIONS := twig
_MARKDOWNLINT_EXTENSIONS  := md markdown
_TYPESCRIPT_EXTENSIONS    := ts tsx cts mts
_VITEST_EXTENSIONS        := ts tsx js jsx mts cts mjs cjs

_ESLINT_EXTENSIONS := $(if $(_HAS_ESLINT),js cjs mjs jsx \
	$(call _when_installed,ts cts mts tsx,node_modules/typescript-eslint) \
	$(call _when_installed,svelte,node_modules/eslint-plugin-svelte) \
	$(call _when_installed,json,node_modules/@eslint/json) \
	$(call _when_installed,jsonc json5,node_modules/eslint-plugin-jsonc) \
	$(call _when_installed,yml yaml,node_modules/eslint-plugin-yml) \
	$(call _when_installed,toml,node_modules/eslint-plugin-toml) \
	$(call _when_installed,css,node_modules/@eslint/css) \
	$(call _when_installed,md,node_modules/@eslint/markdown))

_STYLELINT_EXTENSIONS := $(if $(_HAS_STYLELINT),css \
	$(call _when_installed,less,node_modules/stylelint-config-standard-less) \
	$(call _when_installed,scss,node_modules/stylelint-config-standard-scss) \
	$(call _when_installed,ejs html svelte svg vue astro xml,node_modules/stylelint-config-html))

_PHPUNIT_NAME       := $(if $(_TESTS_DIRECTORY),$(call _get_tracked_tool,phpunit,$(and $(_HAS_PHP),$(_HAS_PHPUNIT)),PHP_EXTENSIONS))
_PHPSTAN_NAME       := $(call _get_tracked_tool,phpstan,$(and $(_HAS_PHP),$(_HAS_PHPSTAN)),PHP_EXTENSIONS)
_PHP_CS_FIXER_NAME  := $(call _get_tracked_tool,php-cs-fixer,$(and $(_HAS_PHP),$(_HAS_PHP_CS_FIXER)),PHP_EXTENSIONS)
_RECTOR_NAME        := $(call _get_tracked_tool,rector,$(and $(_HAS_PHP),$(_HAS_RECTOR)),PHP_EXTENSIONS)
_TWIG_CS_FIXER_NAME := $(call _get_tracked_tool,twig-cs-fixer,$(and $(_HAS_PHP),$(_HAS_TWIG_CS_FIXER)),TWIG_CS_FIXER_EXTENSIONS)
_COMMITLINT_NAME    := $(if $(and $(_HAS_JS),$(_HAS_COMMITLINT)),commitlint)
_ESLINT_NAME        := $(call _get_tracked_tool,eslint,$(and $(_HAS_JS),$(_HAS_ESLINT)),ESLINT_EXTENSIONS)
_MARKDOWNLINT_NAME  := $(call _get_tracked_tool,markdownlint,$(and $(_HAS_JS),$(_HAS_MARKDOWNLINT)),MARKDOWNLINT_EXTENSIONS)
_STYLELINT_NAME     := $(call _get_tracked_tool,stylelint,$(and $(_HAS_JS),$(_HAS_STYLELINT)),STYLELINT_EXTENSIONS)
_TYPESCRIPT_NAME    := $(call _get_tracked_tool,typescript,$(and $(_HAS_JS),$(_HAS_TYPESCRIPT)),TYPESCRIPT_EXTENSIONS)
_VITEST_NAME        := $(if $(_TESTS_DIRECTORY),$(call _get_tracked_tool,vitest,$(and $(_HAS_JS),$(_HAS_VITEST)),VITEST_EXTENSIONS))
_HADOLINT_NAME      := $(if $(_HAS_HADOLINT),hadolint)
_ACTIONLINT_NAME    := $(if $(_HAS_ACTIONLINT),actionlint)
_SEMGREP_NAME       := $(if $(_HAS_SEMGREP),semgrep)

_FIXER_NAMES := $(strip \
	$(_RECTOR_NAME) \
	$(_PHP_CS_FIXER_NAME) \
	$(_TWIG_CS_FIXER_NAME) \
	$(_ESLINT_NAME) \
	$(_STYLELINT_NAME) \
	$(_MARKDOWNLINT_NAME) \
)

_ANALYZER_NAMES := $(strip \
	$(_PHPSTAN_NAME) \
	$(_COMMITLINT_NAME) \
	$(_TYPESCRIPT_NAME) \
	$(_HADOLINT_NAME) \
	$(_ACTIONLINT_NAME) \
	$(_SEMGREP_NAME) \
)

_GROUP_NAMES := $(strip \
	$(_PHPSTAN_NAME) \
	$(_PHP_CS_FIXER_NAME) \
	$(_RECTOR_NAME) \
	$(_TWIG_CS_FIXER_NAME) \
	$(_ESLINT_NAME) \
	$(_MARKDOWNLINT_NAME) \
	$(_STYLELINT_NAME) \
	$(_TYPESCRIPT_NAME) \
	$(_HADOLINT_NAME) \
	$(_ACTIONLINT_NAME) \
	$(_SEMGREP_NAME) \
)

_TEST_NAMES      := $(strip $(_PHPUNIT_NAME) $(_VITEST_NAME))
_COVERAGE_NAMES  := $(strip $(_PHPUNIT_NAME) $(if $(_HAS_VITEST_COVERAGE),$(_VITEST_NAME)))
_HAS_CHECK       := $(or $(_FIXER_NAMES),$(_ANALYZER_NAMES),$(CHECK_TARGETS))
_HAS_FIX         := $(or $(_FIXER_NAMES),$(FIX_TARGETS))
_HAS_GROUP       := $(or $(_GROUP_NAMES),$(GROUP_TARGETS))
_HAS_TEST        := $(or $(_TEST_NAMES),$(TEST_TARGETS))
_HAS_TEST_UPDATE := $(_TEST_NAMES)
_HAS_COVERAGE    := $(_COVERAGE_NAMES)
_HAS_CI          := $(or $(_HAS_CHECK),$(_HAS_AUDIT),$(_HAS_TEST),$(CI_TARGETS))

export _BRNSHKR_DETECTED_IN := $(CURDIR)
export _DETECTED_EDITOR
export _HAS_ACTIONLINT
export _HAS_DOCKER
export _HAS_HADOLINT
export _HAS_SEMGREP
export _SERVICE_DIRECTORY

_ENABLED_FEATURES = $(foreach VARIABLE,$(filter _HAS_%,$(.VARIABLES)),$(if $($(VARIABLE)),$(VARIABLE)))

#---vvv targets

_TARGET_SOURCES := $(shell $(AWK) -v _PREFIX='$(_TARGET_PREFIX)' -v _CURDIR='$(CURDIR)' \
	-v _ENABLED_FEATURES=' $(_ENABLED_FEATURES) ' \
	'function does_path_exist(path,    line, does_exist) { does_exist = (getline line < path) >= 0; close(path); return does_exist } \
	/^define[[:space:]]/ { is_in_define = 1; next } \
	/^endef([[:space:]]|$$)/ { is_in_define = 0; next } \
	is_in_define { next } \
	/^ifneq/ && index($$0, "wildcard") { \
		depth += 1; \
		if (!skip) { \
			count = split(substr($$0, index($$0, "wildcard") + 9), paths, /[[:space:]]+/); \
			has_existing_path = 0; \
			for (i = 1; i <= count; i += 1) { \
				path = paths[i]; \
				if (!index(path, "/")) continue; \
				path = substr(path, index(path, "/")); \
				sub(/[^A-Za-z0-9_.\/-]+$$/, "", path); \
				if (does_path_exist(_CURDIR path)) has_existing_path = 1; \
			} \
			if (!has_existing_path) skip = depth \
		} \
		next \
	} \
	/^ifneq \(\$$\(_HAS_[A-Z0-9_]+\),\)/ { \
		depth += 1; \
		feature = substr($$0, 10); \
		sub(/[^A-Z0-9_].*/, "", feature); \
		if (!skip && !index(_ENABLED_FEATURES, " " feature " ")) skip = depth; \
		next \
	} \
	/^ifdef|^ifndef|^ifeq|^ifneq/ { depth += 1; next } \
	/^endif/ { if (skip == depth) skip = 0; depth -= 1; next } \
	skip { next } \
	{ start_line = FNR } \
	/^[^\043\t=]*\\$$/ { while ($$0 ~ /\\$$/ && (getline continuation) > 0) { \
		sub(/[[:space:]]*\\$$/, " "); sub(/^[[:space:]]+/, "", continuation); $$0 = $$0 continuation \
	} } \
	{ while (match($$0, /\$$\(call _target,[a-zA-Z0-9_.-]+\)/)) \
		$$0 = substr($$0, 1, RSTART - 1) "@" substr($$0, RSTART + 15, RLENGTH - 16) substr($$0, RSTART + RLENGTH) } \
	{ gsub(/\$$\(_TARGET_PREFIX\)/, _PREFIX) } \
	/^@?[a-zA-Z0-9_.-]+([[:space:]]+@?[a-zA-Z0-9_.-]+)*:([^=]|$$)/ { \
		count = split(substr($$0, 1, index($$0, ":") - 1), ns, /[[:space:]]+/); \
		for (i = 1; i <= count; i += 1) \
			if (ns[i] != "" && ns[i] !~ /^[.]/) printf "%s|%s:%d ", ns[i], FILENAME, start_line \
	}' \
	$(MAKEFILE_LIST) \
)

_TARGET_FILES := $(foreach SOURCE,$(_TARGET_SOURCES),$(firstword $(subst :, ,$(SOURCE))))
_TARGET_LINKS := $(subst |,=,$(patsubst @%,%,$(_TARGET_SOURCES)))

ifeq ($(_TARGET_SOURCES),) # awk
$(error [$(_LABEL)] `$(AWK)` found no targets, so it is missing or is not an awk)
endif # /awk

#**
#* The targets a set of files defines.
#*
#* parameters:
#*   files: list<string>
#*
#* returns: list<string>
#*
define _targets_from
$(sort $(foreach FILE,$1,$(patsubst %|$(FILE),%,$(filter %|$(FILE),$(_TARGET_FILES)))))
endef

#**
#* Where a target is defined, each as `<file>:<line>`.
#*
#* parameters:
#*   target: string
#*
#* returns: list<string>
#*
define _definitions_of
$(sort $(patsubst $1|%,%,$(filter $1|%,$(_TARGET_SOURCES))) $(patsubst @$1|%,%,$(filter @$1|%,$(_TARGET_SOURCES))))
endef

#**
#* The files that define a target, which is what a collision has to name.
#*
#* parameters:
#*   target: string
#*
#* returns: list<string>
#*
define _files_defining
$(sort $(foreach DEFINITION,$(call _definitions_of,$1),$(firstword $(subst :, ,$(DEFINITION)))))
endef

#**
#* The name a shared target ends up with.
#* Its own, unless this project took that name, in which case the first free one behind
#* `COLLISION_PREFIX`, numbered if the project took that too.
#* A private one while the goal before it takes it as a name, so that it does not run.
#*
#* parameters:
#*   name: string
#*
#* returns: string
#*
define _target
$(foreach SHARED_TARGET,$(or $(firstword $(filter-out $(_CONSUMER_TARGETS),$(_TARGET_PREFIX)$1 $(_COLLISION_PREFIX)$(_TARGET_PREFIX)$1 $(foreach \
	NUMBER,1 2 3 4 5 6 7 8 9,$(_COLLISION_PREFIX)$(_TARGET_PREFIX)$1-$(NUMBER)))),$(_COLLISION_PREFIX)$(_TARGET_PREFIX)$1),$(if \
	$(filter $(SHARED_TARGET),$(_NAMED_TARGETS)),_$(SHARED_TARGET)-as-name,$(SHARED_TARGET)))
endef

_CONSUMER_TARGETS  := $(call _targets_from,$(_CONSUMER_MAKEFILES))
_SCRAPED_SHARED    := $(call _targets_from,$(filter-out $(_CONSUMER_MAKEFILES),$(MAKEFILE_LIST)))
_SHARED_NAMES      := $(patsubst @%,%,$(filter @%,$(_SCRAPED_SHARED)))
_COLLIDING_TARGETS := $(strip $(foreach NAME,$(_SHARED_NAMES),$(if $(filter $(_TARGET_PREFIX)$(NAME),$(_CONSUMER_TARGETS)),$(NAME))))
_TARGET_NAMES      := $(foreach NAME,$(_SHARED_NAMES),$(NAME)=$(call _target,$(NAME)))
_SHARED_TARGETS    := $(sort $(filter-out @%,$(_SCRAPED_SHARED)) $(foreach NAME,$(_SHARED_NAMES),$(call _target,$(NAME))))
_ALL_TARGETS       := $(sort $(_CONSUMER_TARGETS) $(_SHARED_TARGETS))
_DUPLICATE_TARGETS := $(strip $(foreach NAME,$(_CONSUMER_TARGETS),$(if $(word 2,$(filter $(_CONSUMER_MAKEFILES),$(call _files_defining,$(NAME)))),$(NAME))))
.DEFAULT_GOAL      := $(or $(_DEFAULT_GOAL),$(call _target,help))

#---vv arguments

#**
#* The goal a name reaches, empty where it names nothing.
#*
#* parameters:
#*   name: string
#*
#* returns: string
#*
define _get_goal
$(or $(filter $1,$(_ALL_TARGETS)),$(filter $(call _target,$1),$(_ALL_TARGETS)))
endef

#**
#* The target an alias stands for, empty where the name is no alias.
#*
#* parameters:
#*   name: string
#*
#* returns: string
#*
define _get_alias_target
$(strip $(foreach PAIR,$(TARGET_ALIASES),$(if $(filter $(firstword $(subst =, ,$(PAIR))),$(patsubst -%,%,$1)),$(lastword $(subst =, ,$(PAIR))))))
endef

TARGET_ALIASES ?= h=help \
	$(if $(_HAS_CHECK),c=check) \
	$(if $(_HAS_TEST),t=test) \
	$(if $(_HAS_FIX),f=fix) \
	$(if $(_HAS_GROUP),g=group)#~~ short names for targets, as `<alias>=<target>` pairs

_FIRST_GOAL        := $(firstword $(foreach GOAL,$(MAKECMDGOALS),$(if $(filter $(_ALL_TARGETS),$(GOAL))$(call _get_alias_target,$(GOAL)),$(GOAL))))
_NAMING_GOAL       := $(filter $(foreach NAME,help configs cc $(_HELP_WORD_TARGETS),$(call _target,$(NAME))),$(call _get_goal,$(or $(call _get_alias_target,$(_FIRST_GOAL)),$(_FIRST_GOAL))))
_NAME_GOALS        := $(if $(_NAMING_GOAL),$(filter-out $(_FIRST_GOAL),$(MAKECMDGOALS)))
_NAMED_TARGETS     := $(filter $(foreach PAIR,$(_TARGET_NAMES),$(lastword $(subst =, ,$(PAIR)))),$(_NAME_GOALS))
_NAMED_OWN_TARGETS := $(strip $(foreach GOAL,$(_NAME_GOALS),$(filter $(_CONSUMER_TARGETS),$(GOAL))))

TARGET := $(firstword $(filter $(_ALL_TARGETS),$(if $(_NAMING_GOAL),$(_FIRST_GOAL),$(MAKECMDGOALS))))#~~ the first target of this run
ARG1    = $(word 1,$(ARGS))#~~ the running target's first argument, and so on to `ARG9`
ARG2    = $(word 2,$(ARGS))
ARG3    = $(word 3,$(ARGS))
ARG4    = $(word 4,$(ARGS))
ARG5    = $(word 5,$(ARGS))
ARG6    = $(word 6,$(ARGS))
ARG7    = $(word 7,$(ARGS))
ARG8    = $(word 8,$(ARGS))
ARG9    = $(word 9,$(ARGS))
ARGS    = $(call _shell_quoted,$(_ARGS))#~~ every argument the running target got, quoted for the shell

_GOAL = $@

_ARGS = $(strip $(if $(filter command line,$(origin ARGS)) \
	,$(ARGS), \
	$(if $(_NAMING_GOAL) \
		,$(filter-out $(_FIRST_GOAL) --,$(_GOAL_WORDS)), \
		$(filter-out $(_ALL_TARGETS),$(call _collect_args,$(_GOAL),$(_GOAL_WORDS))) \
	) \
))

_FIXER_ARGS = $(call _shell_quoted,$(call _without_dry_run,$(_ARGS)))
_IS_DRY_RUN = $(or $(filter %-dry-run,$(_GOAL)),$(call _is_dry_run,$(_ARGS)))
_DEBUG_FLAG = $(if $(filter %-debug,$(_GOAL)),--debug)

_OPTION_VARIABLES = $(strip $(foreach NAME,$(filter -%,$(.VARIABLES)),$(if $(filter command line,$(origin $(NAME))),$(NAME))))
_MAKE_ARGV        = $(eval _MAKE_ARGV := $$(shell { $$(TR) '\000' ' ' </proc/$$$$PPID/cmdline || $$(PS) -ww -o args= -p $$$$PPID; } 2>/dev/null))$(_MAKE_ARGV)

_GOAL_WORDS = $(if $(and $(_OPTION_VARIABLES),$(_MAKE_ARGV)),$(foreach WORD,$(_MAKE_ARGV),$(if $(or \
	$(filter $(WORD),$(MAKECMDGOALS)), \
	$(filter $(_OPTION_VARIABLES),$(firstword $(subst =, ,$(WORD))))),$(WORD))),$(MAKECMDGOALS))

_TARGET_ALIAS_MAP = $(foreach PAIR,$(TARGET_ALIASES),$(lastword $(subst =, ,$(PAIR)))=$(firstword $(subst =, ,$(PAIR))))
_ALIAS_TARGET     = $(call _get_alias_target,$@)
_ALIAS_GOAL       = $(call _get_goal,$(_ALIAS_TARGET))

_TAKEN_ALIASES := $(strip $(foreach PAIR,$(TARGET_ALIASES), \
	$(filter $(firstword $(subst =, ,$(PAIR))),$(_ALL_TARGETS))))

_MALFORMED_ALIASES := $(strip $(foreach PAIR,$(TARGET_ALIASES), \
	$(if $(filter 2,$(words $(subst =, ,$(PAIR)))),,$(PAIR))))

_DANGLING_ALIASES := $(strip $(foreach PAIR,$(filter-out $(_MALFORMED_ALIASES),$(TARGET_ALIASES)), \
	$(if $(call _get_goal,$(lastword $(subst =, ,$(PAIR)))),,$(PAIR))))

ifneq ($(_TAKEN_ALIASES),) # taken
$(error [$(_LABEL)] `$(firstword $(_TAKEN_ALIASES))` is a target, so it cannot also be an alias)
endif # /taken

ifneq ($(_MALFORMED_ALIASES),) # malformed
$(error [$(_LABEL)] `$(firstword $(_MALFORMED_ALIASES))` is not an `<alias>=<target>` pair)
endif # /malformed

ifneq ($(_DANGLING_ALIASES),) # dangling
$(error [$(_LABEL)] `$(subst =,` aliases `,$(firstword $(_DANGLING_ALIASES)))`, which is not a target)
endif # /dangling

_IS_JUST_PRINT = $(findstring n,$(firstword -$(MAKEFLAGS)))
_IS_FIRST_GOAL = $(filter $@,$(firstword $(MAKECMDGOALS)))
_UNKNOWN_ARGS  = $(call _shell_quoted,$(filter-out $@,$(_ARGS)))
_SUGGESTED_FOR = $@
_TARGET_GOALS  = $(filter-out _% -%,$(_ALL_TARGETS))
_STAGE_GOALS   = $(foreach STAGE,$(_DOTENV_STAGES),$(addprefix $(STAGE)-,$(_TARGET_GOALS)))

#**
#* The goals tied for nearest to what was typed, at most three.
#*
#* parameters:
#*   goals: list<string>
#*   typed_name?: string = $(_SUGGESTED_FOR)
#*
#* returns: list<string>
#*
define _suggestions
$(shell $(PRINTF) '%s\n' $(call _shell_quoted,$1) \
	| $(AWK) -v _UNKNOWN=$(call _quoted,$(or $2,$(_SUGGESTED_FOR))) ' \
	function levenshtein(left, right,    left_length, right_length, left_index, right_index, cost, best, previous, current) { \
		left_length = length(left); \
		right_length = length(right); \
		for (right_index = 0; right_index <= right_length; right_index += 1) previous[right_index] = right_index; \
		for (left_index = 1; left_index <= left_length; left_index += 1) { \
			current[0] = left_index; \
			for (right_index = 1; right_index <= right_length; right_index += 1) { \
				cost = substr(left, left_index, 1) == substr(right, right_index, 1) ? 0 : 1; \
				best = previous[right_index] + 1; \
				if (current[right_index - 1] + 1 < best) best = current[right_index - 1] + 1; \
				if (previous[right_index - 1] + cost < best) best = previous[right_index - 1] + cost; \
				current[right_index] = best \
			} \
			for (right_index = 0; right_index <= right_length; right_index += 1) previous[right_index] = current[right_index]; \
		} \
		return previous[right_length] \
	} \
	{ measured_distance = levenshtein(_UNKNOWN, $$0); if (measured_distance <= (length(_UNKNOWN) > 4 ? 2 : 1) || (length(_UNKNOWN) >= 3 && index($$0, _UNKNOWN))) printf "%d %s\n", measured_distance, $$0 }' \
	| sort -n \
	| $(AWK) 'NR == 1 { nearest = $$1 } $$1 == nearest && NR <= 3 { print $$2 }')
endef

_IS_NEAR_A_STAGE = $(if $(findstring -,$(_SUGGESTED_FOR)),$(call _suggestions,$(_DOTENV_STAGES),$(firstword $(subst -, ,$(_SUGGESTED_FOR)))))

_SUGGESTED_TARGETS = $(if $(filter -%,$(_SUGGESTED_FOR)),,$(or \
	$(and $(_IS_NEAR_A_STAGE),$(call _suggestions,$(_TARGET_GOALS) $(_STAGE_GOALS))), \
	$(call _suggestions,$(_TARGET_GOALS)), \
	$(call _suggestions,$(_STAGE_GOALS))))

_ARG_VALUES       =
_ARG_FILES        =
_UNKNOWN_VALUES   = $(if $(_ARG_VALUES),$(filter-out $(_ARG_VALUES) $(_ARG_FILES),$(_ARG_WORDS)))
_MISTYPED_VALUE   = $(firstword $(_UNKNOWN_VALUES))
_SUGGESTED_VALUES = $(call _suggestions,$(sort $(_ARG_VALUES) $(_ARG_FILES)),$(_MISTYPED_VALUE))

#--- common

_COMMA := ,
_QUOTE := '
_SPACE := $(subst ,, )

define _NEWLINE


endef

_PACKAGE_CONF_DIRS = $(APP_DIR)/$(_COMPOSER_VENDOR_DIR)/brnshkr/config/conf \
	$(APP_DIR)/node_modules/@brnshkr/config/conf

_AUTOLOADER       = $(APP_DIR)/$(_COMPOSER_VENDOR_DIR)/autoload.php
_PACK_NAMES      :=
_PLAIN_CONFIGS    =
_LAYERED_CONFIGS  =
_LINKED_CONFIGS   =

_CSS_DATA_CONFIGS = $(call _when_installed,$(CURDIR)/.vscode/tailwind.css-data.json|$(_BRNSHKR_CONFIG_DIR)/vscode-tailwind.css-data.dist.json,node_modules/tailwindcss)

_CSS_DATA_FILES = $(sort \
	$(foreach CONFIG,$(_CSS_DATA_CONFIGS),$(call _get_file_name,$(CONFIG))) \
	$(foreach FILE,$(patsubst .vscode/%,%,$(filter .vscode/%.css-data.json,$(_TRACKED_FILES))),$(if \
		$(findstring /,$(FILE))$(findstring ",$(FILE))$(findstring \,$(FILE)),,$(FILE))) \
)

_MANAGED_CONFIGS = $(CURDIR)/.gitignore| \
	$(CURDIR)/.gitattributes|$(_BRNSHKR_CONFIG_DIR)/gitattributes.dist \
	$(CURDIR)/.editorconfig|$(_BRNSHKR_CONFIG_DIR)/editorconfig.dist \
	$(CURDIR)/.vscode/settings.json|$(_BRNSHKR_CONFIG_DIR)/vscode-settings.dist.jsonc \
	$(CURDIR)/.vscode/extensions.json|$(_BRNSHKR_CONFIG_DIR)/vscode-extensions.dist.jsonc \
	$(_CSS_DATA_CONFIGS) \
	$(if $(wildcard $(CURDIR)/package.json),$(CURDIR)/bunfig.toml|$(or $(wildcard $(CURDIR)/$(_CONFIG_DIR)/bunfig.toml.example),$(_BRNSHKR_CONFIG_DIR)/bunfig.dist.toml)) \
	$(CURDIR)/Makefile|$(_BRNSHKR_CONFIG_DIR)/Makefile.dist

_STACK_SUFFIXES = $(if $(wildcard $(CURDIR)/package.json),js) \
	$(if $(wildcard $(CURDIR)/composer.json),php)

_EDITOR_BINARY_SETTINGS = $(if $(_PHP_CS_FIXER_NAME),'  "php-cs-fixer.executablePath": "$${workspaceFolder}/$(_COMPOSER_BIN_DIR)/php-cs-fixer"' \
	'  "php-cs-fixer.executablePathWindows": "$${workspaceFolder}\\$(subst /,\\,$(_COMPOSER_BIN_DIR))\\php-cs-fixer.bat"') \
	$(if $(_PHPSTAN_NAME),'  "phpstan.binPath": "./$(_COMPOSER_BIN_DIR)/phpstan"')

_EDITOR_CONFIG_SETTINGS = $(if $(_CSS_DATA_FILES),'  "css.customData": [' $(foreach FILE,$(_CSS_DATA_FILES),'    "./.vscode/'$(call _shell_quoted,$(FILE))'",') '  ]') \
	$(call _get_editor_setting,$(_STYLELINT_NAME),stylelint.configFile,$(STYLELINT_CONFIG)) \
	$(if $(and $(_ESLINT_NAME),$(call _get_editor_config,$(ESLINT_CONFIG))),'  "eslint.options": {' '    "overrideConfigFile": "$(call _get_editor_config,$(ESLINT_CONFIG))"' '  }') \
	$(call _get_editor_setting,$(_MARKDOWNLINT_NAME),markdownlint.configFile,$(MARKDOWNLINT_CONFIG)) \
	$(call _get_editor_setting,$(_PHP_CS_FIXER_NAME),php-cs-fixer.config,$(PHP_CS_FIXER_CONFIG)) \
	$(call _get_editor_setting,$(_PHPSTAN_NAME),phpstan.configFile,$(PHP_STAN_CONFIG))

_GITIGNORED_PATHS = $(foreach IGNORED_PATH,$(sort \
	/$(_CACHE_DIR)/ \
	/$(_LOCAL_DIR)/ \
	$(if $(wildcard $(CURDIR)/package.json),/node_modules/) \
	$(if $(wildcard $(CURDIR)/composer.json),/$(_COMPOSER_VENDOR_DIR)/) \
	$(if $(_HAS_VITEST),/.vitest/) \
	$(patsubst $(APP_DIR)/%,/%,$(call _in_project,$(subst .dist.,.,$(_LAYERED_CONFIGS))))), \
	$(if $(filter $(patsubst /%,%,$(IGNORED_PATH)),$(_TRACKED_FILES)),,$(IGNORED_PATH)))

_HELP_READ_VARIABLES := INDENT \
	VALUE_WIDTH \
	_DOTENV_FILE_KEYS \
	_EDITORS \
	_EDITOR_UNKNOWN \
	_EDITOR_URL_TEMPLATE \
	_HELP_WORDS \
	_IS_NO_COLOR \
	_TARGET_ALIAS_MAP \
	_TARGET_NAMES

_HELP_VARIABLES = $(sort $(_HELP_READ_VARIABLES) $(_CONDITION_VARIABLES) $(if $(_HAS_RESOLVE_ARGUMENT),$(_RESOLVED_VARIABLES)))

define _VARIABLE_NAMES_AWK
BEGIN { \
	count = split(_VAR_NAMES, names, " "); \
	for (idx = 1; idx <= count; idx++) if (names[idx] ~ /^_?[A-Z][A-Z0-9_]*$$/) print names[idx] \
}
endef

define _ASSIGNED_VARIABLES_AWK
/^(export[[:space:]]+|override[[:space:]]+)*[A-Z][A-Z0-9_]*[[:space:]]*(:|::|:::|[?+!])?=/ { \
	sub(/^(export[[:space:]]+|override[[:space:]]+)*/, ""); sub(/[[:space:]]*(:|::|:::|[?+!])?=.*/, ""); print \
}
/^define[[:space:]]+[A-Z][A-Z0-9_]*([[:space:]]|$$)/ { print $$2 }
endef

define _CONDITION_VARIABLES_AWK
/^(else[[:space:]]+)?if(n?eq|n?def)[[:space:]]/ { \
	if ($$1 ~ /^ifn?def$$/) print $$2; else if ($$2 ~ /^ifn?def$$/) print $$3; \
	line = $$0; \
	while (match(line, /\$$[({][A-Za-z0-9_]+[)}]/)) { \
		print substr(line, RSTART + 2, RLENGTH - 3); \
		line = substr(line, RSTART + RLENGTH) \
	} \
}
endef

_HELP_ENV_INPUTS = _EXPORTED_NAMES="$$exported_names" \
	_HIDDEN_NAMES="_HELP_AWK _DOTENV_REPORT_AWK OLDPWD PWD SHLVL $(foreach VARIABLE_NAME,$(.VARIABLES),$(if $(filter environment,$(origin $(VARIABLE_NAME))),$(VARIABLE_NAME)))" \
	_COMMAND_LINE_NAMES="$(_COMMAND_LINE_KEYS)"

_HELP_RESOLVE_INPUTS = _VARIABLE_SOURCES="$(foreach VARIABLE_NAME,$(sort $(_RESOLVED_VARIABLES)),$(if $(filter command line,$(origin $(VARIABLE_NAME))),$(VARIABLE_NAME)=cli,$(if $(filter environment%,$(origin $(VARIABLE_NAME))),$(VARIABLE_NAME)=env)))"
_HELP_DOTENV_INPUTS  = _DOTENV_REPORT="$$($(_DOTENV_RESET)$(AWK) -v _REPORT=1 $(_DOTENV_AWK_FLAGS) "$$_DOTENV_REPORT_AWK")"

_RESOLVED_VARIABLES  = $(filter $(_ASSIGNED_VARIABLES) $(_DOTENV_KEYS) $(filter-out _%,$(_COMMAND_LINE_KEYS)),$(shell $(AWK) -v _VAR_NAMES="$(.VARIABLES)" '$(_VARIABLE_NAMES_AWK)'))
_ASSIGNED_VARIABLES  = $(shell $(AWK) '$(_ASSIGNED_VARIABLES_AWK)' $(MAKEFILE_LIST))
_CONDITION_VARIABLES = $(shell $(AWK) '$(_CONDITION_VARIABLES_AWK)' $(MAKEFILE_LIST))

$(call _target,help): #~~ shows this help, narrowed by scope, widened by `v` to `vvv`
	$(TRACE_PREFIX)$(if $(_HAS_ENV_ARGUMENT),exported_names=$$(env | $(AWK) -F= '/^[A-Za-z_][A-Za-z0-9_]*=/ { print $$1 }'); )$(call _make_vars_as_env,$(_HELP_VARIABLES)) \
		$(if $(_HAS_ENV_ARGUMENT),$(_HELP_ENV_INPUTS)) \
		$(if $(_HAS_RESOLVE_ARGUMENT),$(_HELP_RESOLVE_INPUTS)) \
		$(if $(or $(_HAS_ENV_ARGUMENT),$(_HAS_RESOLVE_ARGUMENT)),$(_HELP_DOTENV_INPUTS)) \
		_CURDIR="$(abspath $(CURDIR))" \
		_APP_DIR="$(APP_DIR)" \
		_TARGET_PREFIX="$(_TARGET_PREFIX)" \
		_HEADER_LOGO="$(if $(LOGO),$$($(PRINTF) '%b' '$(call _color,$(COLOR_LOGO))$(subst $(_NEWLINE),\n,$(LOGO))$(_ANSI_RESET)__BRNSHKR_LOGO_END__'),)" \
		_HEADER_TITLE="$(if $(or $(PACKAGE),$(VERSION)),$$($(PRINTF) '%b' '$(call text,$(if $(PACKAGE),@@brnshkr-package@@)$(if $(and $(PACKAGE),$(VERSION)), )$(if $(VERSION),($(strip $(VERSION)))),$(COLOR_TITLE))'),)" \
		_HEADER_PACKAGE="$(PACKAGE)" \
		_HEADER_USAGE="$$($(PRINTF) '%b' '$(call text,Usage:,$(COLOR_SECTION_LABEL))\n  $(call text,make,$(COLOR_USAGE_PROGRAM)) $(call text,<command> [--] [arguments],$(COLOR_USAGE_ARGUMENTS))')" \
		_SCOPES="$(subst .,/,$(_HELP_SCOPES))" \
		_HAS_LIST_SCOPES_ARGUMENT="$(_HAS_LIST_SCOPES_ARGUMENT)" \
		_HAS_RESOLVE_ARGUMENT="$(_HAS_RESOLVE_ARGUMENT)" \
		_HAS_ENV_ARGUMENT="$(_HAS_ENV_ARGUMENT)" \
		_INLINE_CODE_OPEN="$$($(PRINTF) '%b' '$(call _ansi,$(COLOR_INLINE_CODE))')" \
		_INLINE_CODE_CLOSE="$$($(PRINTF) '%b' '$(_ANSI_RESET)$(call _ansi,$(COLOR_DESCRIPTION))')" \
		_VERBOSITY="$(or $(V),$(_HELP_VERBOSITY))" \
		LC_ALL=C \
		$(AWK) "$$_HELP_AWK" \
		$(MAKEFILE_LIST)

$(call _target,list-scopes) \
	$(call _target,ls) \
	$(call _target,resolve) \
	$(call _target,r) \
	$(call _target,env) \
	$(call _target,e) \
	$(call _target,v) \
	$(call _target,vv) \
	$(call _target,vvv): $(call _target,help) #~~ runs `help` with its own name as the argument #vvvv

$(foreach WORD,$(_HELP_WORD_TARGETS),$(eval $(call _target,$(WORD)): _HELP_WORD := $(WORD)))

STARTUP_TARGETS ?= #v #~~ this project's own targets, run by `startup` after installs

_STARTUP := _$(_TARGET_PREFIX)install-missing-hooks \
	$(if $(_IS_INSTALLING),,$(if $(wildcard $(CURDIR)/composer.json),_$(_TARGET_PREFIX)composer-install) \
	$(if $(wildcard $(CURDIR)/package.json),_$(_TARGET_PREFIX)bun-install))

$(call _target,startup): $(_STARTUP) #~~ brings a fresh checkout to a workable state
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) --no-keep-going $(call _target,configs)
	$(DEBUG_PREFIX)$(if $(STARTUP_TARGETS),$(MAKE) $(_VERB_FLAGS) --no-keep-going $(STARTUP_TARGETS),:)

GIT_HOOKS ?= #v #~~ git hooks this project installs, as `<hook>=<target>` pairs

$(call _target,install-hooks): #~~ installs this project's git hooks, replacing any that exist #v
	$(TRACE_PREFIX)$(GIT) rev-parse --is-inside-work-tree >/dev/null 2>&1 || { \
		$(call log,`install-hooks` needs a git checkout.,$(COLOR_ERROR)) >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}
	$(TRACE_PREFIX)$(call _install_git_hooks,yes)

_$(_TARGET_PREFIX)install-missing-hooks:
	$(TRACE_PREFIX)$(call _install_git_hooks)

CONFIG              ?= #v #~~ `local`/`dist` to pin the config, else the first found
ARCHIVE_EXTRA_PATHS ?= #v #~~ extra paths an archive ships, a trailing `/` for a directory

_PINNED_CONFIG = $(filter local dist,$(CONFIG))
_TEMPLATE_DIRS = $(_BRNSHKR_CONFIG_DIR) $(filter-out $(_BRNSHKR_CONFIG_DIR),$(wildcard $(CURDIR)/$(_COMPOSER_VENDOR_DIR)/brnshkr/config/conf $(CURDIR)/node_modules/@brnshkr/config/conf))

_NAMED_CONFIGS        = $(filter-out tools t local l force f,$(_ARG_WORDS))
_IS_FORCED_CONFIG     = $(filter force f,$(_ARG_WORDS))
_IS_PRIVATE_CONFIG    = $(filter local l,$(_ARG_WORDS))
_IS_TOOL_CONFIG       = $(or $(filter tools t,$(_ARG_WORDS)),$(_IS_PRIVATE_CONFIG),$(_NAMED_CONFIGS))
_TRACKED_TOOL_CONFIGS = $(foreach CONFIG,$(_LAYERED_CONFIGS),$(call _tracked,$(CONFIG)))
_PRIVATE_TOOL_CONFIGS = $(subst .dist.,.,$(_LAYERED_CONFIGS))
_CONFIG_NAMES         = $(sort $(foreach CONFIG,$(_PLAIN_CONFIGS) $(_TRACKED_TOOL_CONFIGS) $(_MANAGED_CONFIGS) $(_LINKED_CONFIGS),$(call _get_short_name,$(CONFIG))))
_CONFIG_FILES         = $(sort $(foreach CONFIG,$(_PLAIN_CONFIGS) $(_TRACKED_TOOL_CONFIGS) $(_PRIVATE_TOOL_CONFIGS) $(_MANAGED_CONFIGS) $(_LINKED_CONFIGS),$(call _get_file_name,$(CONFIG))))

_CONFIGS = $(call _in_project,$(_PLAIN_CONFIGS) $(if $(_IS_TOOL_CONFIG),$(_TRACKED_TOOL_CONFIGS)) \
	$(if $(_IS_PRIVATE_CONFIG),$(_PRIVATE_TOOL_CONFIGS),$(foreach CONFIG,$(_PRIVATE_TOOL_CONFIGS),$(if $(filter $(call _get_file_name,$(CONFIG)),$(_NAMED_CONFIGS)),$(CONFIG)))))

$(call _target,configs): #~~ writes missing files, `tools` configs, `local` private ones
	$(TRACE_PREFIX)$(call _require_known_value,config)
	$(TRACE_PREFIX)for config_path in $(call _host_path,$(call _selected_configs,$(_CONFIGS))); do \
		$(call _refuse_links,"$$config_path" "$${config_path%/*}" "$${config_path}.example"); \
		if [ -f "$$config_path" ]; then \
			$(if $(_IS_FORCED_CONFIG),:,$(call log,`%s` already exists.,$(COLOR_NOTICE),"./$${config_path#$(CURDIR)/}")); \
			$(if $(_IS_FORCED_CONFIG),:,$(if $(_NAMED_CONFIGS),$(call confirm,Overwrite it?),false)) || continue; \
		fi; \
		config_name="$${config_path##*/}"; \
		template_path="$${config_path}.example"; \
		case "$$config_name" in \
			*.dist.*) shipped_template_name="$$config_name"; tracked_template_name="";; \
			*.mjs|*.php) shipped_template_name=""; tracked_template_name="";; \
			?*.?*) shipped_template_name=""; tracked_template_name="$${config_name%.*}.dist.$${config_name##*.}";; \
			*) shipped_template_name=""; tracked_template_name="$${config_name}.dist";; \
		esac; \
		for directory in $(_TEMPLATE_DIRS); do \
			[ -r "$$template_path" ] && break; \
			for template_name in "$$shipped_template_name" "$$config_name.example" "$$tracked_template_name"; do \
				[ -n "$$template_name" ] || continue; \
				template_path="$$directory/$$template_name"; \
				[ -r "$$template_path" ] && break; \
			done; \
		done; \
		case "$$config_path" in */*) $(MKDIR) -p "$${config_path%/*}";; esac; \
		if [ -r "$$template_path" ]; then \
			$(CP) "$$template_path" "$$config_path" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
		else \
			included_config_path="$${config_path%/*}/$${config_name%.*}.dist.$${config_name##*.}"; \
			case "$$config_name" in \
				*.mjs) $(PRINTF) '%s\n' "export { default } from './$${included_config_path##*/}';" > "$$config_path" \
					|| exit $(BRNSHKR_CONFIG_ERROR_CODE);; \
				*.php) $(PRINTF) '%s\n' '<?php' '' 'declare(strict_types=1);' '' '/**' ' * @internal' ' */' \
					"\$$config = include __DIR__ . '/$${included_config_path##*/}';" '' 'return $$config;' > "$$config_path" \
					|| exit $(BRNSHKR_CONFIG_ERROR_CODE);; \
				*) included_config_path=""; continue;; \
			esac; \
		fi; \
		case "$$config_path" in $(CURDIR)/*.php) $(call _name_internal_tag,$${config_path#$(CURDIR)/});; esac; \
		$(call log,Created `%s`.,$(COLOR_SUCCESS),"./$${config_path#$(CURDIR)/}"); \
		if [ -n "$$included_config_path" ]; then \
			$(call log,Built on `%s` — keep that include.,$(COLOR_NOTICE),"./$${included_config_path#$(CURDIR)/}"); \
			included_config_path=""; \
		fi; \
	done
	$(TRACE_PREFIX)body_path="$$($(MKTEMP))" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	trap '$(RM) -f "$$body_path"' EXIT; \
	for managed_config_pair in $(foreach MANAGED_CONFIG,$(call _selected_configs,$(_MANAGED_CONFIGS)),'$(MANAGED_CONFIG)'); do \
		config_path="$${managed_config_pair%%|*}"; \
		template_path="$${managed_config_pair#*|}"; \
		$(call _write_managed_config); \
	done
	$(TRACE_PREFIX)for linked_config_pair in $(foreach LINKED_CONFIG,$(call _selected_configs,$(_LINKED_CONFIGS)),'$(LINKED_CONFIG)'); do \
		link_path="$${linked_config_pair%%|*}"; \
		linked_path="$${linked_config_pair#*|}"; \
		relative_linked_path="$${linked_path#$(CURDIR)/}"; \
		if [ -e "$$link_path" ]; then \
			$(if $(_IS_FORCED_CONFIG),:,$(call log,`%s` already exists.,$(COLOR_NOTICE),"./$${link_path#$(CURDIR)/}")); \
			$(if $(_IS_FORCED_CONFIG),:,$(if $(_NAMED_CONFIGS),$(call confirm,Overwrite it?),false)) || continue; \
		fi; \
		[ -r "$$linked_path" ] || continue; \
		if $(LN) -sfn "$$relative_linked_path" "$$link_path" 2>/dev/null; then \
			$(call log,Linked `%s` to `%s`.,$(COLOR_SUCCESS),"./$${link_path#$(CURDIR)/}" "./$$relative_linked_path"); \
		else \
			$(PRINTF) '%s\n' '{' "  \"extends\": \"./$$relative_linked_path\"" '}' > "$$link_path" \
				|| exit $(BRNSHKR_CONFIG_ERROR_CODE); \
			$(call log,Created `%s`.,$(COLOR_SUCCESS),"./$${link_path#$(CURDIR)/}"); \
		fi; \
	done

CACHE_DIR                ?= ./.cache#v #~~ directory the tools keep their caches in
_CACHE_DIR                = $(patsubst ./%,%,$(CACHE_DIR))
export BRNSHKR_CACHE_DIR  = $(_CACHE_DIR)
_CACHES                   = $(strip $(if $(_CACHE_DIR),$(foreach CACHE,$(notdir $(wildcard $(CURDIR)/$(_CACHE_DIR)/*)),$(if $(findstring %,$(CACHE)),,$(CACHE)))))
_CACHE_NAMES              = $(sort $(foreach CACHE,$(_CACHES),$(call _get_short_name,$(CACHE))))
_NAMED_CACHES             = $(strip $(foreach CACHE,$(_CACHES),$(if $(filter $(CACHE) $(call _get_short_name,$(CACHE)),$(_ARG_WORDS)),$(CACHE))))

$(call _target,cc): #~~ removes the caches named, or all of them when given none
	$(TRACE_PREFIX)$(call _require_known_value,cache)
	$(TRACE_PREFIX)if [ -z '$(if $(_CACHES),1)' ]; then \
		$(call log,No caches to remove.,$(COLOR_NOTICE)); \
	elif [ -n '$(if $(_NAMED_CACHES),1)' ]; then \
		for name in $(call _shell_quoted,$(_NAMED_CACHES)); do \
			$(RM) -rf "$(CURDIR)/$(_CACHE_DIR)/$$name" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
			$(call log,Removed `%s/%s`.,$(COLOR_SUCCESS),'$(call _named_path,$(CURDIR)/$(_CACHE_DIR))' "$$name"); \
		done; \
	else \
		$(call log,This removes every cache in `%s`.,$(COLOR_WARNING),'$(call _named_path,$(CURDIR)/$(_CACHE_DIR))'); \
		$(call log,Name %s to remove only some.,$(COLOR_NOTICE),$(call _quoted,$(call _join_as_quoted_list,$(_CACHE_NAMES),disjunction)),no-autolink); \
		if $(call confirm,Continue?); then \
			$(RM) -rf "$(CURDIR)/$(_CACHE_DIR)" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
			$(call log,Removed `%s`.,$(COLOR_SUCCESS),'$(call _named_path,$(CURDIR)/$(_CACHE_DIR))'); \
		else \
			$(call log,Nothing removed.,$(COLOR_NOTICE)); \
		fi; \
	fi

FRESH_KEEP ?= /$(_LOCAL_DIR)/ \
	*.local \
	*.local.* \
	/.idea/ \
	/.vscode/ \
	/.fleet/ \
	/.zed/ \
	/.history/ \
	/.claude/ \
	/.agents/ \
	/.cursor/ \
	/.aider* \
	/.continue/ \
	/.windsurf/#v #~~ patterns `fresh` keeps, unless `--all` is passed

_IS_FRESH_ALL    = $(filter --all -a,$(_ARGS))
_FRESH_UNTRACKED = LC_ALL=C $(GIT) -c core.quotePath=false ls-files --others --exclude-standard
_FRESH_KEPT      = LC_ALL=C $(GIT) -c core.quotePath=false ls-files --others --ignored --directory $(_FRESH_EXCLUDES)

_FRESH_DIST_FILES = $(foreach \
	FILE,$(_TRACKED_FILES),$(if $(or $(filter %.dist,$(FILE)),$(findstring .dist.,$(notdir $(FILE)))),$(FILE)))

_FRESH_DIST_SIBLINGS = $(foreach \
	FILE,$(_FRESH_DIST_FILES),/$(patsubst ./%,%,$(dir $(FILE)))$(patsubst %.dist,%,$(subst .dist.,.,$(notdir $(FILE)))))

_FRESH_EXCLUDES = $(if $(_IS_FRESH_ALL),,$(foreach \
	FRESH_PATTERN,$(FRESH_KEEP) $(_FRESH_DIST_SIBLINGS),--exclude='$(FRESH_PATTERN)'))

_FRESH_OPTIONS = set -f; \
	set -- -xdf$(if $(_IS_FRESH_ALL),f) $(_FRESH_EXCLUDES); \
	$(if $(_IS_FRESH_ALL),:,untracked_paths=$$($(_FRESH_UNTRACKED)) || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	newline=$$($(PRINTF) '\n_'); \
	IFS=$${newline%_}; \
	for path in $$untracked_paths; do \
		set -- "$$@" --exclude="/$$($(PRINTF) '%s' "$$path" | $(AWK) "$$_FRESH_ESCAPE_AWK")"; \
	done; \
	unset IFS)

$(call _target,fresh): #~~ removes ignored files but private ones, reruns `startup`
	$(TRACE_PREFIX)$(GIT) rev-parse --is-inside-work-tree >/dev/null 2>&1 || { \
		$(call log,`fresh` needs a git checkout.,$(COLOR_ERROR)) >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}
	$(TRACE_PREFIX)$(GIT) rev-parse --verify --quiet HEAD >/dev/null || { \
		$(call log,`fresh` needs a commit to reset to.,$(COLOR_ERROR)) >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}
	$(TRACE_PREFIX)$(if $(_IS_FRESH_ALL),:,$(call _capture,$(_FRESH_KEPT)) | $(AWK) "$$_FRESH_KEPT_AWK" \
		| while IFS= read -r path; do \
			$(call log,Keeping `./%s`.,$(COLOR_NOTICE),"$$path"); \
		done)
	$(TRACE_PREFIX)$(if $(_IS_FRESH_ALL),:,$(call _capture,$(_FRESH_UNTRACKED)) | while IFS= read -r path; do \
		[ -n "$$path" ] || continue; \
		$(call log,Keeping `./%s` (untracked).,$(COLOR_NOTICE),"$$path"); \
	done)
	$(TRACE_PREFIX)$(_FRESH_OPTIONS); $(call _capture,LC_ALL=C $(GIT) clean -n "$$@") | while IFS= read -r line; do \
		[ -n "$$line" ] || continue; \
		if [ "$${line#Would skip repository }" != "$$line" ]; then \
			$(call log,Keeping `./%s/` (repository).,$(COLOR_NOTICE),"$${line#Would skip repository }"); \
		else \
			$(call log,Would remove `./%s`.,$(COLOR_NOTICE),"$${line#Would remove }"); \
		fi; \
	done
	$(TRACE_PREFIX)$(if $(_IS_JUST_PRINT)$(_IS_DRY_RUN),:,$(if $(filter --force -f,$(_ARGS)),:,$(call confirm,Remove them and reinstall?) || { \
			$(call log,Nothing removed.,$(COLOR_NOTICE)); \
			exit 0; \
		}); \
		$(_FRESH_OPTIONS); $(GIT) clean "$$@")
	$(TRACE_PREFIX)$(if $(_IS_JUST_PRINT)$(_IS_DRY_RUN),:,$(_FRESH_OPTIONS); \
		remaining_report=$$(LC_ALL=C $(GIT) clean -n "$$@") || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
		[ "$${remaining_report#*Would remove }" != "$$remaining_report" ] || { \
			{ [ -r $(_BRNSHKR_CONFIG_MAKEFILE) ] || { \
				$(if $(_COMPOSER_INSTALL),$(_COMPOSER_INSTALL) &&) $(if $(_BUN_INSTALL),$(_BUN_INSTALL) &&) \
				is_installing=_IS_INSTALLING=1; \
			}; } && $(MAKE) $(_VERB_FLAGS) --no-keep-going $$is_installing $(call _target,startup); \
		})

$(call _target,fresh-dry-run): $(call _target,fresh) #~~ lists what `fresh` would remove and keep #vv
$(call _target,fresh-dry-run): _GOAL := $(call _target,fresh-dry-run)

#---v container

ifneq ($(_HAS_CONTAINER),) # container

COMPOSE_UP_FLAGS    ?= --wait#v #~~ additional flags passed to `compose up`
COMPOSE_DOWN_FLAGS  ?= --remove-orphans#v #~~ additional flags passed to `compose down`
COMPOSE_BUILD_FLAGS ?= --pull#v #~~ additional flags passed to `compose build`
COMPOSE_LOGS_FLAGS  ?= --follow#v #~~ additional flags passed to `compose logs`
SERVICE_SHELL       ?= sh#v #~~ program a service's shell starts

$(call _target,up): #~~ starts the services the tools need
	$(DEBUG_PREFIX)$(_COMPOSE) up $(COMPOSE_UP_FLAGS) $(ARGS)

$(call _target,down): #~~ stops them again
	$(DEBUG_PREFIX)$(_COMPOSE) down $(COMPOSE_DOWN_FLAGS) $(ARGS)

$(call _target,ps): #~~ lists what is running #v
	$(DEBUG_PREFIX)$(_COMPOSE) ps $(ARGS)

$(call _target,shell): $(_TARGET_PREFIX)$(APP_SERVICE)-shell #~~ opens a shell in `APP_SERVICE`
$(call _target,shell): _GOAL := $(call _target,shell)

$(call _target,exec): $(_TARGET_PREFIX)$(APP_SERVICE)-exec #~~ runs a command in `APP_SERVICE`
$(call _target,exec): _GOAL := $(call _target,exec)

_SERVICE_ARGS = $(call _shell_quoted,$(filter-out $@,$(_ARGS)))

$(_TARGET_PREFIX)%-shell:
	$(DEBUG_PREFIX)$(call _enter_service,$*) $(or $(_SERVICE_ARGS),$(SERVICE_SHELL))

$(_TARGET_PREFIX)%-logs:
	$(DEBUG_PREFIX)$(_COMPOSE) logs $(COMPOSE_LOGS_FLAGS) $* $(_SERVICE_ARGS)

$(_TARGET_PREFIX)%-exec:
	$(DEBUG_PREFIX)$(call run_in,$*) $(_SERVICE_ARGS)

$(_TARGET_PREFIX)%-build:
	$(DEBUG_PREFIX)$(_COMPOSE) build $(COMPOSE_BUILD_FLAGS) $(_SERVICE_ARGS) $*

endif # /container

#---vvv semver

SEMVER_NUMBER            ?= (0|[1-9][0-9]*)#~~ a numeric identifier, which carries no leading zero
SEMVER_PRERELEASE_PART   ?= (0|[1-9][0-9]*|[0-9]*[A-Za-z-][0-9A-Za-z-]*)#~~ one prerelease identifier, no leading zero when numeric
SEMVER_PRERELEASE        ?= $(SEMVER_PRERELEASE_PART)(\.$(SEMVER_PRERELEASE_PART))*#~~ the prerelease grammar semver itself permits
SEMVER_PRERELEASE_STRICT ?= (alpha|beta|rc)(\.(0|[1-9][0-9]*))?#~~ the narrower one npm and Composer share, spelled npm's way
SEMVER_BUILD             ?= [0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*#~~ build metadata, which neither registry publishes
SEMVER_REGEX             ?= $(SEMVER_NUMBER)\.$(SEMVER_NUMBER)\.$(SEMVER_NUMBER)(-$(SEMVER_PRERELEASE))?(\+$(SEMVER_BUILD))?#~~ the semver grammar, as a POSIX extended regular expression

_DOTENV_ASSIGNMENTS := $(if $(wildcard $(_DOTENV_BASE) $(_DOTENV_BASE).dist),$(shell $(AWK) $(_DOTENV_AWK_FLAGS) '$(_DOTENV_AWK)'))

$(eval $(subst $(_DOTENV_SEPARATOR),$(_NEWLINE),$(_DOTENV_ASSIGNMENTS)))

#**
#* Defines `<stage>-<target>`, which runs that target with the environment key set to that stage.
#*
#* parameters:
#*   stage: string
#*
#* returns: void
#*
define _stage_rule
$1-%: _SUGGESTED_FOR = $$*
$1-%:
	$$(TRACE_PREFIX)$$(if $$(call _get_goal,$$*),:,$$(call log,Unknown command `%s`.,$$(COLOR_ERROR),$$(call _quoted,$$@)); \
		$$(call _suggest,$1-))
	$$(DEBUG_PREFIX)$$(if $$(call _get_goal,$$*),$$(_DOTENV_RESET)$$(MAKE) $$(_MAKE_FLAGS) $$(_INHERITED_FLAGS) $(DOTENV_ENV_KEY)=$1 $$* \
		$$(if $$(_UNKNOWN_ARGS),-- $$(_UNKNOWN_ARGS)),:)
endef

$(foreach STAGE,$(_DOTENV_STAGES),$(eval $(call _stage_rule,$(STAGE))))

ifneq ($(_HAS_PHP),) # php

#--- php

PHP_EXTENSIONS ?= $(_PHP_EXTENSIONS)#vv #~~ extensions the PHP tools read

#--- composer

COMPOSER              ?= $(RUN) composer#v #~~ command that runs `composer`
unexport COMPOSER      # NOTICE: Composer reads this as the manifest to open
COMPOSER_FLAGS        ?= $(if $(_IS_CI),--no-interaction) $(if $(_IS_PROGRESS_HIDDEN),--no-progress) $(if $(_IS_TRACE),-vvv,$(if $(_IS_DEBUG),-v))#v #~~ additional flags passed to `composer`
COMPOSER_AUDIT_FLAGS  ?= --locked#vv #~~ additional flags passed to `composer audit`
_COMPOSER_PACKAGE_DIR := $(CURDIR)/$(_LOCAL_DIR)/$(if $(VENDOR),$(patsubst @%,%,$(VENDOR))/)$(PACKAGE)
_COMPOSER_INSTALL      = $(COMPOSER) install $(COMPOSER_FLAGS)

_PACK_NAMES += composer-pack

$(call _target,composer): #~~ runs `composer`
	$(DEBUG_PREFIX)$(COMPOSER) $(COMPOSER_FLAGS) $(ARGS)

ifneq ($(_HAS_COMPOSER_AUDIT),)
$(call _target,composer-audit): #~~ runs `composer audit` over the locked dependencies #vv
	$(TRACE_PREFIX)$(call _spinner,$(COMPOSER) audit $(COMPOSER_AUDIT_FLAGS) $(ARGS))
endif

$(call _target,composer-list): #~~ lists the files the composer package would ship #vv
	$(DEBUG_PREFIX)$(call _in_scratch_directory,$(COMPOSER) archive --quiet --format=tar --dir="$$app_scratch_directory" --file=package \
		&& $(TAR) -tf "$$scratch_directory/package.tar")

$(call _target,composer-pack): #~~ packs the composer package to `./.local/<VENDOR>/<PACKAGE>` #vvv
	$(DEBUG_PREFIX)$(call _in_scratch_directory,$(RM) -rf '$(_COMPOSER_PACKAGE_DIR)' \
		&& $(MKDIR) -p '$(_COMPOSER_PACKAGE_DIR)' \
		&& $(COMPOSER) archive --quiet --format=tar --dir="$$app_scratch_directory" --file=package \
		&& $(TAR) -xf "$$scratch_directory/package.tar" -C '$(_COMPOSER_PACKAGE_DIR)')

$(call _target,composer-print): #~~ prints the resolved `composer` configuration #vv
	$(DEBUG_PREFIX)$(COMPOSER) config --list $(COMPOSER_FLAGS) $(ARGS)

_$(_TARGET_PREFIX)composer-install:
	$(TRACE_PREFIX)$(call _spinner,$(_COMPOSER_INSTALL))

#--- phpunit

ifneq ($(_HAS_PHPUNIT),) # phpunit

PEST                          ?= $(RUN) $(_PEST_BINARY)#vvv #~~ command that runs `pest`
PHP_UNIT                      ?= $(if $(wildcard $(CURDIR)/$(_COMPOSER_BIN_DIR)/pest),$(PEST),$(RUN) $(APP_DIR)/$(_COMPOSER_BIN_DIR)/phpunit)#v #~~ command that runs the tests, Pest when installed
PHP_UNIT_CONFIG               ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/phpunit.xml $(APP_DIR)/$(_CONFIG_DIR)/phpunit.dist.xml,php)#v #~~ path to the test runner config
PHP_UNIT_FLAGS                ?= $(if $(_IS_DEBUG),--debug) $(if $(_IS_NO_COLOR),--colors=never,$(if $(_IS_PARALLEL),--colors=always))#v #~~ additional flags passed to the test runner
PHP_UNIT_EXCLUDED_GROUPS      ?= #v #~~ test groups the runner leaves out
PHP_UNIT_SNAPSHOT_FLAGS       ?= $(if $(_IS_PEST),--update-snapshots) --do-not-fail-on-incomplete#v #~~ flags that make the runner rewrite its snapshots
PHP_UNIT_COVERAGE_DIR         ?= $(APP_DIR)/$(_CACHE_DIR)/phpunit.cache/coverage#v #~~ directory the HTML coverage report is written to
PHP_UNIT_COVERAGE_FILE        ?= $(PHP_UNIT_COVERAGE_DIR)/coverage.txt#vv #~~ report the minimum is read from, where the runner has none
PHP_UNIT_MIN_COVERAGE         ?= 100#v #~~ percentage every metric must reach, `0` for no floor
PHP_UNIT_MIN_COVERAGE_CLASSES ?= $(PHP_UNIT_MIN_COVERAGE)#vv #~~ percentage the class metric must reach, unread by Pest
PHP_UNIT_MIN_COVERAGE_METHODS ?= $(PHP_UNIT_MIN_COVERAGE)#vv #~~ percentage the method metric must reach, unread by Pest
PHP_UNIT_MIN_COVERAGE_LINES   ?= $(PHP_UNIT_MIN_COVERAGE)#vv #~~ percentage the line metric must reach
PHP_UNIT_COVERAGE_FLAGS       ?= $(if $(_IS_PEST),--coverage $(if $(_PHP_UNIT_FLOOR),--min=$(_PHP_UNIT_FLOOR)),$(if $(_PHP_UNIT_FLOORS),--coverage-text=$(PHP_UNIT_COVERAGE_FILE))) --coverage-html $(PHP_UNIT_COVERAGE_DIR)#v #~~ additional flags passed when collecting coverage

_PEST_BINARY     := $(APP_DIR)/$(_COMPOSER_BIN_DIR)/pest
_IS_PEST          = $(findstring pest,$(PHP_UNIT))
_PHP_UNIT_UPDATE  = $(if $(wildcard $(CURDIR)/$(_COMPOSER_BIN_DIR)/update-snapshots),$(subst /$(_COMPOSER_BIN_DIR)/phpunit,/$(_COMPOSER_BIN_DIR)/update-snapshots,$(PHP_UNIT)),$(PHP_UNIT))
_PHP_UNIT_FLOOR   = $(filter-out 0,$(strip $(PHP_UNIT_MIN_COVERAGE_LINES)))

_PHP_UNIT_FLOORS = Classes=$(strip $(PHP_UNIT_MIN_COVERAGE_CLASSES)) \
	Methods=$(strip $(PHP_UNIT_MIN_COVERAGE_METHODS)) \
	Lines=$(strip $(PHP_UNIT_MIN_COVERAGE_LINES))

_PHP_UNIT_GROUP_FLAGS = $(foreach GROUP,$(PHP_UNIT_EXCLUDED_GROUPS),--exclude-group $(GROUP))

_LAYERED_CONFIGS += $(if $(wildcard $(CURDIR)/tests),$(call _when_tracked,$(call _layered,$(PHP_UNIT_CONFIG),xml),$(PHP_EXTENSIONS)))

_$(_TARGET_PREFIX)assert-pest:
	$(TRACE_PREFIX)test -x '$(call _host_path,$(_PEST_BINARY))' || { \
		$(call log,`pest` is not installed.,$(COLOR_ERROR)) >&2; \
		$(call log,Require `pestphp/pest` or run `make $(call _target,phpunit)` to use the runner this project has.,$(COLOR_NOTICE)) >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}

$(call _target,pest): _$(_TARGET_PREFIX)assert-pest $(call _target,phpunit) #~~ runs the PHP tests through `pest` in particular #vvv
$(call _target,pest): PHP_UNIT = $(PEST)
$(call _target,pest): _GOAL := $(call _target,pest)

$(call _target,pest-coverage): _$(_TARGET_PREFIX)assert-pest $(call _target,phpunit-coverage) #~~ runs them through `pest` and reports its coverage #vvv
$(call _target,pest-coverage): PHP_UNIT = $(PEST)
$(call _target,pest-coverage): _GOAL := $(call _target,pest-coverage)

$(call _target,pest-debug): _$(_TARGET_PREFIX)assert-pest $(call _target,phpunit) #~~ runs them through `pest` with debugging information #vvv
$(call _target,pest-debug): PHP_UNIT = $(PEST)
$(call _target,pest-debug): _GOAL := $(call _target,pest-debug)

$(call _target,pest-list): _$(_TARGET_PREFIX)assert-pest $(call _target,phpunit-list) #~~ lists the tests `pest` collects #vvv
$(call _target,pest-list): PHP_UNIT = $(PEST)
$(call _target,pest-list): _GOAL := $(call _target,pest-list)

$(call _target,pest-update): _$(_TARGET_PREFIX)assert-pest $(call _target,phpunit-update) #~~ runs them through `pest` and updates their snapshots #vvv
$(call _target,pest-update): PHP_UNIT = $(PEST)
$(call _target,pest-update): _GOAL := $(call _target,pest-update)

$(call _target,phpunit): #~~ runs the PHP tests
	$(TRACE_PREFIX)$(call _require_file,$(PHP_UNIT_CONFIG),PHP_UNIT_CONFIG)
	$(TRACE_PREFIX)$(call _announce_excluded_groups,$(PHP_UNIT_EXCLUDED_GROUPS))
	$(TRACE_PREFIX)$(call _spinner,$(PHP_UNIT) --configuration $(PHP_UNIT_CONFIG) $(PHP_UNIT_FLAGS) $(_PHP_UNIT_GROUP_FLAGS) $(_DEBUG_FLAG) $(ARGS))

$(call _target,phpunit-coverage): #~~ runs the PHP tests and reports how much source they reach #v
	$(TRACE_PREFIX)$(call _require_file,$(PHP_UNIT_CONFIG),PHP_UNIT_CONFIG)
	$(TRACE_PREFIX)$(call _announce_excluded_groups,$(PHP_UNIT_EXCLUDED_GROUPS))
	$(TRACE_PREFIX)$(call _spinner,$(PHP_UNIT) --configuration $(PHP_UNIT_CONFIG) $(PHP_UNIT_FLAGS) $(_PHP_UNIT_GROUP_FLAGS) $(PHP_UNIT_COVERAGE_FLAGS) $(ARGS))
	$(TRACE_PREFIX)$(if $(or $(_IS_PEST),$(if $(_PHP_UNIT_FLOORS),,1)),:,$(foreach FLOOR,$(_PHP_UNIT_FLOORS),$(call _require_coverage,$(call _host_path,$(PHP_UNIT_COVERAGE_FILE)),$(firstword $(subst =, ,$(FLOOR))),$(lastword $(subst =, ,$(FLOOR))),PHP_UNIT_COVERAGE_FILE)$(_NEWLINE)))

$(call _target,phpunit-debug): $(call _target,phpunit) #~~ runs the PHP tests with debugging information, not progress #vvv
$(call _target,phpunit-debug): _GOAL := $(call _target,phpunit-debug)

$(call _target,phpunit-list): #~~ lists the tests the runner collects #vv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_UNIT_CONFIG),PHP_UNIT_CONFIG)
	$(DEBUG_PREFIX)$(PHP_UNIT) --configuration $(PHP_UNIT_CONFIG) $(ARGS) --list-tests

$(call _target,phpunit-update): #~~ runs the PHP tests and updates their snapshots #v
	$(TRACE_PREFIX)$(call _require_file,$(PHP_UNIT_CONFIG),PHP_UNIT_CONFIG)
	$(DEBUG_PREFIX)$(_PHP_UNIT_UPDATE) --configuration $(PHP_UNIT_CONFIG) $(PHP_UNIT_FLAGS) $(PHP_UNIT_SNAPSHOT_FLAGS) $(ARGS)
$(call _target,phpunit-update): export UPDATE_SNAPSHOTS = true
$(call _target,phpunit-update): _FORWARDED_KEYS += UPDATE_SNAPSHOTS

endif # /phpunit

#--- phpstan

ifneq ($(_HAS_PHPSTAN),) # phpstan

PHP_STAN        ?= $(RUN) $(APP_DIR)/$(_COMPOSER_BIN_DIR)/phpstan#v #~~ path to `phpstan` binary
PHP_STAN_CONFIG ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/phpstan.php $(APP_DIR)/$(_CONFIG_DIR)/phpstan.dist.php,php)#v #~~ path to `phpstan` config
PHP_STAN_FLAGS  ?= --memory-limit=-1 $(_ANSI_OPTION) $(if $(_IS_PROGRESS_HIDDEN),--no-progress) $(if $(_IS_DEBUG),-vvv,-vv)#v #~~ additional flags passed to `phpstan`

_PHP_STAN_ARGS = $(if $(_COMMAND),$(ARGS),$(call _shell_quoted,$(call _without_option,--level,$(_ARGS))))

_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(PHP_STAN_CONFIG),php),$(PHP_EXTENSIONS))

$(call _target,phpstan): #~~ runs `phpstan analyze`
	$(TRACE_PREFIX)$(call _require_file,$(PHP_STAN_CONFIG),PHP_STAN_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(PHP_STAN) $(_COMMAND) $(_PHP_STAN_ARGS) --configuration $(PHP_STAN_CONFIG) $(PHP_STAN_FLAGS) $(_DEBUG_FLAG))

$(call _target,phpstan): _COMMAND = analyze

$(call _target,phpstan-debug): $(call _target,phpstan) #~~ runs `phpstan analyze` in debug mode #vvv
$(call _target,phpstan-debug): _GOAL := $(call _target,phpstan-debug)

$(call _target,phpstan-group): #~~ counts the `phpstan` findings by identifier #v
	$(TRACE_PREFIX)$(call _require_file,$(PHP_STAN_CONFIG),PHP_STAN_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(PHP_STAN) analyze --configuration $(PHP_STAN_CONFIG) $(PHP_STAN_FLAGS) --no-progress --error-format=json $(ARGS),identifier,message)

$(call _target,phpstan-list): #~~ lists all files that are processed by `phpstan` #vv
	$(TRACE_PREFIX)$(call _capture,$(MAKE) $(_MAKE_FLAGS) $(call _target,phpstan-raw) -- --debug 2>&1) \
		| $(AWK) 'match($$0, /.+\.php$$/) { print substr($$0, RSTART, RLENGTH) }'

$(call _target,phpstan-print): #~~ prints the resolved `phpstan` configuration #vv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_STAN_CONFIG),PHP_STAN_CONFIG)
	$(DEBUG_PREFIX)$(PHP_STAN) dump-parameters --configuration $(PHP_STAN_CONFIG) $(_ANSI_OPTION) $(ARGS)

$(call _target,phpstan-raw): #~~ runs `phpstan analyze` with raw output #vvv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_STAN_CONFIG),PHP_STAN_CONFIG)
	$(DEBUG_PREFIX)$(PHP_STAN) analyze --configuration $(PHP_STAN_CONFIG) $(PHP_STAN_FLAGS) --error-format=raw $(ARGS)

endif # /phpstan

#--- php-cs-fixer

ifneq ($(_HAS_PHP_CS_FIXER),) # php-cs-fixer

PHP_CS_FIXER        ?= $(RUN) $(APP_DIR)/$(_COMPOSER_BIN_DIR)/php-cs-fixer#v #~~ path to `php-cs-fixer` binary
PHP_CS_FIXER_CONFIG ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/php-cs-fixer.php $(APP_DIR)/$(_CONFIG_DIR)/php-cs-fixer.dist.php,php)#v #~~ path to `php-cs-fixer` config
PHP_CS_FIXER_FLAGS  ?= --show-progress=$(if $(_IS_PROGRESS_HIDDEN),none,dots) $(_ANSI_OPTION) $(if $(_IS_DEBUG),-vvv,-v)#v #~~ additional flags passed to `php-cs-fixer`

_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(PHP_CS_FIXER_CONFIG),php),$(PHP_EXTENSIONS))

$(call _target,php-cs-fixer): #~~ runs `php-cs-fixer fix`
	$(TRACE_PREFIX)$(call _require_file,$(PHP_CS_FIXER_CONFIG),PHP_CS_FIXER_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(PHP_CS_FIXER) fix --config $(PHP_CS_FIXER_CONFIG) $(PHP_CS_FIXER_FLAGS) $(if $(_IS_DRY_RUN),--dry-run) $(_FIXER_ARGS))

$(call _target,php-cs-fixer-dry-run): $(call _target,php-cs-fixer) #~~ runs `php-cs-fixer fix` in dry run mode #v
$(call _target,php-cs-fixer-dry-run): _GOAL := $(call _target,php-cs-fixer-dry-run)

$(call _target,php-cs-fixer-group): #~~ counts the `php-cs-fixer` findings by fixer #v
	$(TRACE_PREFIX)$(call _require_file,$(PHP_CS_FIXER_CONFIG),PHP_CS_FIXER_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(PHP_CS_FIXER) fix --config $(PHP_CS_FIXER_CONFIG) $(PHP_CS_FIXER_FLAGS) $(ARGS) --dry-run --format=json,appliedFixers)

$(call _target,php-cs-fixer-list): #~~ lists all files that are processed by `php-cs-fixer` #vv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_CS_FIXER_CONFIG),PHP_CS_FIXER_CONFIG)
	$(TRACE_PREFIX)$(call _capture,$(PHP_CS_FIXER) list-files --config $(PHP_CS_FIXER_CONFIG) $(_ANSI_OPTION) 2>&1) \
		| $(AWK) '/^'\''\.\/.*\.php'\''$$/ { sub(/^'\''\.\//, ""); sub(/'\''$$/, ""); print }'

$(call _target,php-cs-fixer-print): #~~ prints the `php-cs-fixer` rules in use #vv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_CS_FIXER_CONFIG),PHP_CS_FIXER_CONFIG)
	$(DEBUG_PREFIX)$(PHP_CS_FIXER) describe --config $(PHP_CS_FIXER_CONFIG) $(_ANSI_OPTION) -n @ $(ARGS)

endif # /php-cs-fixer

#--- rector

ifneq ($(_HAS_RECTOR),) # rector

RECTOR        ?= $(RUN) $(APP_DIR)/$(_COMPOSER_BIN_DIR)/rector#v #~~ path to `rector` binary
RECTOR_CONFIG ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/rector.php $(APP_DIR)/$(_CONFIG_DIR)/rector.dist.php,php)#v #~~ path to `rector` config
RECTOR_FLAGS  ?= --memory-limit=-1 $(_ANSI_OPTION) $(if $(_IS_PROGRESS_HIDDEN),--no-progress-bar)#v #~~ additional flags passed to `rector`

_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(RECTOR_CONFIG),php),$(PHP_EXTENSIONS))

$(call _target,rector): #~~ runs `rector process`
	$(TRACE_PREFIX)$(call _require_file,$(RECTOR_CONFIG),RECTOR_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(RECTOR) $(_COMMAND) $(_FIXER_ARGS) --config $(RECTOR_CONFIG) $(RECTOR_FLAGS) $(if $(_IS_DRY_RUN),--dry-run) $(_DEBUG_FLAG))

$(call _target,rector): _COMMAND = process

$(call _target,rector-debug): $(call _target,rector) #~~ runs `rector process` in debug mode #vvv
$(call _target,rector-debug): _GOAL := $(call _target,rector-debug)

$(call _target,rector-dry-run): $(call _target,rector) #~~ runs `rector process` in dry run mode #v
$(call _target,rector-dry-run): _GOAL := $(call _target,rector-dry-run)

$(call _target,rector-group): #~~ counts the `rector` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(RECTOR_CONFIG),RECTOR_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(RECTOR) process --config $(RECTOR_CONFIG) $(RECTOR_FLAGS) $(ARGS) --dry-run --output-format=json,applied_rectors)

$(call _target,rector-list): #~~ lists all files that are processed by `rector` #vv
	$(TRACE_PREFIX)$(call _require_file,$(RECTOR_CONFIG),RECTOR_CONFIG)
	$(TRACE_PREFIX)$(call _capture,$(RECTOR) process --config $(RECTOR_CONFIG) --debug --dry-run) | $(AWK) '/\[file\]/ { sub(".*\\[file\\] $(APP_DIR)/", ""); if (/\.php$$/) print }'

$(call _target,rector-print): #~~ prints the `rector` rules in use #vv
	$(TRACE_PREFIX)$(call _require_file,$(RECTOR_CONFIG),RECTOR_CONFIG)
	$(TRACE_PREFIX)$(call _php_program,_RULES_PROGRAM,rector $(RECTOR_CONFIG) '$(_INLINE_CODE_URL)' $(APP_DIR) $(CURDIR))

$(call _target,rector-print): export _RULES_PROGRAM = $(_RULES_PROGRAM_SOURCE)

endif # /rector

#--- twig-cs-fixer

ifneq ($(_HAS_TWIG_CS_FIXER),) # twig-cs-fixer

TWIG_CS_FIXER            ?= $(RUN) $(APP_DIR)/$(_COMPOSER_BIN_DIR)/twig-cs-fixer#v #~~ path to `twig-cs-fixer` binary
TWIG_CS_FIXER_CONFIG     ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/twig-cs-fixer.php $(APP_DIR)/$(_CONFIG_DIR)/twig-cs-fixer.dist.php,php)#v #~~ path to `twig-cs-fixer` config
TWIG_CS_FIXER_FLAGS      ?= $(if $(_IS_DEBUG),-vvv --debug,-v) $(_ANSI_OPTION)#v #~~ additional flags passed to `twig-cs-fixer`
TWIG_CS_FIXER_EXTENSIONS ?= $(_TWIG_CS_FIXER_EXTENSIONS)#vv #~~ extensions `twig-cs-fixer` reads

_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(TWIG_CS_FIXER_CONFIG),php),$(TWIG_CS_FIXER_EXTENSIONS))

$(call _target,twig-cs-fixer): #~~ runs `twig-cs-fixer fix`, or `lint` under `--dry-run`
	$(TRACE_PREFIX)$(call _require_file,$(TWIG_CS_FIXER_CONFIG),TWIG_CS_FIXER_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(TWIG_CS_FIXER) lint $(if $(_IS_DRY_RUN),,--fix) --config $(TWIG_CS_FIXER_CONFIG) $(TWIG_CS_FIXER_FLAGS) $(_DEBUG_FLAG) $(_FIXER_ARGS))

$(call _target,twig-cs-fixer-debug): $(call _target,twig-cs-fixer) #~~ runs `twig-cs-fixer` in debug mode #vvv
$(call _target,twig-cs-fixer-debug): _GOAL := $(call _target,twig-cs-fixer-debug)

$(call _target,twig-cs-fixer-dry-run): $(call _target,twig-cs-fixer) #~~ runs `twig-cs-fixer` in dry run mode #v
$(call _target,twig-cs-fixer-dry-run): _GOAL := $(call _target,twig-cs-fixer-dry-run)

$(call _target,twig-cs-fixer-group): #~~ counts the `twig-cs-fixer` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(TWIG_CS_FIXER_CONFIG),TWIG_CS_FIXER_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(TWIG_CS_FIXER) lint --config $(TWIG_CS_FIXER_CONFIG) --report gitlab $(ARGS),check_name,description)

$(call _target,twig-cs-fixer-print): #~~ prints the `twig-cs-fixer` rules in use #vv
	$(TRACE_PREFIX)$(call _require_file,$(TWIG_CS_FIXER_CONFIG),TWIG_CS_FIXER_CONFIG)
	$(TRACE_PREFIX)$(call _php_program,_RULES_PROGRAM,twig-cs-fixer $(TWIG_CS_FIXER_CONFIG) '$(_INLINE_CODE_URL)' $(APP_DIR) $(CURDIR))

$(call _target,twig-cs-fixer-print): export _RULES_PROGRAM = $(_RULES_PROGRAM_SOURCE)

endif # /twig-cs-fixer

endif # /php

ifneq ($(_HAS_JS),) # js

#--- bun

BUN             ?= $(RUN) bun#v #~~ command that runs `bun`
BUN_FLAGS       ?= --bun#v #~~ flags passed to `bun` before the command
BUN_AUDIT_FLAGS ?= #vv #~~ additional flags passed to `bun audit`

_BUN_PACKAGE_DIR := $(CURDIR)/$(_LOCAL_DIR)/$(if $(VENDOR),$(VENDOR)/)$(PACKAGE)
_BUN_INSTALL      = $(BUN) install$(if $(or $(_IS_DEBUG),$(_IS_TRACE)), --verbose)

_PACK_NAMES += bun-pack

$(call _target,bun): #~~ runs `bun`
	$(DEBUG_PREFIX)$(BUN) $(ARGS)

ifneq ($(_HAS_BUN_AUDIT),)
$(call _target,bun-audit): #~~ runs `bun audit` over the locked dependencies #vv
	$(TRACE_PREFIX)$(call _spinner,$(BUN) audit $(BUN_AUDIT_FLAGS) $(ARGS))
endif

$(call _target,bun-list): #~~ lists the files the bun package would ship #vv
	$(TRACE_PREFIX)$(call _capture,NO_COLOR=1 $(BUN) pm pack --dry-run) | $(AWK) '/^packed /{ print $$3 }'

$(call _target,bun-pack): #~~ packs the bun package to `./.local/<VENDOR>/<PACKAGE>` #vvv
	$(DEBUG_PREFIX)$(call _in_scratch_directory,$(RM) -rf '$(_BUN_PACKAGE_DIR)' \
		&& $(MKDIR) -p '$(_BUN_PACKAGE_DIR)' \
		&& $(BUN) pm pack --quiet --filename "$$app_scratch_directory/package.tgz" >/dev/null \
		&& $(TAR) -xzf "$$scratch_directory/package.tgz" -C '$(_BUN_PACKAGE_DIR)' --strip-components 1)

ifneq ($(_HAS_BUN_INSPECT),) # bun-inspect

NODE_MODULES_INSPECTOR       ?= $(BUN) $(BUN_FLAGS) x node-modules-inspector#v #~~ command that runs `node-modules-inspector`
NODE_MODULES_INSPECTOR_FLAGS ?= #v #~~ additional flags passed to `node-modules-inspector`

$(call _target,bun-inspect): #~~ runs `node-modules-inspector` #v
	$(DEBUG_PREFIX)$(NODE_MODULES_INSPECTOR) $(ARGS) $(NODE_MODULES_INSPECTOR_FLAGS)

endif # /bun-inspect

_$(_TARGET_PREFIX)bun-install:
	$(TRACE_PREFIX)$(call _spinner,$(_BUN_INSTALL))

#--- commitlint

ifneq ($(_HAS_COMMITLINT),) # commitlint

COMMITLINT        ?= $(BUN) $(BUN_FLAGS) x commitlint#v #~~ command that runs `commitlint`
COMMITLINT_CONFIG ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/commitlint.mjs $(APP_DIR)/$(_CONFIG_DIR)/commitlint.dist.mjs,js)#v #~~ path to `commitlint` config
COMMITLINT_RANGE  ?= #vv #~~ commit range to lint, as `<from>..<to>`, not one message
COMMITLINT_SOURCE ?= $(if $(COMMITLINT_RANGE),--from $(word 1,$(_COMMITLINT_PARTS)) --to $(or $(word 2,$(_COMMITLINT_PARTS)),HEAD),$(if $(wildcard $(CURDIR)/.git/COMMIT_EDITMSG),--edit,--last))#vv #~~ which messages `commitlint` reads
COMMITLINT_FLAGS  ?= $(if $(_IS_DEBUG),--verbose) $(if $(_IS_NO_COLOR),--color false)#v #~~ additional flags passed to `commitlint`

_COMMITLINT_PARTS = $(subst .., ,$(subst ...,..,$(COMMITLINT_RANGE)))

GIT_HOOKS += commit-msg=$(call _target,commitlint)

_LAYERED_CONFIGS += $(call _layered,$(COMMITLINT_CONFIG),mjs)

$(call _target,commitlint): $(if $(filter --from --last,$(COMMITLINT_SOURCE)),_$(_TARGET_PREFIX)install-missing-hooks) #~~ lints a commit message
	$(TRACE_PREFIX)$(call _require_file,$(COMMITLINT_CONFIG),COMMITLINT_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(COMMITLINT) --config $(COMMITLINT_CONFIG) $(COMMITLINT_FLAGS) $(if $(ARGS),$(ARGS),$(COMMITLINT_SOURCE)))

$(call _target,commitlint-print): #~~ prints the resolved `commitlint` configuration #vv
	$(TRACE_PREFIX)$(call _require_file,$(COMMITLINT_CONFIG),COMMITLINT_CONFIG)
	$(DEBUG_PREFIX)$(COMMITLINT) --config $(COMMITLINT_CONFIG) $(COMMITLINT_FLAGS) --print-config $(ARGS)

endif # /commitlint

#--- eslint

ifneq ($(_HAS_ESLINT),) # eslint

ESLINT                ?= $(BUN) $(BUN_FLAGS) x eslint#v #~~ command that runs `eslint`
ESLINT_CONFIG         ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/eslint.ts $(APP_DIR)/$(_CONFIG_DIR)/eslint.mjs $(APP_DIR)/$(_CONFIG_DIR)/eslint.dist.mjs,js)#v #~~ path to `eslint` config
ESLINT_CACHE          ?= $(APP_DIR)/$(_CACHE_DIR)/eslint.cache.json#v #~~ path to the `eslint` cache
ESLINT_FLAGS          ?= --max-warnings 0 $(if $(_IS_DEBUG),--debug) $(if $(_IS_NO_COLOR),--no-color)#v #~~ additional flags passed to `eslint`
unexport ESLINT_FLAGS  # NOTICE: ESLint reads this as its feature flags

_ESLINT_OUTPUT := $(APP_DIR)/$(_CACHE_DIR)/eslint.output

ESLINT_EXTENSIONS ?= $(_ESLINT_EXTENSIONS)#vv #~~ extensions `eslint` lints, set by its config's modules

_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(ESLINT_CONFIG),mjs),$(ESLINT_EXTENSIONS))

# NOTICE: ESLint writes through `--output-file` since Bun truncates its piped output
$(call _target,eslint): #~~ runs `eslint`, writing fixes unless `--dry-run`
	$(TRACE_PREFIX)$(call _require_file,$(ESLINT_CONFIG),ESLINT_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(RM) -f '$(call _host_path,$(_ESLINT_OUTPUT))'; \
		$(ESLINT) --config $(ESLINT_CONFIG) --cache --cache-location $(ESLINT_CACHE) $(ESLINT_FLAGS) --output-file $(_ESLINT_OUTPUT) $(_FIXER_ARGS) $(if $(_IS_DRY_RUN),,--fix); \
		status=$$?; \
		[ ! -r '$(call _host_path,$(_ESLINT_OUTPUT))' ] || $(CAT) '$(call _host_path,$(_ESLINT_OUTPUT))'; \
		$(RM) -f '$(call _host_path,$(_ESLINT_OUTPUT))'; \
		exit $$status)

$(call _target,eslint-dry-run): $(call _target,eslint) #~~ runs `eslint` without writing fixes #v
$(call _target,eslint-dry-run): _GOAL := $(call _target,eslint-dry-run)

$(call _target,eslint-group): #~~ counts the `eslint` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(ESLINT_CONFIG),ESLINT_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(ESLINT) --config $(ESLINT_CONFIG) --cache --cache-location $(ESLINT_CACHE) $(ESLINT_FLAGS) --format json --output-file /dev/stdout $(ARGS),ruleId,message)

$(call _target,eslint-print): #~~ prints the resolved `eslint` config for a file #vv
	$(TRACE_PREFIX)$(call _require_file,$(ESLINT_CONFIG),ESLINT_CONFIG)
	$(TRACE_PREFIX)$(call _require_file_argument,$(call _target,eslint-print))
	$(DEBUG_PREFIX)$(ESLINT) --config $(ESLINT_CONFIG) --print-config $(ARGS)

ifneq ($(_HAS_ESLINT_INSPECT),) # eslint-inspect

# NOTICE: runs on node since bun has no `module.registerHooks` (https://github.com/oven-sh/bun/issues/27369)
ESLINT_CONFIG_INSPECTOR       ?= $(BUN) x eslint-config-inspector#v #~~ command that runs `eslint-config-inspector`
ESLINT_CONFIG_INSPECTOR_FLAGS ?= #v #~~ additional flags passed to `eslint-config-inspector`

$(call _target,eslint-inspect): #~~ runs `eslint-config-inspector` #v
	$(TRACE_PREFIX)$(call _require_file,$(ESLINT_CONFIG),ESLINT_CONFIG)
	$(DEBUG_PREFIX)$(ESLINT_CONFIG_INSPECTOR) --config $(ESLINT_CONFIG) $(ARGS) $(if $(filter %-stats,$(_GOAL)),--stats) $(ESLINT_CONFIG_INSPECTOR_FLAGS)

$(call _target,eslint-inspect-stats): $(call _target,eslint-inspect) #~~ runs `eslint-config-inspector` with a timed full lint #vv
$(call _target,eslint-inspect-stats): _GOAL := $(call _target,eslint-inspect-stats)

endif # /eslint-inspect

endif # /eslint

#--- markdownlint

ifneq ($(_HAS_MARKDOWNLINT),) # markdownlint

MARKDOWNLINT            ?= $(BUN) $(BUN_FLAGS) x markdownlint-cli2#v #~~ command that runs `markdownlint-cli2`
MARKDOWNLINT_CONFIG     ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/markdownlint.mjs $(APP_DIR)/$(_CONFIG_DIR)/markdownlint.dist.mjs,js)#v #~~ path to `markdownlint` config
MARKDOWNLINT_GLOB       ?= $(call _brace_glob,$(MARKDOWNLINT_EXTENSIONS))#vv #~~ files `markdownlint` looks at
MARKDOWNLINT_FLAGS      ?= #v #~~ additional flags passed to `markdownlint-cli2`
MARKDOWNLINT_EXTENSIONS ?= $(_MARKDOWNLINT_EXTENSIONS)#vv #~~ extensions `markdownlint` reads

_MARKDOWNLINT_VALUE_FLAGS := --config \
	--configPointer

_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(MARKDOWNLINT_CONFIG),mjs),$(MARKDOWNLINT_EXTENSIONS))

$(call _target,markdownlint): #~~ runs `markdownlint-cli2`, writing fixes unless `--dry-run`
	$(TRACE_PREFIX)$(call _require_file,$(MARKDOWNLINT_CONFIG),MARKDOWNLINT_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(MARKDOWNLINT) --config $(MARKDOWNLINT_CONFIG) $(MARKDOWNLINT_FLAGS) \
		$(if $(_IS_DRY_RUN),,--fix) \
		$(if $(call _has_positional_arg,$(call _without_dry_run,$(_ARGS)),$(_MARKDOWNLINT_VALUE_FLAGS)), \
			$(_FIXER_ARGS), \
			$(_FIXER_ARGS) '$(MARKDOWNLINT_GLOB)'))

$(call _target,markdownlint-dry-run): $(call _target,markdownlint) #~~ runs `markdownlint-cli2` without writing fixes #v
$(call _target,markdownlint-dry-run): _GOAL := $(call _target,markdownlint-dry-run)

$(call _target,markdownlint-group): #~~ counts the `markdownlint` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(MARKDOWNLINT_CONFIG),MARKDOWNLINT_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(MARKDOWNLINT) --config $(MARKDOWNLINT_CONFIG) $(MARKDOWNLINT_FLAGS) $(if $(call _has_positional_arg,$(ARGS),$(_MARKDOWNLINT_VALUE_FLAGS)),$(ARGS),$(ARGS) '$(MARKDOWNLINT_GLOB)'))

endif # /markdownlint

#--- stylelint

ifneq ($(_HAS_STYLELINT),) # stylelint

STYLELINT        ?= $(BUN) $(BUN_FLAGS) x stylelint#v #~~ command that runs `stylelint`
STYLELINT_CONFIG ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/stylelint.mjs $(APP_DIR)/$(_CONFIG_DIR)/stylelint.dist.mjs,js)#v #~~ path to `stylelint` config
STYLELINT_GLOB   ?= $(call _brace_glob,$(STYLELINT_EXTENSIONS))#vv #~~ files `stylelint` looks at
STYLELINT_CACHE  ?= $(APP_DIR)/$(_CACHE_DIR)/stylelint.cache.json#v #~~ path to the `stylelint` cache
STYLELINT_FLAGS  ?= --allow-empty-input --max-warnings 0 --ignore-path $(APP_DIR)/.gitignore --ignore-path $(APP_DIR)/.stylelintignore $(if $(_IS_NO_COLOR),--no-color)#v #~~ additional flags passed to `stylelint`

_STYLELINT_VALUE_FLAGS := --cache-location \
	--cache-strategy \
	--config-basedir \
	--config \
	--custom-formatter \
	--custom-syntax \
	--formatter \
	--globby-options \
	--go \
	--ignore-path \
	--ignore-pattern \
	--ip \
	--max-warnings \
	--mw \
	--output-file \
	--stdin-filename \
	--suppress-location \
	-c \
	-f \
	-i \
	-o

STYLELINT_EXTENSIONS ?= $(_STYLELINT_EXTENSIONS)#vv #~~ extensions `stylelint` parses, set by its config's syntaxes

_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(STYLELINT_CONFIG),mjs),$(STYLELINT_EXTENSIONS))

$(call _target,stylelint): #~~ runs `stylelint`, writing fixes unless `--dry-run`
	$(TRACE_PREFIX)$(call _require_file,$(STYLELINT_CONFIG),STYLELINT_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(STYLELINT) --config $(STYLELINT_CONFIG) --config-basedir $(APP_DIR) --cache --cache-location $(STYLELINT_CACHE) $(STYLELINT_FLAGS) \
		$(if $(_IS_DEBUG),--formatter verbose) $(if $(_IS_DRY_RUN),,--fix) \
		$(if $(call _has_positional_arg,$(call _without_dry_run,$(_ARGS)),$(_STYLELINT_VALUE_FLAGS)), \
			$(_FIXER_ARGS), \
			$(_FIXER_ARGS) '$(STYLELINT_GLOB)'))

$(call _target,stylelint-dry-run): $(call _target,stylelint) #~~ runs `stylelint` without writing fixes #v
$(call _target,stylelint-dry-run): _GOAL := $(call _target,stylelint-dry-run)

$(call _target,stylelint-group): #~~ counts the `stylelint` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(STYLELINT_CONFIG),STYLELINT_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(STYLELINT) --config $(STYLELINT_CONFIG) --config-basedir $(APP_DIR) --cache --cache-location $(STYLELINT_CACHE) $(STYLELINT_FLAGS) --formatter json $(if $(call _has_positional_arg,$(ARGS),$(_STYLELINT_VALUE_FLAGS)),$(ARGS),$(ARGS) '$(STYLELINT_GLOB)'),rule,text)

$(call _target,stylelint-print): #~~ prints the resolved `stylelint` config for a file #vv
	$(TRACE_PREFIX)$(call _require_file,$(STYLELINT_CONFIG),STYLELINT_CONFIG)
	$(TRACE_PREFIX)$(call _require_file_argument,$(call _target,stylelint-print),$(_STYLELINT_VALUE_FLAGS))
	$(DEBUG_PREFIX)$(STYLELINT) --config $(STYLELINT_CONFIG) --print-config $(ARGS)

endif # /stylelint

#--- typescript

ifneq ($(_HAS_TYPESCRIPT),) # typescript

TYPESCRIPT            ?= $(BUN) $(BUN_FLAGS) x tsc#v #~~ command that runs `tsc`
TYPESCRIPT_CONFIG     ?= $(APP_DIR)/tsconfig.json#v #~~ path to the `tsc` project
TYPESCRIPT_FLAGS      ?= --noEmit $(if $(_IS_DEBUG),--extendedDiagnostics)#v #~~ additional flags passed to `tsc`
TYPESCRIPT_EXTENSIONS ?= $(_TYPESCRIPT_EXTENSIONS)#vv #~~ extensions `tsc` type checks

_TYPESCRIPT_PROJECT := $(APP_DIR)/$(_CONFIG_DIR)/tsconfig.json

_PLAIN_CONFIGS  += $(call _when_tracked,$(_TYPESCRIPT_PROJECT),$(TYPESCRIPT_EXTENSIONS))
_LINKED_CONFIGS += $(call _when_tracked,$(call _host_path,$(TYPESCRIPT_CONFIG))|$(call _host_path,$(_TYPESCRIPT_PROJECT)),$(TYPESCRIPT_EXTENSIONS))

$(call _target,typescript): #~~ runs the `tsc` type check
	$(TRACE_PREFIX)$(call _require_file,$(TYPESCRIPT_CONFIG),TYPESCRIPT_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(TYPESCRIPT) --project $(TYPESCRIPT_CONFIG) $(TYPESCRIPT_FLAGS) $(ARGS))

$(call _target,typescript-group): #~~ counts the `tsc` errors by code #v
	$(TRACE_PREFIX)$(call _require_file,$(TYPESCRIPT_CONFIG),TYPESCRIPT_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(TYPESCRIPT) --project $(TYPESCRIPT_CONFIG) $(TYPESCRIPT_FLAGS) --pretty false $(ARGS))

$(call _target,typescript-list): #~~ lists the files the `tsc` project includes #vv
	$(TRACE_PREFIX)$(call _require_file,$(TYPESCRIPT_CONFIG),TYPESCRIPT_CONFIG)
	$(DEBUG_PREFIX)$(TYPESCRIPT) --project $(TYPESCRIPT_CONFIG) --listFilesOnly $(ARGS)

$(call _target,typescript-print): #~~ prints the resolved `tsc` project #vv
	$(TRACE_PREFIX)$(call _require_file,$(TYPESCRIPT_CONFIG),TYPESCRIPT_CONFIG)
	$(DEBUG_PREFIX)$(TYPESCRIPT) --project $(TYPESCRIPT_CONFIG) --showConfig $(ARGS)

endif # /typescript

#--- vitest

ifneq ($(_HAS_VITEST),) # vitest

VITEST            ?= $(BUN) $(BUN_FLAGS) x vitest#v #~~ command that runs `vitest`
unexport VITEST    # NOTICE: Vitest sets this to mark its own runs
VITEST_CONFIG     ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/vitest.ts $(APP_DIR)/$(_CONFIG_DIR)/vitest.mjs $(APP_DIR)/$(_CONFIG_DIR)/vitest.dist.mjs,js)#v #~~ path to `vitest` config
VITEST_EXTENSIONS ?= $(_VITEST_EXTENSIONS)#vv #~~ extensions `vitest` runs tests from
VITEST_FLAGS      ?= --run --passWithNoTests $(if $(_IS_DEBUG),--reporter=verbose)#v #~~ additional flags passed to `vitest`

VITEST_COVERAGE_DIR            ?= $(APP_DIR)/$(_CACHE_DIR)/vitest.cache/coverage#v #~~ directory the HTML coverage report is written to
VITEST_MIN_COVERAGE            ?= 100#v #~~ percentage every metric must reach, `0` for no floor
VITEST_MIN_COVERAGE_BRANCHES   ?= $(VITEST_MIN_COVERAGE)#vv #~~ percentage the branch metric must reach
VITEST_MIN_COVERAGE_FUNCTIONS  ?= $(VITEST_MIN_COVERAGE)#vv #~~ percentage the function metric must reach
VITEST_MIN_COVERAGE_LINES      ?= $(VITEST_MIN_COVERAGE)#vv #~~ percentage the line metric must reach
VITEST_MIN_COVERAGE_STATEMENTS ?= $(VITEST_MIN_COVERAGE)#vv #~~ percentage the statement metric must reach

VITEST_COVERAGE_FLAGS?= --coverage \
	--coverage.reporter=text \
	--coverage.reporter=html \
	--coverage.reportsDirectory=$(VITEST_COVERAGE_DIR) \
	--coverage.thresholds.branches=$(VITEST_MIN_COVERAGE_BRANCHES) \
	--coverage.thresholds.functions=$(VITEST_MIN_COVERAGE_FUNCTIONS) \
	--coverage.thresholds.lines=$(VITEST_MIN_COVERAGE_LINES) \
	--coverage.thresholds.statements=$(VITEST_MIN_COVERAGE_STATEMENTS)#v #~~ additional flags passed when collecting coverage

_LAYERED_CONFIGS += $(if $(wildcard $(CURDIR)/tests),$(call _when_tracked,$(call _layered,$(VITEST_CONFIG),mjs),$(VITEST_EXTENSIONS)))

$(call _target,vitest): #~~ runs `vitest`
	$(TRACE_PREFIX)$(call _require_file,$(VITEST_CONFIG),VITEST_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(VITEST) --config $(VITEST_CONFIG) $(VITEST_FLAGS) $(ARGS))

ifneq ($(_HAS_VITEST_COVERAGE),) # vitest-coverage

$(call _target,vitest-coverage): #~~ runs `vitest` and reports how much of the source it reaches #v
	$(TRACE_PREFIX)$(call _require_file,$(VITEST_CONFIG),VITEST_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(VITEST) --config $(VITEST_CONFIG) $(VITEST_FLAGS) $(VITEST_COVERAGE_FLAGS) $(ARGS))

endif # /vitest-coverage

$(call _target,vitest-list): #~~ lists the tests `vitest` collects #vv
	$(TRACE_PREFIX)$(call _require_file,$(VITEST_CONFIG),VITEST_CONFIG)
	$(DEBUG_PREFIX)$(VITEST) list --config $(VITEST_CONFIG) $(ARGS)

$(call _target,vitest-update): #~~ runs `vitest` and updates its snapshots #v
	$(TRACE_PREFIX)$(call _require_file,$(VITEST_CONFIG),VITEST_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(VITEST) --config $(VITEST_CONFIG) $(VITEST_FLAGS) $(ARGS) --update)

endif # /vitest

endif # /js

#--- hadolint

HADOLINT_CONFIG  ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/hadolint.yaml $(APP_DIR)/$(_CONFIG_DIR)/hadolint.dist.yaml,)#v #~~ path to `hadolint` config
_LAYERED_CONFIGS += $(if $(_HADOLINT_FILES),$(call _layered,$(HADOLINT_CONFIG),yaml))

ifneq ($(_HAS_HADOLINT),) # hadolint

HADOLINT       ?= $(RUN) hadolint#v #~~ command that runs `hadolint`
HADOLINT_FILES ?= $(_HADOLINT_FILES)#vv #~~ dockerfiles `hadolint` reads
HADOLINT_FLAGS ?= $(if $(_IS_NO_COLOR),--no-color)#v #~~ additional flags passed to `hadolint`

_QUOTED_HADOLINT_FILES = $(call _shell_quoted,$(HADOLINT_FILES))

_HADOLINT_VALUE_FLAGS := --config \
	--failure-threshold \
	--format \
	-c \
	-f \
	-t

$(call _target,hadolint): #~~ runs `hadolint` over this project's dockerfiles
	$(TRACE_PREFIX)$(call _require_file,$(HADOLINT_CONFIG),HADOLINT_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(HADOLINT) --config $(HADOLINT_CONFIG) $(HADOLINT_FLAGS) \
		$(if $(call _has_positional_arg,$(_ARGS),$(_HADOLINT_VALUE_FLAGS)),$(ARGS),$(ARGS) $(_QUOTED_HADOLINT_FILES)))

$(call _target,hadolint-group): #~~ counts the `hadolint` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(HADOLINT_CONFIG),HADOLINT_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(HADOLINT) --config $(HADOLINT_CONFIG) $(HADOLINT_FLAGS) --format json \
		$(if $(call _has_positional_arg,$(ARGS),$(_HADOLINT_VALUE_FLAGS)),$(ARGS),$(ARGS) $(_QUOTED_HADOLINT_FILES)),code,message)

$(call _target,hadolint-list): #~~ lists the dockerfiles `hadolint` reads #vv
	$(TRACE_PREFIX)$(if $(HADOLINT_FILES),$(PRINTF) '%s\n' $(_QUOTED_HADOLINT_FILES),$(call log,No dockerfile to lint.,$(COLOR_NOTICE)))

$(call _target,hadolint-print): #~~ prints the resolved `hadolint` configuration #vv
	$(TRACE_PREFIX)$(call _require_file,$(HADOLINT_CONFIG),HADOLINT_CONFIG)
	$(DEBUG_PREFIX)$(HADOLINT) --config $(HADOLINT_CONFIG) --verbose $(HADOLINT_FLAGS) - </dev/null

endif # /hadolint

#--- actionlint

ACTIONLINT_CONFIG ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/actionlint.yaml $(APP_DIR)/$(_CONFIG_DIR)/actionlint.dist.yaml,)#v #~~ path to `actionlint` config
_LAYERED_CONFIGS  += $(if $(_ACTIONLINT_FILES),$(call _layered,$(ACTIONLINT_CONFIG),yaml))

ifneq ($(_HAS_ACTIONLINT),) # actionlint

ACTIONLINT       ?= $(RUN) actionlint#v #~~ command that runs `actionlint`
ACTIONLINT_FILES ?= $(_ACTIONLINT_FILES)#vv #~~ workflows `actionlint` reads
ACTIONLINT_FLAGS ?= $(if $(_IS_DEBUG),-verbose) $(if $(_IS_NO_COLOR),-no-color)#v #~~ additional flags passed to `actionlint`

_QUOTED_ACTIONLINT_FILES = $(call _shell_quoted,$(ACTIONLINT_FILES))

_ACTIONLINT_VALUE_FLAGS := -config-file \
	-format \
	-ignore \
	-pyflakes \
	-shellcheck

$(call _target,actionlint): #~~ runs `actionlint` over this project's workflows
	$(TRACE_PREFIX)$(call _require_file,$(ACTIONLINT_CONFIG),ACTIONLINT_CONFIG)
	$(TRACE_PREFIX)$(call _spinner,$(ACTIONLINT) -config-file $(ACTIONLINT_CONFIG) $(ACTIONLINT_FLAGS) \
		$(if $(call _has_positional_arg,$(_ARGS),$(_ACTIONLINT_VALUE_FLAGS)),$(ARGS),$(ARGS) $(_QUOTED_ACTIONLINT_FILES)))

$(call _target,actionlint-group): #~~ counts the `actionlint` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(ACTIONLINT_CONFIG),ACTIONLINT_CONFIG)
	$(TRACE_PREFIX)$(call _group,$(ACTIONLINT) -config-file $(ACTIONLINT_CONFIG) $(ACTIONLINT_FLAGS) -format '{{json .}}' \
		$(if $(call _has_positional_arg,$(ARGS),$(_ACTIONLINT_VALUE_FLAGS)),$(ARGS),$(ARGS) $(_QUOTED_ACTIONLINT_FILES)),kind,message)

$(call _target,actionlint-list): #~~ lists the workflows `actionlint` reads #vv
	$(TRACE_PREFIX)$(if $(ACTIONLINT_FILES),$(PRINTF) '%s\n' $(_QUOTED_ACTIONLINT_FILES),$(call log,No workflow to lint.,$(COLOR_NOTICE)))

endif # /actionlint

#--- semgrep

ifneq ($(_HAS_SEMGREP),) # semgrep

SEMGREP          ?= $(RUN) semgrep#v #~~ command that runs `semgrep`
SEMGREP_CONFIG   ?= $(call _config,$(APP_DIR)/$(_CONFIG_DIR)/semgrep.yaml $(APP_DIR)/$(_CONFIG_DIR)/semgrep.dist.yaml,)#v #~~ path to `semgrep` config
SEMGREP_RULESETS ?= $(_SEMGREP_RULESETS)#v #~~ registry rulesets, detected unless `x-brnshkr` sets them
SEMGREP_FLAGS    ?= --error --quiet --metrics off --disable-version-check --no-rewrite-rule-ids $(if $(_IS_NO_COLOR),--no-color)#v #~~ additional flags passed to `semgrep`
SEMGREP_CACHE    ?= $(APP_DIR)/$(_CACHE_DIR)/semgrep#vv #~~ path to the `semgrep` ruleset cache
SEMGREP_MAX_AGE  ?= 7#vv #~~ days a cached ruleset is kept before it is fetched again
SEMGREP_REGISTRY ?= https://semgrep.dev/c/p#vvv #~~ address `semgrep` fetches its rulesets from
SEMGREP_PYTHON   ?= $(RUN) python3#vvv #~~ command that runs `python3`, merging `semgrep` rulesets

_LAYERED_CONFIGS += $(call _layered,$(SEMGREP_CONFIG),yaml)

_SEMGREP_DATABASE_PACKAGES := $(_COMPOSER_VENDOR_DIR)/doctrine/dbal \
	$(_COMPOSER_VENDOR_DIR)/doctrine/orm \
	$(_COMPOSER_VENDOR_DIR)/illuminate/database \
	node_modules/@prisma/client \
	node_modules/better-sqlite3 \
	node_modules/drizzle-orm \
	node_modules/mysql2 \
	node_modules/pg

_SEMGREP_CLIENT_PACKAGES := node_modules/@angular/core \
	node_modules/lit \
	node_modules/preact \
	node_modules/react \
	node_modules/solid-js \
	node_modules/svelte \
	node_modules/vue

_SEMGREP_DETECTED_RULESETS = default \
	gitleaks \
	owasp-top-ten \
	secrets \
	security-audit \
	$(if $(_HAS_PHP),php phpcs-security-audit) \
	$(if $(_HAS_JS),typescript javascript nodejs nodejsscan mcp ai-best-practices) \
	$(call _when_installed,sql-injection,$(_SEMGREP_DATABASE_PACKAGES)) \
	$(call _when_installed,clientside-js,$(_SEMGREP_CLIENT_PACKAGES)) \
	$(call _when_installed,php-laravel,$(_COMPOSER_VENDOR_DIR)/laravel/framework) \
	$(call _when_installed,wordpress,$(_COMPOSER_VENDOR_DIR)/johnpbloch/wordpress-core $(_COMPOSER_VENDOR_DIR)/roots/wordpress) \
	$(call _when_installed,react,node_modules/react) \
	$(call _when_installed,nextjs,node_modules/next) \
	$(call _when_installed,nestjs,node_modules/@nestjs/core) \
	$(call _when_installed,expressjs,node_modules/express) \
	$(call _when_installed,koa,node_modules/koa) \
	$(call _when_installed,hapi,node_modules/@hapi/hapi) \
	$(call _when_installed,electron-desktop-app,node_modules/electron) \
	$(call _when_installed,headless-browser,node_modules/playwright node_modules/playwright-core node_modules/puppeteer) \
	$(if $(_HADOLINT_FILES),dockerfile) \
	$(if $(_ACTIONLINT_FILES),github-actions) \
	$(if $(findstring compose,$(filter %.yaml %.yml,$(_TRACKED_FILES))),docker-compose) \
	$(if $(filter %Chart.yaml k8s/% kubernetes/% helm/%,$(_TRACKED_FILES)),kubernetes) \
	$(if $(or $(findstring nginx,$(filter %.conf,$(_TRACKED_FILES))),$(filter nginx/%,$(_TRACKED_FILES))),nginx) \
	$(if $(filter %.tf,$(_TRACKED_FILES)),terraform) \
	$(if $(_HAS_SEMGREP_RULES),semgrep-rule-lints)

_HAS_SEMGREP_RULES = $(eval _HAS_SEMGREP_RULES := $$(shell $$(AWK) '/^  - id:/ { found = 1; exit } END { exit !found }' \
	'$$(call _host_path,$$(SEMGREP_CONFIG))' 2>/dev/null && $$(PRINTF) 1))$(_HAS_SEMGREP_RULES)
_SEMGREP_OVERRIDES = $(eval _SEMGREP_OVERRIDES := $$(call _semgrep_overrides,$$(call _host_path,$$(SEMGREP_CONFIG))))$(_SEMGREP_OVERRIDES)

_SEMGREP_RULESETS = $(sort $(filter-out $(patsubst -%,%,$(filter -%,$(_SEMGREP_OVERRIDES))), \
	$(_SEMGREP_DETECTED_RULESETS) $(patsubst +%,%,$(filter +%,$(_SEMGREP_OVERRIDES)))))

_SEMGREP_IGNORES = $(filter =% ~%,$(_SEMGREP_OVERRIDES))
_SEMGREP_CONFIGS = --config $(SEMGREP_CACHE)/rules.json --config $(SEMGREP_CONFIG)

_SEMGREP_VALUE_FLAGS := --config \
	--exclude \
	--include \
	--json-output \
	--max-target-bytes \
	--severity \
	--timeout \
	-c \
	-e

$(call _target,semgrep): #~~ runs `semgrep` over this project's sources
	$(TRACE_PREFIX)$(call _require_file,$(SEMGREP_CONFIG),SEMGREP_CONFIG)
	$(TRACE_PREFIX)$(call _semgrep_rules)
	$(TRACE_PREFIX)$(call _spinner,$(SEMGREP) scan $(_SEMGREP_CONFIGS) $(SEMGREP_FLAGS) $(ARGS))

$(call _target,semgrep-group): #~~ counts the `semgrep` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(SEMGREP_CONFIG),SEMGREP_CONFIG)
	$(TRACE_PREFIX)$(call _semgrep_rules)
	$(TRACE_PREFIX)$(call _group,$(SEMGREP) scan $(_SEMGREP_CONFIGS) $(SEMGREP_FLAGS) --json $(ARGS),check_id,message)

$(call _target,semgrep-list): #~~ lists the rulesets `semgrep` runs #vv
	$(TRACE_PREFIX)$(PRINTF) '%s\n' $(SEMGREP_RULESETS)

$(call _target,semgrep-validate): #~~ checks that the `semgrep` rules load #vv
	$(TRACE_PREFIX)$(call _require_file,$(SEMGREP_CONFIG),SEMGREP_CONFIG)
	$(TRACE_PREFIX)$(call _semgrep_rules)
	$(DEBUG_PREFIX)$(SEMGREP) scan $(_SEMGREP_CONFIGS) --validate $(SEMGREP_FLAGS) $(ARGS)

$(call _target,semgrep) $(call _target,semgrep-group) $(call _target,semgrep-validate): export _SEMGREP_RULES_PROGRAM = $(_SEMGREP_RULES_PROGRAM_SOURCE)

endif # /semgrep

#--- changelog

CHANGELOG_RANGE ?= #v #~~ commit range the changelog covers
CHANGELOG_URL   ?= #v #~~ base URL a commit hash links to
CHANGELOG_DIR   ?= $(CURDIR)/changelog#vv #~~ directory the release notes are written to
CHANGELOG_TITLE ?= \# Changelog#vv #~~ heading a new changelog file starts with

CHANGELOG_SKIPPED_COMMITS ?= chore(hygiene) \
	chore(release)#vv #~~ `type(scope)` pairs left out of the changelog

CHANGELOG_SKIPPED_TYPES ?= test	\
	style \
	build#vv #~~ commit types left out of the changelog

CHANGELOG_GROUPS ?= dependencies=📦_Dependencies \
	a11y=♿_Accessibility \
	admin=🛠️_Admin \
	ai=🤖_AI \
	api=🔌_API \
	assets=🖼️_Assets \
	auth=🔐_Auth \
	backend=🧱_Backend \
	cache=🗃️_Cache \
	ci=⚙️_CI \
	cli=⌨️_CLI \
	composer=📦_Composer \
	config=🔧_Configuration \
	db=🗄️_Database \
	docker=🐳_Docker \
	docs=📚_Documentation \
	events=📡_Events \
	forms=📝_Forms \
	frontend=🖥️_Frontend \
	i18n=🌍_Translations \
	js=☕_JS \
	logging=🧾_Logging \
	mail=✉️_Mail \
	make=🔨_Make \
	payment=💳_Payment \
	perf=⚡_Performance \
	php=🐘_PHP \
	queue=📬_Queue \
	readme=📚_Documentation \
	release=🔖_Release \
	routing=🧭_Routing \
	rule=🧩_Rules \
	schedule=⏰_Schedule \
	search=🔍_Search \
	security=🔒_Security \
	seo=🔎_SEO \
	storage=💾_Storage \
	templates=🖌️_Templates \
	test=🧪_Tests \
	twig=🌿_Twig \
	ui=🎨_UI \
	vscode=💻_Editor \
	general=🧰_General#vv #~~ headings per root, as `<root>=<heading>` pairs, `_` a space

CHANGELOG_NAMES ?= a11y=a11y \
	acl=ACL \
	apcu=APCu \
	api=API \
	aws=AWS \
	bdd=BDD \
	cdn=CDN \
	cgi=CGI \
	cli=CLI \
	cms=CMS \
	commitlint=commitlint \
	cors=CORS \
	csp=CSP \
	css=CSS \
	csv=CSV \
	db=Database \
	dbal=DBAL \
	di=DI \
	dns=DNS \
	dto=DTO \
	dx=DX \
	esbuild=esbuild \
	eslint=ESLint \
	fastcgi=FastCGI \
	ffi=FFI \
	fpm=FPM \
	frankenphp=FrankenPHP \
	gcp=GCP \
	github=GitHub \
	gitlab=GitLab \
	graphql=GraphQL \
	grpc=gRPC \
	gui=GUI \
	html=HTML \
	http=HTTP \
	i18n=i18n \
	iam=IAM \
	ide=IDE \
	ini=INI \
	ios=iOS \
	ip=IP \
	jetbrains=JetBrains \
	js=JS \
	jsdoc=JSDoc \
	json=JSON \
	jwt=JWT \
	k8s=K8s \
	kubernetes=Kubernetes \
	l10n=l10n \
	less=Less \
	macos=macOS \
	mariadb=MariaDB \
	markdownlint=markdownlint \
	md=Markdown \
	mdx=MDX \
	mfa=MFA \
	mongodb=MongoDB \
	mysql=MySQL \
	nestjs=NestJS \
	nginx=Nginx \
	nodejs=Node.js \
	npm=npm \
	oauth=OAuth \
	oidc=OIDC \
	opcache=OPcache \
	orm=ORM \
	os=OS \
	pcov=PCOV \
	pdo=PDO \
	php-cs-fixer=PHP-CS-Fixer \
	php=PHP \
	phpat=PHPat \
	phpdoc=PHPDoc \
	phpstan=PHPStan \
	phpstorm=PhpStorm \
	phpunit=PHPUnit \
	pnpm=pnpm \
	postgres=PostgreSQL \
	psr=PSR \
	qa=QA \
	rabbitmq=RabbitMQ \
	rbac=RBAC \
	rest=REST \
	roadrunner=RoadRunner \
	rpc=RPC \
	saml=SAML \
	sass=Sass \
	scss=SCSS \
	sdk=SDK \
	seo=SEO \
	sql=SQL \
	sqlite=SQLite \
	ssh=SSH \
	ssl=SSL \
	sso=SSO \
	ssr=SSR \
	svg=SVG \
	tdd=TDD \
	tls=TLS \
	toml=TOML \
	ts=TS \
	twig-cs-fixer=Twig-CS-Fixer \
	typedoc=TypeDoc \
	typescript=TypeScript \
	ui=UI \
	ulid=ULID \
	uri=URI \
	url=URL \
	utf8=UTF-8 \
	uuid=UUID \
	ux=UX \
	wasm=WASM \
	webpack=webpack \
	xhprof=XHProf \
	xml=XML \
	yaml=YAML \
	yarn=Yarn \
	yml=YAML#vv #~~ title-casing fixes, as `<scope>=<name>` pairs, `_` a space

_CHANGELOG_LAST_TAG = $(eval _CHANGELOG_LAST_TAG := $$(shell $$(GIT) describe --tags --abbrev=0 2>/dev/null))$(_CHANGELOG_LAST_TAG)
_IS_CHANGELOG_ALL   = $(filter all a,$(_ARG_WORDS))
_IS_CHANGELOG_WRITE = $(filter write w,$(_ARG_WORDS))
_IS_CHANGELOG_NOTES = $(filter notes n,$(_ARG_WORDS))
_CHANGELOG_FILE     = $(CHANGELOG_DIR)/$(_CHANGELOG_MAJOR).x.md
_CHANGELOG_MAJOR    = $(firstword $(subst ., ,$(patsubst v%,%,$(_CHANGELOG_VERSION))))
_CHANGELOG_RANGE    = $(if $(_IS_CHANGELOG_ALL),,$(or $(CHANGELOG_RANGE),$(if $(_CHANGELOG_LAST_TAG),$(_CHANGELOG_LAST_TAG)..HEAD)))
_CHANGELOG_PARTS    = $(subst .., ,$(subst ...,..,$(_CHANGELOG_RANGE)))
_CHANGELOG_START    = $(word 1,$(_CHANGELOG_PARTS))
_CHANGELOG_END      = $(word 2,$(_CHANGELOG_PARTS))
_CHANGELOG_VERSION  = $(shell $(GIT) describe --tags --exact-match '$(_CHANGELOG_END)' 2>/dev/null || $(PRINTF) '%s' '$(VERSION)')
_CHANGELOG_COMPARE  = $(if $(and $(CHANGELOG_URL),$(_CHANGELOG_START)),$(CHANGELOG_URL)/compare/$(_CHANGELOG_START)...$(_CHANGELOG_END))

_CHANGELOG_ODD_TAG = $(shell { $(GIT) describe --tags --abbrev=0; $(GIT) describe --tags --exact-match '$(_CHANGELOG_END)'; } 2>/dev/null \
	| $(AWK) '!/^[A-Za-z0-9._+\/-]+$$/ { print; exit }')

$(call _target,changelog): #~~ prints the changelog, or writes it to `CHANGELOG_DIR` #v
	$(if $(_CHANGELOG_ODD_TAG),$(error [$(_LABEL)] `$(_CHANGELOG_ODD_TAG)` does not name a tag `changelog` can read, which takes only letters, digits, `.`, `_`, `-`, `+` and `/`))
	$(TRACE_PREFIX)$(call _capture,$(GIT) log --no-merges --pretty=format:'%H%x1f%D%x1f%s%x1f%b%x1e' $(_CHANGELOG_RANGE)) \
		| _CHANGELOG_URL="$(CHANGELOG_URL)" \
			_CHANGELOG_VERSION="$(_CHANGELOG_VERSION)" \
			_CHANGELOG_COMPARE="$(_CHANGELOG_COMPARE)" \
			_CHANGELOG_ALL="$(_IS_CHANGELOG_ALL)" \
			_CHANGELOG_COMMITS="$(CHANGELOG_SKIPPED_COMMITS)" \
			_CHANGELOG_TYPES="$(CHANGELOG_SKIPPED_TYPES)" \
			_CHANGELOG_MAJOR="$(if $(_IS_CHANGELOG_WRITE),$(_CHANGELOG_MAJOR))" \
			_CHANGELOG_GROUPS="$(CHANGELOG_GROUPS)" \
			_CHANGELOG_NAMES="$(CHANGELOG_NAMES)" \
			$(AWK) "$$_CHANGELOG_AWK" \
		| { $(if $(_IS_CHANGELOG_WRITE),$(call _changelog_write),$(if $(_IS_CHANGELOG_NOTES),$(call _changelog_notes),$(call _changelog_print))); }

#--- common

_FIXERS    := $(foreach FIXER_NAME,$(_FIXER_NAMES),$(call _target,$(FIXER_NAME)))
_DRY_RUNS  := $(foreach FIXER_NAME,$(_FIXER_NAMES),$(call _target,$(FIXER_NAME)-dry-run))
_ANALYZERS := $(foreach ANALYZER_NAME,$(_ANALYZER_NAMES),$(call _target,$(ANALYZER_NAME)))
_TESTS     := $(foreach TEST_NAME,$(_TEST_NAMES),$(call _target,$(TEST_NAME)))
_UPDATES   := $(foreach TEST_NAME,$(_TEST_NAMES),$(call _target,$(TEST_NAME)-update))
_COVERAGE  := $(foreach COVERAGE_NAME,$(_COVERAGE_NAMES),$(call _target,$(COVERAGE_NAME)-coverage))
_GROUPS    := $(foreach GROUP_NAME,$(_GROUP_NAMES),$(call _target,$(GROUP_NAME)-group))
_PACKS     := $(foreach PACK_NAME,$(_PACK_NAMES),$(call _target,$(PACK_NAME)))
_AUDITS    := $(if $(_HAS_COMPOSER_AUDIT),$(call _target,composer-audit)) $(if $(_HAS_BUN_AUDIT),$(call _target,bun-audit))

ifneq ($(_HAS_AUDIT),)
$(call _target,audit): #~~ checks locked dependencies for known vulnerabilities
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) $(_AUDITS)
endif

CHECK_TARGETS ?= #v #~~ this project's own targets, run by `check`

ifneq ($(_HAS_CHECK),)
$(call _target,check): #~~ runs the fixers without writing, and every analyzer
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) $(_DRY_RUNS) $(_ANALYZERS) $(CHECK_TARGETS)
endif

_CI_TARGETS := $(if $(_HAS_CHECK),$(call _target,check)) \
	$(if $(_HAS_AUDIT),$(call _target,audit)) \
	$(if $(_HAS_TEST),$(call _target,test))

CI_TARGETS ?= $(_CI_TARGETS)#vv #~~ targets `ci` runs in order

ifneq ($(_HAS_CI),)
$(call _target,ci): #~~ runs `CI_TARGETS` one after the other
	$(DEBUG_PREFIX)$(foreach TARGET,$(CI_TARGETS), \
		$(MAKE) $(_VERB_FLAGS) $(TARGET) && \
	):
endif

ifneq ($(_HAS_COVERAGE),)
$(call _target,coverage): #~~ runs the tests and fails below the minimum coverage
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) $(_COVERAGE)
endif

_FIX_ORDER := rector+php-cs-fixer \
	twig-cs-fixer \
	eslint+stylelint+markdownlint

FIX_TARGETS ?= #v #~~ this project's own targets, run by `fix`

ifneq ($(_HAS_FIX),)
$(call _target,fix): #~~ runs every fixer this project has, writing its fixes
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) $(_FIXERS) $(FIX_TARGETS)
endif

#**
#* Makes each goal of a fixer chain wait for the ones before it, so `-j` keeps the order.
#*
#* parameters:
#*   goals: list<string>
#*
#* returns: string
#*
define _order_fixers
$(if $(word 2,$1),$(lastword $1): | $(filter-out $(lastword $1),$1)$(_NEWLINE)$(call _order_fixers,$(filter-out $(lastword $1),$1)))
endef

$(foreach CHAIN,$(_FIX_ORDER),$(eval $(call _order_fixers,$(filter $(MAKECMDGOALS),$(foreach NAME,$(subst +, ,$(CHAIN)),$(call _target,$(NAME)))))))

GROUP_TARGETS ?= #v #~~ this project's own targets, run by `group`

ifneq ($(_HAS_GROUP),)
$(call _target,group): #~~ counts every tool's findings by identifier
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) $(_GROUPS) $(GROUP_TARGETS)
endif

$(call _target,pack): $(_PACKS) #~~ packs every stack's package to `./.local` #vvv
	$(TRACE_PREFIX)$(if $(_PACKS),:,$(call log,No package to pack.,$(COLOR_NOTICE)))

TEST_TARGETS ?= #v #~~ this project's own targets, run by `test`

ifneq ($(_HAS_TEST),)
$(call _target,test): #~~ runs the tests of every stack this project has
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) $(_TESTS) $(TEST_TARGETS)
endif

ifneq ($(_HAS_TEST_UPDATE),)
$(call _target,test-update): #~~ runs those tests and updates their snapshots
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) $(_UPDATES)
endif

FIXTURE_TARGETS ?= #v #~~ this project's own targets, run by `fixtures` for its tests

$(call _target,fixtures): #~~ builds the fixtures this project's tests read #v
	$(TRACE_PREFIX)$(if $(FIXTURE_TARGETS),:,$(call log,No fixture to build.,$(COLOR_NOTICE)))
	$(DEBUG_PREFIX)$(if $(FIXTURE_TARGETS),$(MAKE) $(_VERB_FLAGS) --no-keep-going $(FIXTURE_TARGETS),:)

_FILE_GOALS := $(if $(_NAMING_GOAL),,$(wildcard $(filter-out $(_ALL_TARGETS),$(MAKECMDGOALS))))
_NAME_WORDS := $(strip $(foreach WORD,$(filter-out $(_ALL_TARGETS),$(_NAME_GOALS)),$(if $(findstring %,$(WORD))$(findstring :,$(WORD))$(findstring /,$(WORD)),,$(WORD))))

ifneq ($(_FILE_GOALS),)
.PHONY: $(_FILE_GOALS)
$(_FILE_GOALS): ; @:
endif

ifneq ($(_NAMED_TARGETS),)
.PHONY: $(_NAMED_TARGETS)
$(_NAMED_TARGETS): ; @:
endif

ifneq ($(_NAME_WORDS),)
$(_NAME_WORDS):: ; @:
endif

.DEFAULT:
ifeq ($(TARGET),) # unknown
	$(DEBUG_PREFIX)$(if $(_ALIAS_GOAL),exec $(MAKE) $(_MAKE_FLAGS) $(_ALIAS_GOAL) \
		$(if $(_UNKNOWN_ARGS),-- $(_UNKNOWN_ARGS)),:)
	$(TRACE_PREFIX)$(if $(_ALIAS_TARGET),$(if $(_ALIAS_GOAL),:,{ \
		$(call log,`%s` aliases `%s`$(_COMMA) which is not a target.,$(COLOR_ERROR),$(call _quoted,$@) $(call _quoted,$(_ALIAS_TARGET))); \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}),:)
	$(TRACE_PREFIX)$(if $(_ALIAS_TARGET),:,$(if $(_IS_FIRST_GOAL),$(call log,Unknown command `%s`.,$(COLOR_ERROR),$(call _quoted,$@)),:))
	$(TRACE_PREFIX)$(if $(_ALIAS_TARGET),:,$(if $(_IS_FIRST_GOAL),$(if $(_IS_JUST_PRINT),:,$(call _suggest)),:))
else
	$(TRACE_PREFIX):
endif # /unknown

_PREFLIGHT := _$(_TARGET_PREFIX)assert-no-collisions

$(filter-out $(_PREFLIGHT),$(_ALL_TARGETS)): | $(_PREFLIGHT)

_$(_TARGET_PREFIX)assert-no-collisions:
ifneq ($(_DUPLICATE_TARGETS),) # duplicates
	$(foreach NAME,$(_DUPLICATE_TARGETS),$(TRACE_PREFIX)$(call log,`%s` is defined in %s.,$(COLOR_ERROR),'$(NAME)' '$(call _join_as_quoted_list,$(call _named_path,$(call _definitions_of,$(NAME))))')$(_NEWLINE))
	$(TRACE_PREFIX)$(call log,Rename $(if $(word 2,$(_DUPLICATE_TARGETS)),them,it)$(_COMMA) since this project defines the same name twice.,$(COLOR_NOTICE))
	$(TRACE_PREFIX)exit $(BRNSHKR_CONFIG_ERROR_CODE)
endif # /duplicates
ifneq ($(and $(strip $(TARGET_PREFIX)),$(_COLLIDING_TARGETS)),) # collisions
	$(foreach NAME,$(_COLLIDING_TARGETS),$(TRACE_PREFIX)$(call log,`%s` is defined in %s.,$(COLOR_ERROR),'$(_TARGET_PREFIX)$(NAME)' '$(call _join_as_quoted_list,$(call _named_path,$(call _definitions_of,$(_TARGET_PREFIX)$(NAME))))')$(_NEWLINE))
	$(TRACE_PREFIX)$(call log,Rename $(if $(word 2,$(_COLLIDING_TARGETS)),them,it)$(_COMMA) since `TARGET_PREFIX` is already the namespace this project chose.,$(COLOR_NOTICE))
	$(TRACE_PREFIX)exit $(BRNSHKR_CONFIG_ERROR_CODE)
endif # /collisions
ifneq ($(_NAMED_OWN_TARGETS),) # own names
	$(foreach NAME,$(_NAMED_OWN_TARGETS),$(TRACE_PREFIX)$(call log,`%s` is a target of this project$(_COMMA) so it cannot follow `%s`.,$(COLOR_ERROR),'$(NAME)' '$(_FIRST_GOAL)')$(_NEWLINE))
	$(TRACE_PREFIX)$(if $(word 2,$(_NAMED_OWN_TARGETS)), \
		$(call log,Run them in make calls of their own$(_COMMA) or pass them as `ARGS="%s"`.,$(COLOR_NOTICE),'$(_NAMED_OWN_TARGETS)'), \
		$(call log,Run it in a make call of its own$(_COMMA) or pass it as `ARGS=%s`.,$(COLOR_NOTICE),'$(_NAMED_OWN_TARGETS)'))
	$(TRACE_PREFIX)exit $(BRNSHKR_CONFIG_ERROR_CODE)
endif # /own names

PHONY ?= 1#v #~~ targets marked `.PHONY`, `shared` ones only, or `0` none

.PHONY: $(if $(filter-out $(_FALSE),$(PHONY)),$(if $(filter shared,$(PHONY)),$(_SHARED_TARGETS),$(_ALL_TARGETS)))

#--- ansi

_ANSI_MODIFIER_NORMAL    := 0
_ANSI_MODIFIER_BOLD      := 1
_ANSI_MODIFIER_UNDERLINE := 4
_ANSI_MODIFIER_REVERSE   := 7

_ANSI_COLOR_NORMAL  := 0
_ANSI_COLOR_BLACK   := 30
_ANSI_COLOR_RED     := 31
_ANSI_COLOR_GREEN   := 32
_ANSI_COLOR_YELLOW  := 33
_ANSI_COLOR_BLUE    := 34
_ANSI_COLOR_MAGENTA := 35
_ANSI_COLOR_CYAN    := 36
_ANSI_COLOR_WHITE   := 37

_ANSI_COLOR_BRIGHT_NORMAL  := 0
_ANSI_COLOR_BRIGHT_BLACK   := 90
_ANSI_COLOR_BRIGHT_RED     := 91
_ANSI_COLOR_BRIGHT_GREEN   := 92
_ANSI_COLOR_BRIGHT_YELLOW  := 93
_ANSI_COLOR_BRIGHT_BLUE    := 94
_ANSI_COLOR_BRIGHT_MAGENTA := 95
_ANSI_COLOR_BRIGHT_CYAN    := 96
_ANSI_COLOR_BRIGHT_WHITE   := 97

_ANSI_RESET       = $(if $(_IS_NO_COLOR),,\033[0m)
_ANSI_CLEAR_LINE  = $(if $(_IS_NO_COLOR),,\033[2K)
_ANSI_HIDE_CURSOR = $(if $(_IS_NO_COLOR),,\033[?25l)
_ANSI_SHOW_CURSOR = $(if $(_IS_NO_COLOR),,\033[?25h)

_UPPER_normal  := NORMAL
_UPPER_black   := BLACK
_UPPER_red     := RED
_UPPER_green   := GREEN
_UPPER_yellow  := YELLOW
_UPPER_blue    := BLUE
_UPPER_magenta := MAGENTA
_UPPER_cyan    := CYAN
_UPPER_white   := WHITE

#---v helpers

#**
#* Wraps a string in ANSI color and modifier escapes, resetting after it.
#* The color and the modifiers may be given together or apart.
#*
#* parameters:
#*   text: string
#*   color_and_or_modifiers?: AnyOf<normal | white | black | red | yellow | green | cyan | blue | magenta | bold | underline | reverse | bright>
#*   modifiers?: AnyOf<normal | bold | underline | reverse | bright>
#*
#* returns: string
#*
define text
$(call _ansi,$2 $3)$1$(_ANSI_RESET)
endef

#**
#* Prints a message behind the `<VENDOR>/<PACKAGE>` label.
#* Anything in backticks renders as inline code, and keeps its backticks where color is off.
#* The message is a `printf` format, so a value goes in as an argument rather than being pasted in.
#* A literal comma has to be written as `$(_COMMA)`.
#* A backticked span that names a target or an existing path links to it, which `no-autolink` turns
#* off where the sentence is about neither.
#*
#* parameters:
#*   message: string
#*   color_and_or_modifiers?: AnyOf<normal | white | black | red | yellow | green | cyan | blue | magenta | bold | underline | reverse | bright>
#*   arguments?: list<string>
#*   autolink?: OneOf<autolink | no-autolink> = autolink
#*
#* returns: void
#*
define log
$(PRINTF) '$(call text,[$(_LABEL)],$2) $1\n' $3$(if $(findstring `,$1$3), \
	| $(call _render_inline_code,print,$(if $(filter no-autolink,$4),,yes)))
endef

#**
#* The command a tool named for a compose service runs through, empty on this machine.
#* The service is started first, once per run, unless a throwaway container is what runs it.
#* A key the `.env` files declare passes by name, so its value never reaches a command line.
#*
#* parameters:
#*   service: string
#*
#* returns: string
#*
define run_in
$(call _enter_service,$1,-T)
endef

#**
#* Asks a yes/no question before something destructive happens.
#* It answers itself where nobody can: under `CI`, or where its input is no terminal, it answers the default.
#* It succeeds on yes, so the caller reads it as a condition and says what it skipped.
#*
#* parameters:
#*   question: string
#*   default?: OneOf<yes | no> = no
#*
#* returns: void
#*
define confirm
{ \
	if [ -n "$(_IS_CI)" ] || [ ! -t 0 ] || ! ( : < /dev/tty ) 2>/dev/null; then \
		$(if $(filter yes,$2),true,false); \
	else \
		$(PRINTF) '%s $(call text,[$(if $(filter yes,$2),Y/n,y/N)],$(COLOR_NOTICE)) ' '$1' \
			| $(call _render_inline_code,printf "%s"$(_COMMA) $$0,yes) >&2 \
			&& trap '$(PRINTF) "\n" >&2; trap - $(_SIGNAL_INTERRUPT); kill -$(_SIGNAL_INTERRUPT) $$$$' $(_SIGNAL_INTERRUPT) \
			&& read -r answer < /dev/tty \
			&& trap - $(_SIGNAL_INTERRUPT) \
			&& case "$$answer" in \
				[yY]|[yY][eE][sS]) true;; \
				"") $(if $(filter yes,$2),true,false);; \
				*) false;; \
			esac; \
	fi; \
}
endef

#**
#* The path on this machine of a file the tools see under `APP_DIR`.
#*
#* parameters:
#*   path: string
#*
#* returns: string
#*
define _host_path
$(patsubst $(APP_DIR)/%,$(CURDIR)/%,$1)
endef

#**
#* The same text, quoted for the shell.
#*
#* parameters:
#*   text: string
#*
#* returns: string
#*
define _quoted
$(_QUOTE)$(subst $(_QUOTE),$(_QUOTE)\$(_QUOTE)$(_QUOTE),$1)$(_QUOTE)
endef

#**
#* The environment the tools are given inside the container: what the files cannot carry themselves.
#*
#* returns: string
#*
define _forwarded_environment
$(foreach KEY,$(_FORWARDED_KEYS),$(KEY)=$(call _quoted,$(call _forwarded_value,$(KEY))) )
endef

#**
#* The value a forwarded key carries, which for the editor URL is the one this machine resolved.
#*
#* parameters:
#*   key: string
#*
#* returns: string
#*
define _forwarded_value
$(if $(filter EDITOR_URL,$1),$(_EDITOR_URL),$($1))
endef

#**
#* Enters a compose service, starting it when it is not running.
#*
#* parameters:
#*   service: string
#*   flags?: string
#*
#* returns: string
#*
define _enter_service
$(if $(_HAS_CONTAINER) \
	,$(if $(_IS_SERVICE_RUN) \
		,$(_COMPOSE) --progress quiet run --rm --no-deps $2, \
		$(call _start_service,$1)$(_COMPOSE) exec $2 \
	) $(addprefix -e ,$(_DECLARED_KEYS)) $1 env $(_forwarded_environment) \
)
endef

#**
#* Starts a service that is not running yet, before the command.
#*
#* parameters:
#*   service: string
#*
#* returns: string
#*
define _start_service
[ -n "$$($(_COMPOSE) ps --status running --quiet $1 2>/dev/null)" ] || $(_COMPOSE) up --detach --wait $1 >/dev/null 2>&1;
endef

#**
#* Turns a list of extensions into the brace glob a tool is handed.
#*
#* parameters:
#*   extensions: list<string>
#*
#* returns: string
#*
define _brace_glob
**/*.{$(subst $(_SPACE),$(_COMMA),$(strip $1))}
endef

#**
#* Whether `--dry-run` is among the given words.
#* The Makefile's own pseudo-flag, since none of these fixers understand it natively; its presence
#* tells the plain target to skip `--fix`.
#*
#* parameters:
#*   words: list<string>
#*
#* returns: string
#*
define _is_dry_run
$(filter --dry-run,$1)
endef

#**
#* The given words with the Makefile's own `--dry-run` pseudo-flag removed, so it never reaches the tool.
#*
#* parameters:
#*   words: list<string>
#*
#* returns: list<string>
#*
define _without_dry_run
$(filter-out --dry-run,$1)
endef

#**
#* The given words without an option, in its `--name value` and its `--name=value` form.
#*
#* parameters:
#*   option: string
#*   words: list<string>
#*
#* returns: list<string>
#*
define _without_option
$(eval _IS_OPTION_VALUE_NEXT :=)$(strip $(foreach WORD,$2,$(if \
	$(_IS_OPTION_VALUE_NEXT),$(eval _IS_OPTION_VALUE_NEXT :=),$(if \
	$(filter $1,$(WORD)),$(eval _IS_OPTION_VALUE_NEXT := 1),$(filter-out $1=%,$(WORD))))))
endef

#**
#* Whether the given words contain a real positional argument.
#* Not a flag, and not the value a preceding flag from the second list consumes.
#*
#* parameters:
#*   words: list<string>
#*   value_flags: list<string>
#*
#* returns: string
#*
define _has_positional_arg
$(strip $(shell $(PRINTF) '%s\n' $(call _shell_quoted,$1) | $(AWK) -v _VALUE_FLAGS=' $(strip $2) ' '\
	{ \
		if (skip) { skip = 0; next } \
		if (index(_VALUE_FLAGS, " " $$0 " ") > 0) { skip = 1; next } \
		if ($$0 != "" && substr($$0, 1, 1) != "-") { print 1; exit } \
	}'))
endef

#**
#* The name a config or a cache answers to, which is its file name without a leading dot, a `.dist` or a suffix.
#*
#* parameters:
#*   path: string
#*
#* returns: string
#*
define _get_short_name
$(firstword $(subst ., ,$(patsubst .%,%,$(notdir $(lastword $(subst |, ,$1))))))
endef

#**
#* The file name a config is written to.
#*
#* parameters:
#*   path: string
#*
#* returns: string
#*
define _get_file_name
$(notdir $(firstword $(subst |, ,$1)))
endef

#**
#* Appends the entries of a shipped JSONC file and of its variant per stack, without their outer lines.
#*
#* parameters:
#*   outer_line_count: int
#*
#* returns: void
#*
define _append_stack_entries
for shipped_part in "$$template_path" $(foreach SUFFIX,$(_STACK_SUFFIXES),"$${template_path%.dist.*}.$(SUFFIX).dist.$${template_path##*.}"); do \
	[ -r "$$shipped_part" ] || continue; \
	[ ! -s "$$body_path" ] || $(PRINTF) '\n' >> "$$body_path"; \
	$(AWK) -v outer_line_count=$1 '{ part_lines[NR] = $$0 } \
		END { for (line_index = outer_line_count + 1; line_index <= NR - outer_line_count; line_index += 1) print part_lines[line_index] }' \
		"$$shipped_part" >> "$$body_path"; \
done
endef

#**
#* The config an editor opens: tracked or shipped, never a private half.
#*
#* parameters:
#*   path: string
#*
#* returns: string
#*
define _get_editor_config
$(addprefix ./,$(patsubst $(APP_DIR)/%,%,$(firstword \
	$(foreach CANDIDATE,$1 $(call _tracked,$1),$(if $(or $(filter $(patsubst $(APP_DIR)/%,%,$(CANDIDATE)),$(_TRACKED_FILES)),$(filter $(addsuffix /%,$(_PACKAGE_CONF_DIRS)),$(CANDIDATE))),$(CANDIDATE))) \
	$(foreach DIRECTORY,$(_PACKAGE_CONF_DIRS),$(call _configs_in,$(DIRECTORY),$(notdir $(call _tracked,$1)))))))
endef

#**
#* A VS Code setting naming a tool's config, where the tool runs and its config exists.
#*
#* parameters:
#*   tool_name: string
#*   key: string
#*   config: string
#*
#* returns: string
#*
define _get_editor_setting
$(if $(and $1,$(call _get_editor_config,$3)),'  "$2": "$(call _get_editor_config,$3)"')
endef

#**
#* Stops a write that a symbolic link in the checkout would carry elsewhere.
#*
#* parameters:
#*   paths: list<string>
#*
#* returns: void
#*
define _refuse_links
for checked_path in $1; do \
	[ ! -L "$$checked_path" ] || { \
		$(call log,`%s` is a symbolic link$(_COMMA) which `configs` does not follow.,$(COLOR_ERROR),"./$${checked_path#$(CURDIR)/}") >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}; \
done
endef

#**
#* Writes a managed config: its marked block, or the whole file.
#*
#* returns: void
#*
define _write_managed_config
case "$$config_path" in */Makefile) $(AWK) '/^###> brnshkr\/config ###$$/ { is_marked = 1 } END { exit !is_marked }' "$$config_path" 2>/dev/null || continue;; esac; \
[ -z "$$template_path" ] || [ -r "$$template_path" ] || continue; \
$(call _refuse_links,"$$config_path" "$${config_path%/*}" "$$template_path"); \
: > "$$body_path"; \
block_anchor=; block_indent=; comment_prefix=; deduplication_mode=lines; is_whole_file=; written_verb=Updated; \
[ -f "$$config_path" ] || written_verb=Created; \
case "$$config_path" in */*) $(MKDIR) -p "$${config_path%/*}";; esac; \
case "$$config_path" in \
	*/.gitignore) $(PRINTF) '%s\n' $(call _shell_quoted,$(_GITIGNORED_PATHS)) > "$$body_path";; \
	*/.gitattributes) $(CAT) "$$template_path" > "$$body_path" $(if $(wildcard $(CURDIR)/composer.json),&& { \
		$(PRINTF) '\n'; $(PHP) -r "$$_GITATTRIBUTES_PROGRAM" '$(APP_DIR)/composer.json' '$(strip $(ARCHIVE_EXTRA_PATHS))'; \
	} >> "$$body_path") || exit $(BRNSHKR_CONFIG_ERROR_CODE);; \
	*/.editorconfig|*/bunfig.toml) $(CAT) "$$template_path" > "$$body_path"; deduplication_mode=sections;; \
	*/settings.json) $(call _append_stack_entries,1); \
		$(if $(filter-out .cache,$(_CACHE_DIR))$(filter-out vendor,$(_COMPOSER_VENDOR_DIR)),$(AWK) -v cache_directory='$(_CACHE_DIR)' -v vendor_directory='$(_COMPOSER_VENDOR_DIR)' 'index($$0, "\"**/.cache/**\"") { sub(/\.cache/, cache_directory) } index($$0, "\"**/vendor/**\"") { sub(/vendor/, vendor_directory) } { print }' "$$body_path" > "$$body_path.renamed" && $(MV) "$$body_path.renamed" "$$body_path" || exit $(BRNSHKR_CONFIG_ERROR_CODE);) \
		$(if $(strip $(_EDITOR_BINARY_SETTINGS)),$(PRINTF) '%s\n' '' '  // Binaries' $(_EDITOR_BINARY_SETTINGS) >> "$$body_path";) \
		$(if $(strip $(_EDITOR_CONFIG_SETTINGS)),$(PRINTF) '%s\n' '' '  // Configs' $(_EDITOR_CONFIG_SETTINGS) >> "$$body_path";) \
		block_anchor='^[{]'; block_indent='  '; comment_prefix='// '; deduplication_mode=keys; \
		[ -f "$$config_path" ] && [ -n "$$($(TR) -d '{}[:space:]' < "$$config_path")" ] || $(PRINTF) '%s\n' '{' '}' > "$$config_path";; \
	*/extensions.json) $(call _append_stack_entries,2); \
		block_anchor='"recommendations"'; block_indent='    '; comment_prefix='// '; \
		[ -f "$$config_path" ] && [ -n "$$($(TR) -d '{}[:space:]' < "$$config_path")" ] || $(PRINTF) '%s\n' '{' '  "recommendations": [' '  ]' '}' > "$$config_path";; \
	*/Makefile) $(AWK) '/^###< brnshkr\/config ###$$/ { is_inside = 0; print } is_inside { print } /^###> brnshkr\/config ###$$/ { is_inside = 1 }' \
		"$$template_path" > "$$body_path"; deduplication_mode=;; \
	*) is_whole_file=1;; \
esac; \
if [ -n "$$is_whole_file" ]; then \
	$(CMP) -s "$$template_path" "$$config_path" 2>/dev/null || { \
		$(CP) "$$template_path" "$$config_path" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
		$(call log,%s `%s`.,$(COLOR_SUCCESS),"$$written_verb" "./$${config_path#$(CURDIR)/}"); \
	}; \
else \
	[ -f "$$config_path" ] || : > "$$config_path"; \
	updated_contents="$$(_MANAGED_BLOCK_OPEN="$${comment_prefix}###> brnshkr/config ###" \
		_MANAGED_BLOCK_CLOSE="$${comment_prefix}###< brnshkr/config ###" \
		_MANAGED_BLOCK_ANCHOR="$$block_anchor" \
		_MANAGED_BLOCK_INDENT="$$block_indent" \
		_MANAGED_BLOCK_DEDUPLICATION="$$deduplication_mode" \
		_MANAGED_BLOCK_BODY="$$body_path" \
		$(AWK) "$$_MANAGED_BLOCK_AWK" "$$config_path")" || { \
		$(call log,`%s` has unpaired or extra markers$(_COMMA) or no place for the block.,$(COLOR_ERROR),"./$${config_path#$(CURDIR)/}") >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}; \
	[ "$$updated_contents" = "$$($(CAT) "$$config_path")" ] || { \
		$(PRINTF) '%s\n' "$$updated_contents" > "$$config_path" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
		$(call log,%s `%s`.,$(COLOR_SUCCESS),"$$written_verb" "./$${config_path#$(CURDIR)/}"); \
	}; \
fi
endef

#**
#* The configs a run writes, which is every one of them unless the run named some.
#*
#* parameters:
#*   configs: list<string>
#*
#* returns: list<string>
#*
define _selected_configs
$(if $(_NAMED_CONFIGS),$(foreach CONFIG,$1,$(if $(filter $(call _get_short_name,$(CONFIG)) $(call _get_file_name,$(CONFIG)),$(_NAMED_CONFIGS)),$(CONFIG))),$1)
endef

#**
#* The tracked half of a config path, which is the path itself once it names the `.dist` file.
#*
#* parameters:
#*   path: string
#*
#* returns: string
#*
define _tracked
$(if $(findstring .dist.,$1),$1,$(basename $1).dist$(suffix $1))
endef

#**
#* The private half beside wherever the tool resolved its config.
#* Empty where it resolved something outside that pair — an `eslint.ts` rather than either half of
#* the `.mjs` one.
#*
#* parameters:
#*   resolved: string
#*   extension: string
#*
#* returns: string
#*
define _layered
$(if $(filter %.$2 %.dist.$2,$1),$(subst .dist.,.,$1))
endef

#**
#* The same paths, rooted in the project's own `conf/`.
#* Anything the config search resolved inside an installation of this package moves back, so a write
#* never lands in `vendor/` or `node_modules/`.
#*
#* parameters:
#*   paths: list<string>
#*
#* returns: list<string>
#*
define _in_project
$(foreach CONFIG,$1,$(if $(filter $(addsuffix /%,$(_PACKAGE_CONF_DIRS)),$(CONFIG)),$(APP_DIR)/$(_CONFIG_DIR)/$(notdir $(CONFIG)),$(CONFIG)))
endef

#**
#* Runs a PHP program the target exports, handing it the project's autoloader before the arguments.
#*
#* parameters:
#*   program: string
#*   arguments: string
#*
#* returns: void
#*
define _php_program
$(call _echo_command,$(PHP) $1 $(_AUTOLOADER) $2)$(PHP) -r "$$$1" $(_AUTOLOADER) $2
endef

#**
#* Names the project's root namespace in a copied PHP config's `@internal` tag.
#* A file with no namespace still says where it belongs. Falls back to `App`, and does nothing
#* without an autoloader.
#*
#* parameters:
#*   path: string
#*
#* returns: void
#*
define _name_internal_tag
if [ -r "$(call _host_path,$(_AUTOLOADER))" ]; then \
	$(call _php_program,_NAME_INTERNAL_TAG_PROGRAM,"$(APP_DIR)/$1") \
		|| exit $(BRNSHKR_CONFIG_ERROR_CODE); \
fi
endef

#**
#* A list in reverse order.
#*
#* parameters:
#*   items: list<string>
#*
#* returns: list<string>
#*
define _reverse
$(if $1,$(call _reverse,$(wordlist 2,$(words $1),$1)) $(firstword $1))
endef

#**
#* The directories a config is looked for in, most specific first.
#* `$(CURDIR)` is deliberately absent: a bare config at the repository root would re-enter PHPStan's
#* dynamically evaluated paths.
#*
#* parameters:
#*   stack: string
#*
#* returns: list<string>
#*
define _config_dirs
$(foreach BASE,$(call _reverse,$(_SEARCH_BASES)),$(if $1,$(APP_DIR)/$(BASE)/$1) $(APP_DIR)/$(BASE))
endef

#**
#* The candidate names this package ships, which is the tracked half alone.
#*
#* parameters:
#*   candidates: list<string>
#*
#* returns: list<string>
#*
define _shipped_names
$(foreach NAME,$(notdir $1),$(if $(findstring .dist.,$(NAME)),$(NAME)))
endef

#**
#* The configs a directory has, in the order the names were given.
#*
#* parameters:
#*   directory: string
#*   names: list<string>
#*
#* returns: list<string>
#*
define _configs_in
$(foreach NAME,$2,$(if $(wildcard $(call _host_path,$1/$(NAME))),$1/$(NAME)))
endef

#**
#* The first config in the list that is there.
#* Then the tracked name under either installation of this package, so a project reads its own file,
#* falls back to the tracked one, and may keep neither. The last candidate is what a project is told
#* to create when it has none, and `CONFIG` pins the choice.
#*
#* parameters:
#*   candidates: list<string>
#*   stack: string
#*
#* returns: string
#*
define _config
$(if $(_PINNED_CONFIG),$(if $(filter local,$(_PINNED_CONFIG)),$(firstword $1),$(lastword $1)),$(or \
	$(firstword $(foreach DIRECTORY,$(call _config_dirs,$2),$(call _configs_in,$(DIRECTORY),$(notdir $1))) \
	$(foreach DIRECTORY,$(_PACKAGE_CONF_DIRS),$(call _configs_in,$(DIRECTORY),$(call _shipped_names,$1)))),$(lastword $1)))
endef

#**
#* The path a message names it by: relative to the checkout, the way the reader would type it.
#*
#* parameters:
#*   path: string
#*
#* returns: string
#*
define _named_path
$(patsubst $(CURDIR)/%,./%,$(call _host_path,$1))
endef

#**
#* Offers the closest targets to what was typed, asking before running the only one where there is one.
#*
#* parameters:
#*   prefix?: string
#*
#* returns: void
#*
define _suggest
$(if $(_SUGGESTED_TARGETS),$(if $(word 2,$(_SUGGESTED_TARGETS)), \
	$(call log,Did you mean %s?,$(COLOR_NOTICE),'$(call _join_as_quoted_list,$(addprefix $1,$(_SUGGESTED_TARGETS)),disjunction)'); \
	exit $(BRNSHKR_CONFIG_ERROR_CODE),if [ -t 0 ] && [ -z "$(_IS_CI)" ]; then \
		$(call confirm,Did you mean `$1$(_SUGGESTED_TARGETS)`?) || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
		exec $(_DOTENV_RESET)$(MAKE) $(_MAKE_FLAGS) $(_INHERITED_FLAGS) $1$(_SUGGESTED_TARGETS) \
			$(if $(_UNKNOWN_ARGS),-- $(_UNKNOWN_ARGS)); \
	fi; \
	$(call log,Did you mean `%s`?,$(COLOR_NOTICE),$(call _quoted,$1$(_SUGGESTED_TARGETS))); \
	exit $(BRNSHKR_CONFIG_ERROR_CODE)),$(call log,Run `make $(call _target,help)` or just `make` to see available commands.,$(COLOR_NOTICE)); \
	exit $(BRNSHKR_CONFIG_ERROR_CODE))
endef

#**
#* Fails the recipe when an argument is not one of the values the target accepts.
#*
#* parameters:
#*   noun: string
#*
#* returns: void
#*
define _require_known_value
$(if $(_UNKNOWN_VALUES),{ \
	$(call log,No $1 named `%s`.,$(COLOR_ERROR),$(call _quoted,$(_MISTYPED_VALUE))); \
	$(if $(_SUGGESTED_VALUES),$(if $(word 2,$(_SUGGESTED_VALUES)), \
		$(call log,Did you mean %s?,$(COLOR_NOTICE),$(call _quoted,$(call _join_as_quoted_list,$(_SUGGESTED_VALUES),disjunction))), \
		$(call log,Did you mean `%s`?,$(COLOR_NOTICE),$(call _quoted,$(_SUGGESTED_VALUES)))), \
		$(call log,Try %s.,$(COLOR_NOTICE),$(call _quoted,$(call _join_as_quoted_list,$(_ARG_VALUES),disjunction)),no-autolink)); \
	exit $(BRNSHKR_CONFIG_ERROR_CODE); \
},:)
endef

#**
#* Writes each `GIT_HOOKS` pair into this checkout's git hooks, keeping what is there.
#*
#* parameters:
#*   replace?: OneOf<yes | no> = no
#*
#* returns: void
#*
define _install_git_hooks
$(GIT) rev-parse --is-inside-work-tree >/dev/null 2>&1 || exit 0; \
hooks_directory="$$($(GIT) rev-parse --git-path hooks)"; \
$(MKDIR) -p "$$hooks_directory"; \
for hook_pair in $(GIT_HOOKS); do \
	hook="$${hook_pair%%=*}"; \
	$(if $(filter yes,$1),,[ ! -f "$$hooks_directory/$$hook" ] || continue;) \
	$(PRINTF) '#!/bin/sh\nexec %s %s\n' '$(MAKE)' "$${hook_pair#*=}" > "$$hooks_directory/$$hook"; \
	$(CHMOD) +x "$$hooks_directory/$$hook"; \
	$(call log,Installed `%s`.,$(COLOR_SUCCESS),"$$hook"); \
done
endef

#**
#* Fails the recipe when a configured file is not there, naming the variable it came from.
#*
#* parameters:
#*   path: string
#*   variable: string
#*
#* returns: void
#*
define _require_file
test -r '$(call _host_path,$1)' || { \
	$(if $(filter command% environment%,$(origin $2)) \
		,$(call log,`%s`$(_COMMA) which `%s` names$(_COMMA) is missing.,$(COLOR_ERROR),'$(call _named_path,$1)' '$2'), \
		$(call log,`%s` is missing$(_COMMA) set `%s` or create it.,$(COLOR_ERROR),'$(call _named_path,$1)' '$2') \
	) >&2; \
	exit $(BRNSHKR_CONFIG_ERROR_CODE); \
}
endef

#**
#* Fails the recipe when the target was handed no file to work on.
#*
#* parameters:
#*   target: string
#*   value_flags?: list<string>
#*
#* returns: void
#*
define _require_file_argument
$(if $(call _has_positional_arg,$(_ARGS),$2),:,{ \
	$(call log,`%s` needs a file$(_COMMA) pass one as an argument.,$(COLOR_ERROR),'$1') >&2; \
	exit $(BRNSHKR_CONFIG_ERROR_CODE); \
})
endef

_IS_GROUP_PARALLEL    = $(if $(filter -O%,$(MAKEFLAGS)),,$(_IS_PARALLEL))
_HAS_GROUP_LABEL      = $(and $(_IS_ANNOUNCING),$(ANNOUNCEMENT))
_CLEAR_SPINNER        = $(PRINTF) "\r$(_ANSI_CLEAR_LINE)$(_ANSI_SHOW_CURSOR)"
_SPINNER_SECONDS     := 0.15
_SIGNAL_INTERRUPT    := INT
_SIGNAL_TERMINATE    := TERM
_SIGNAL_HANGUP       := HUP
_INTERRUPT_EXIT_CODE := 130
_SPINNER_SIGNALS     := $(_SIGNAL_INTERRUPT) $(_SIGNAL_TERMINATE) $(_SIGNAL_HANGUP)

#**
#* Starts a spinner in the background, which runs until the shell that started it is gone.
#*
#* parameters:
#*   indent?: string
#*
#* returns: void
#*
define __spinner
while kill -0 $$$$ 2>/dev/null; do for frame in '|' '/' '-' '\'; do \
	$(PRINTF) '\r$1$(call text,%s Running…,$(COLOR_DESCRIPTION))' "$$frame"; \
	sleep $(_SPINNER_SECONDS) 2>/dev/null || sleep 1; \
done; done & spinner_pid=$$!
endef

#**
#* Runs a tool and prints its findings grouped by identifier, failing only when it reported nothing.
#*
#* parameters:
#*   command: string
#*   identifier: string
#*   message?: string
#*
#* returns: void
#*
define _group
$(call _echo_command,$1)label=$(if $(_HAS_GROUP_LABEL),$$($(call log,$(ANNOUNCEMENT),$(COLOR_NOTICE),$(call _quoted,$@)))); \
has_spinner=; $(if $(or $(_IS_GROUP_PARALLEL),$(_IS_NO_COLOR)),,[ -t 1 ] && has_spinner=1;) \
$(if $(_IS_GROUP_PARALLEL),,[ -z "$$label" ] || $(PRINTF) '%s\n' "$$label"; \
	[ -z "$$has_spinner" ] || { \
		$(PRINTF) '$(_ANSI_HIDE_CURSOR)'; \
		trap 'kill $$spinner_pid 2>/dev/null; $(_CLEAR_SPINNER); exit $(_INTERRUPT_EXIT_CODE)' $(_SPINNER_SIGNALS); \
		$(call __spinner,$(if $(_HAS_GROUP_LABEL),  )); \
	};) \
output=$$($1 2>&1); status=$$?; \
report=$$($(PRINTF) '%s\n' "$$output" \
	| $(AWK) -v _INDENT='$(if $(_HAS_GROUP_LABEL),$(INDENT),0)' -v _IDENTIFIER='$2' -v _MESSAGE='$3' -v _STATUS="$$status" "$$_GROUP_AWK"); code=$$?; \
$(if $(_IS_GROUP_PARALLEL),[ -z "$$report" ] || $(PRINTF) '%s\n' $${label:+"$$label"} "$$report";, \
	[ -z "$$has_spinner" ] || { \
		kill $$spinner_pid 2>/dev/null; \
		wait $$spinner_pid 2>/dev/null; \
		$(_CLEAR_SPINNER); \
	}; \
	[ -z "$$report" ] || $(PRINTF) '%s\n' "$$report";) \
exit $$code
endef

#**
#* Runs a tool behind a spinner, which clears as soon as the tool prints anything.
#*
#* parameters:
#*   command: string
#*
#* returns: void
#*
define _spinner
$(call _echo_command,$1)spin_command() { $1; }; \
has_spinner=; $(if $(or $(_IS_PARALLEL),$(_IS_NO_COLOR)),,[ -t 1 ] \
	&& [ -n '$(SCRIPT)' ] \
	&& command -v $(SCRIPT) >/dev/null 2>&1 \
	&& has_spinner=1;) \
if [ -z "$$has_spinner" ]; then \
	spin_command; \
else \
	export _SPIN_STATUS=$$($(MKTEMP)); \
	script_command='trap "$(PRINTF) $(_INTERRUPT_EXIT_CODE) > \"$$_SPIN_STATUS\"; exit $(_INTERRUPT_EXIT_CODE)" $(_SIGNAL_INTERRUPT); ($(subst $(_QUOTE),$(_QUOTE)\$(_QUOTE)$(_QUOTE),$1)); $(PRINTF) "%s" $$? > "$$_SPIN_STATUS"'; \
	if $(SCRIPT) --version >/dev/null 2>&1; then \
		set -- -qfc "$$script_command" /dev/null; \
	else \
		set -- -q /dev/null $(SHELL) -c "$$script_command"; \
	fi; \
	$(PRINTF) '$(_ANSI_HIDE_CURSOR)'; \
	trap 'kill $$spinner_pid 2>/dev/null; $(_CLEAR_SPINNER); exit $(_INTERRUPT_EXIT_CODE)' $(_SPINNER_SIGNALS); \
	$(call __spinner); \
	SHELL='$(SHELL)' $(SCRIPT) "$$@" | { \
		IFS= read -r line; has_line=$$?; \
		kill $$spinner_pid 2>/dev/null; \
		wait $$spinner_pid 2>/dev/null; \
		$(_CLEAR_SPINNER); \
		[ $$has_line -ne 0 ] || $(PRINTF) '%s\n' "$$line"; \
		$(CAT); \
	}; \
	code=$$($(CAT) "$$_SPIN_STATUS"); \
	$(RM) -f "$$_SPIN_STATUS"; \
	[ -n "$$code" ] || { spin_command; exit $$?; }; \
	exit $$code; \
fi
endef

#**
#* Runs a command in a fresh `$$scratch_directory` under `CACHE_DIR`, removed afterwards.
#* `$$app_scratch_directory` names the same directory where the tools see it.
#*
#* parameters:
#*   command: string
#*
#* returns: void
#*
define _in_scratch_directory
$(MKDIR) -p '$(CURDIR)/$(_CACHE_DIR)' && scratch_directory="$$($(MKTEMP) -d '$(CURDIR)/$(_CACHE_DIR)/scratch.XXXXXX')" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
app_scratch_directory='$(APP_DIR)/$(_CACHE_DIR)/'"$${scratch_directory##*/}"; \
{ $1; }; status=$$?; $(RM) -rf "$$scratch_directory"; exit $$status
endef

#**
#* Prints a command's output for a pipe to read, failing the recipe when the command itself failed.
#*
#* parameters:
#*   command: string
#*
#* returns: void
#*
define _capture
$(call _echo_command,$1)output=$$($1) || { $(PRINTF) '%s\n' "$$output" >&2; exit $(BRNSHKR_CONFIG_ERROR_CODE); }; $(PRINTF) '%s\n' "$$output"
endef

#**
#* Fails the recipe when one metric of a text coverage report falls short, as every runner but Pest needs.
#*
#* parameters:
#*   path: string
#*   metric: string
#*   minimum: number
#*   variable: string
#*
#* returns: void
#*
define _require_coverage
$(TRACE_PREFIX)$(call _require_file,$1,$4)
$(TRACE_PREFIX)$(AWK) -v _METRIC='$2' -v _MINIMUM='$3' '$$0 ~ _METRIC && !found { if (match($$0, /[0-9]+(\.[0-9]+)?%/)) { reached_percentage = substr($$0, RSTART, RLENGTH - 1) + 0; found = 1 } } END { exit reached_percentage < _MINIMUM + 0 }' '$1' || { \
	$(call log,`%s` coverage is below `%s%%`.,$(COLOR_ERROR),'$2' '$3') >&2; \
	exit $(BRNSHKR_CONFIG_ERROR_CODE); \
}
endef

#**
#* Says which test groups a run leaves out, and nothing when it leaves out none.
#*
#* parameters:
#*   groups: list<string>
#*
#* returns: void
#*
define _announce_excluded_groups
$(if $(strip $1),$(call log,Leaving out the %s $(if $(word 2,$1),groups,group).,$(COLOR_NOTICE),'$(call _join_as_quoted_list,$1)'),:)
endef

#**
#* Prints the command a wrapper runs, on stderr and only under `DEBUG`.
#*
#* parameters:
#*   command: string
#*
#* returns: string
#*
define _echo_command
$(if $(and $(_IS_DEBUG),$(if $(_IS_TRACE),,1)),$(PRINTF) '%s\n' '$(subst $(_QUOTE),$(_QUOTE)\$(_QUOTE)$(_QUOTE),$(strip $1))' >&2; )
endef

#**
#* A list of words, each quoted so the shell reads it as one argument.
#*
#* parameters:
#*   words: list<string>
#*
#* returns: list<string>
#*
define _shell_quoted
$(foreach WORD,$1,'$(call _unmasked,$(subst $(_QUOTE),$(_QUOTE)\$(_QUOTE)$(_QUOTE),$(WORD)))')
endef

#**
#* A tracked file name with the space and newline that make cannot hold in a list put back.
#*
#* parameters:
#*   name: string
#*
#* returns: string
#*
define _unmasked
$(subst $(_MASKED_SPACE),$(_SPACE),$(subst $(_MASKED_NEWLINE),$(_NEWLINE),$1))
endef

#**
#* Joins a list the way `Str::joinAsQuotedList()` and its JavaScript twin do, so the three read alike.
#*
#* parameters:
#*   items: list<string>
#*   type?: OneOf<conjunction | disjunction> = conjunction
#*
#* returns: string
#*
define _join_as_quoted_list
$(if $(word 2,$1),$(subst $(_SPACE),$(_COMMA)$(_SPACE),$(foreach \
	ITEM,$(filter-out $(lastword $1),$1),`$(ITEM)`)) $(if \
	$(filter disjunction,$2),or,and) `$(lastword $1)`,$(if \
	$1,`$1`))
endef

#**
#* Renders every backticked span of a line as inline code, ending with the given awk action.
#* A span links to the target or the path it names only where the caller asks for it.
#*
#* parameters:
#*   action: string
#*   autolink?: OneOf<yes | no> = no
#*
#* returns: void
#*
define _render_inline_code
$(AWK) -v _OPEN='$(if $(_IS_NO_COLOR),`,$(call _ansi,$(COLOR_HIGHLIGHT)))' \
	-v _CLOSE='$(if $(_IS_NO_COLOR),`,$(_ANSI_RESET))' \
	-v _URL='$(if $(filter yes,$2),$(_INLINE_CODE_URL))' \
	-v _CWD='$(CURDIR)' \
	-v _APP_DIR='$(APP_DIR)' \
	-v _PREFIX='$(_TARGET_PREFIX)' \
	-v _STAGES=' $(_DOTENV_STAGES) ' \
	-v _TARGETS=' $(if $(filter yes,$2),$(_TARGET_LINKS)) ' ' \
	function render_url(url, path, line,    placeholder_position) { \
		if (index(url, "{cwd}") && substr(path, 1, 1) == "/") { gsub(/\{cwd\}/, "", url); path = substr(path, 2) } \
		else gsub(/\{cwd\}/, _CWD, url); \
		placeholder_position = index(url, "{file}"); \
		url = substr(url, 1, placeholder_position - 1) path substr(url, placeholder_position + 6); \
		placeholder_position = index(url, "{line}"); \
		if (placeholder_position) url = substr(url, 1, placeholder_position - 1) line substr(url, placeholder_position + 6); \
		return url \
	} \
	function get_target_definition(name,    key, position, definition, dash) { \
		key = name; \
		position = index(_TARGETS, " " key "="); \
		if (!position && _PREFIX != "" && index(key, _PREFIX) == 1) { \
			key = substr(key, length(_PREFIX) + 1); \
			position = index(_TARGETS, " " key "=") \
		} \
		dash = index(key, "-"); \
		if (!position && dash > 1 && index(_STAGES, " " substr(key, 1, dash - 1) " ")) { \
			key = substr(key, dash + 1); \
			position = index(_TARGETS, " " key "=") \
		} \
		if (!position) return ""; \
		definition = substr(_TARGETS, position + length(key) + 2); \
		return substr(definition, 1, index(definition, " ") - 1) \
	} \
	function quote_for_shell(text,    quoted_text, position) { \
		quoted_text = ""; \
		while ((position = index(text, "\047"))) { quoted_text = quoted_text substr(text, 1, position - 1) "\047\\\047\047"; text = substr(text, position + 1) } \
		return "\047" quoted_text text "\047" \
	} \
	function render_hyperlink(path,    line, absolute_path, definition) { \
		if (_URL == "") return ""; \
		definition = get_target_definition(path); \
		if (definition != "") path = definition; \
		else if (path !~ /[\/.]/ || path ~ /[[:space:]]/) return ""; \
		line = 1; \
		if (match(path, /:[0-9]+$$/)) { \
			line = substr(path, RSTART + 1); \
			path = substr(path, 1, RSTART - 1) \
		} \
		if (substr(path, 1, 2) == "./") path = substr(path, 3); \
		if (index(path, _APP_DIR "/") == 1) path = substr(path, length(_APP_DIR) + 2); \
		else if (index(path, _CWD "/") == 1) path = substr(path, length(_CWD) + 2); \
		absolute_path = substr(path, 1, 1) == "/" ? path : _CWD "/" path; \
		if (system("test -r " quote_for_shell(absolute_path)) != 0) return ""; \
		return "\033]8;;" render_url(_URL, path, line) "\033\\" \
	} \
	{ \
		rendered_line = ""; \
		while (match($$0, /`[^`]*`/)) { \
			code_start = RSTART; \
			code_length = RLENGTH; \
			inline_code = substr($$0, code_start + 1, code_length - 2); \
			gsub(/[[:cntrl:]]/, "?", inline_code); \
			hyperlink = render_hyperlink(inline_code); \
			hyperlink_end = hyperlink == "" ? "" : "\033]8;;\033\\"; \
			rendered_line = rendered_line substr($$0, 1, code_start - 1) hyperlink _OPEN inline_code _CLOSE hyperlink_end; \
			$$0 = substr($$0, code_start + code_length) \
		} \
		$$0 = rendered_line $$0; \
		$1 \
	}'
endef

#**
#* Builds an ANSI escape prefix from a combined color and modifier list.
#*
#* parameters:
#*   color_and_or_modifiers: AnyOf<normal | white | black | red | yellow | green | cyan | blue | magenta | bold | underline | reverse | bright>
#*
#* returns: string
#*
define _ansi
$(call _color \
	,$(firstword $(filter $(_COLORS),$1)), \
	$(strip $(filter-out $(firstword $(filter $(_COLORS),$1)),$1)) \
)
endef

#**
#* The semicolon-separated ANSI codes for a list of modifier tokens.
#*
#* parameters:
#*   modifiers: AnyOf<normal | bold | underline | reverse>
#*
#* returns: string
#*
define _get_modifiers
$(if \
	$(filter $(MODIFIER_NORMAL),$1),$(_ANSI_MODIFIER_NORMAL),)$(if \
	$(filter $(MODIFIER_BOLD),$1),$(_ANSI_MODIFIER_BOLD),)$(if \
	$(filter $(MODIFIER_UNDERLINE),$1),;$(_ANSI_MODIFIER_UNDERLINE),)$(if \
	$(filter $(MODIFIER_REVERSE),$1),;$(_ANSI_MODIFIER_REVERSE),)
endef

#**
#* The ANSI code for a color token, bright when the modifiers ask for it and white when it does not resolve.
#*
#* parameters:
#*   color: OneOf<normal | white | black | red | yellow | green | cyan | blue | magenta>
#*   modifiers?: AnyOf<normal | bold | underline | reverse | bright>
#*
#* returns: _ANSI_COLOR_*
#*
define _get_color
$(if \
	$(and $(strip $2),$(or $(if $1,,1),$(filter normal,$1))),$(if \
		$(filter $(MODIFIER_BRIGHT),$2),$(_ANSI_COLOR_BRIGHT_WHITE),$(_ANSI_COLOR_WHITE)),$(if \
		$(filter $(MODIFIER_BRIGHT),$2),$(_ANSI_COLOR_BRIGHT_$(_UPPER_$1)),$(_ANSI_COLOR_$(_UPPER_$1))))
endef

#**
#* The full ANSI escape sequence for a color and its modifiers.
#*
#* parameters:
#*   color: OneOf<normal | white | black | red | yellow | green | cyan | blue | magenta>
#*   modifiers?: AnyOf<normal | bold | underline | reverse | bright>
#*
#* returns: string
#*
define _color
$(if $(_IS_NO_COLOR),,\033[$(if $(call _get_modifiers,$2),$(call _get_modifiers,$2);)$(call _get_color,$1,$2)m)
endef

#**
#* The argument tokens belonging to a target: those before any target, and those following it.
#*
#* parameters:
#*   target: string
#*   goals: list<string>
#*
#* returns: string
#*
define _collect_args
$(eval _ca_phase := before)$(eval _ca_shared :=)$(eval _ca_own :=)$(foreach \
	WORD,$(filter-out --,$2),$(if \
		$(filter before,$(_ca_phase)),$(if \
			$(filter $(WORD),$(_ALL_TARGETS)),$(if \
				$(filter $(WORD),$1),$(eval _ca_phase := own),$(eval _ca_phase := skip)),$(eval \
				_ca_shared += $(subst $$,$$$$,$(WORD)))),$(if \
		$(filter own,$(_ca_phase)),$(if \
			$(filter $(WORD),$(_ALL_TARGETS)),$(eval _ca_phase := done),$(eval \
			_ca_own += $(subst $$,$$$$,$(WORD)))),$(if \
		$(filter skip,$(_ca_phase)),$(if \
			$(filter $(WORD),$(_ALL_TARGETS)),$(if \
				$(filter $(WORD),$1),$(eval _ca_phase := own),))))))$(_ca_own) $(_ca_shared)
endef

#**
#* The variables named as `NAME="value"` shell assignments, escaped and flattened to one line.
#*
#* parameters:
#*   names: list<string>
#*
#* returns: string
#*
define _make_vars_as_env
$(foreach VARIABLE,$1,$(if $(filter simple recursive,$(flavor $(VARIABLE))), \
	$(VARIABLE)="$(subst $$,\$$,$(subst ",\",$(subst `,\`,$(subst \,\\,$(subst $(_NEWLINE), ,$($(VARIABLE)))))))" \
))
endef

#**
#* What a `semgrep` config's `x-brnshkr` key sets: `+<ruleset>`, `-<ruleset>`, `=<rule>` and `~<rule>@<glob>`.
#*
#* parameters:
#*   config: string
#*
#* returns: list<string>
#*
define _semgrep_overrides
$(foreach WORD,$(shell $(AWK) '$(_SEMGREP_OVERRIDES_AWK)' '$1' 2>/dev/null),$(if $(filter !%,$(WORD)),$(error \
	[$(_LABEL)] `$1` line $(patsubst !%,%,$(WORD)) is not a shape `x-brnshkr` takes),$(WORD)))
endef

#**
#* Fetches every ruleset `semgrep` runs that is missing from `SEMGREP_CACHE` or older than `SEMGREP_MAX_AGE` days,
#* then merges them into one `rules.json` with the config's `x-brnshkr` ignores applied.
#*
#* returns: void
#*
define _semgrep_rules
cache_directory='$(call _host_path,$(SEMGREP_CACHE))'; \
$(MKDIR) -p "$$cache_directory" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
for ruleset in $(SEMGREP_RULESETS); do \
	cached_ruleset_path="$$cache_directory/$$ruleset.json"; \
	[ -r "$$cached_ruleset_path" ] && [ -z "$$(find "$$cached_ruleset_path" -mmin +$$(($(SEMGREP_MAX_AGE) * 1440)))" ] && continue; \
	partial_ruleset_path="$$($(MKTEMP) "$$cached_ruleset_path.XXXXXX")" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	$(RUN) $(CURL) --silent --show-error --fail --compressed --proto =https --connect-timeout 10 --max-time 120 \
		-H 'Accept: application/json' -o '$(SEMGREP_CACHE)'/"$${partial_ruleset_path##*/}" '$(SEMGREP_REGISTRY)'/"$$ruleset" \
		|| { $(RM) -f "$$partial_ruleset_path"; exit $(BRNSHKR_CONFIG_ERROR_CODE); }; \
	[ ! -r "$$cached_ruleset_path" ] || $(CMP) -s "$$partial_ruleset_path" "$$cached_ruleset_path" \
		|| $(call log,The `%s` ruleset changed since it was last fetched.,$(COLOR_NOTICE),"$$ruleset"); \
	$(MV) "$$partial_ruleset_path" "$$cached_ruleset_path" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
done; \
$(SEMGREP_PYTHON) -c "$$_SEMGREP_RULES_PROGRAM" '$(SEMGREP_CACHE)/rules.json' \
	$(foreach RULESET,$(SEMGREP_RULESETS),'$(SEMGREP_CACHE)/$(RULESET).json') -- \
	$(foreach IGNORE,$(_SEMGREP_IGNORES),'$(IGNORE)') || exit $(BRNSHKR_CONFIG_ERROR_CODE)
endef

#---vvv programs

define _MANAGED_BLOCK_AWK_SOURCE
function trim(untrimmed_text) {
	sub(/^[[:space:]]+/, "", untrimmed_text)
	sub(/[[:space:]]+$$/, "", untrimmed_text)
	return untrimmed_text
}
function strip_comma(line_text) {
	sub(/,[[:space:]]*$$/, "", line_text)
	return line_text
}
function scan(line_text,    character_index, character, is_in_string, is_escaped) {
	scanned_code = is_jsonc ? "" : line_text
	scanned_end = 0
	for (character_index = 1; is_jsonc && character_index <= length(line_text); character_index += 1) {
		character = substr(line_text, character_index, 1)
		if (is_in_block_comment) {
			if (character == "*" && substr(line_text, character_index + 1, 1) == "/") {
				is_in_block_comment = 0
				character_index += 1
			}
			continue
		}
		if (!is_in_string && character == "/" && substr(line_text, character_index + 1, 1) == "/") break
		if (!is_in_string && character == "/" && substr(line_text, character_index + 1, 1) == "*") {
			is_in_block_comment = 1
			character_index += 1
			continue
		}
		if (is_in_string) {
			if (is_escaped) is_escaped = 0
			else if (character == "\\") is_escaped = 1
			else if (character == "\"") is_in_string = 0
		} else if (character == "\"") is_in_string = 1
		scanned_code = scanned_code character
		if (character !~ /[[:space:]]/) scanned_end = character_index
	}
}
function get_depth_change(code_text,    character_index, character, depth_delta, is_in_string, is_escaped) {
	for (character_index = 1; character_index <= length(code_text); character_index += 1) {
		character = substr(code_text, character_index, 1)
		if (is_in_string) {
			if (is_escaped) is_escaped = 0
			else if (character == "\\") is_escaped = 1
			else if (character == "\"") is_in_string = 0
		} else if (character == "\"") is_in_string = 1
		else if (character == "{" || character == "[") depth_delta += 1
		else if (character == "}" || character == "]") depth_delta -= 1
	}
	return depth_delta
}
function get_entry_key(code_text) {
	return match(code_text, /^[[:space:]]*"[^"]+"[[:space:]]*:/) ? trim(substr(code_text, RSTART, RLENGTH)) : ""
}
function keep(line_text, code_text, code_end) {
	kept_lines[++kept_count] = line_text
	kept_codes[kept_count] = code_text
	kept_ends[kept_count] = code_end
}
function append_block(block_indent, is_spaced, segment_index,    body_index) {
	if (is_spaced) keep("", "", 0)
	keep(block_indent open_marker, "", 0)
	for (body_index = 1; body_index <= segment_sizes[segment_index]; body_index += 1)
		keep(segment_lines[segment_index, body_index], segment_codes[segment_index, body_index], segment_ends[segment_index, body_index])
	keep(block_indent close_marker, "", 0)
	if (is_spaced) keep("", "", 0)
	is_block_written = 1
	is_after_block = 1
	section = block_last_section
}
function get_next_text(lines, line_count, from_index,    line_index) {
	for (line_index = from_index; line_index <= line_count; line_index += 1)
		if (trim(lines[line_index]) != "") return trim(lines[line_index])
	return ""
}
BEGIN {
	open_marker = ENVIRON["_MANAGED_BLOCK_OPEN"]
	close_marker = ENVIRON["_MANAGED_BLOCK_CLOSE"]
	anchor = ENVIRON["_MANAGED_BLOCK_ANCHOR"]
	deduplication_mode = ENVIRON["_MANAGED_BLOCK_DEDUPLICATION"]
	is_jsonc = open_marker ~ /^\/\//
	segment_count = 1
	while ((getline body_line < ENVIRON["_MANAGED_BLOCK_BODY"]) > 0) {
		if (body_line == close_marker) {
			segment_count += 1
			continue
		}
		scan(body_line)
		body_index = ++segment_sizes[segment_count]
		segment_lines[segment_count, body_index] = body_line
		segment_codes[segment_count, body_index] = scanned_code
		segment_ends[segment_count, body_index] = scanned_end
		line_text = trim(body_line)
		if (deduplication_mode == "sections" && line_text ~ /^\[/) block_last_section = line_text
		body_texts[block_last_section SUBSEP strip_comma(line_text)] = 1
		if (!depth && get_entry_key(scanned_code) != "") body_keys[get_entry_key(scanned_code)] = 1
		depth += get_depth_change(scanned_code)
	}
	if (segment_count > 1 && !segment_sizes[segment_count]) segment_count -= 1
	is_in_block_comment = 0
	depth = 0
}
{ target_lines[++target_count] = $$0 }
END {
	for (line_index = 1; line_index <= target_count; line_index += 1) {
		scan(target_lines[line_index])
		target_codes[line_index] = scanned_code
		target_ends[line_index] = scanned_end
		line_text = trim(target_lines[line_index])
		if (!first_header_index && deduplication_mode == "sections" && line_text ~ /^\[/) first_header_index = line_index
		if (!is_in_region && line_text == open_marker) {
			region_starts[++region_count] = line_index
			is_in_region = 1
		} else if (is_in_region && line_text == close_marker) {
			region_ends[region_count] = line_index
			is_in_region = 0
		}
	}
	if (is_in_region || (region_count && region_count != segment_count) || (!region_count && segment_count != 1)) exit 1
	insert_index = deduplication_mode == "sections" ? first_header_index : 1
	region_index = 1
	for (line_index = 1; line_index <= target_count; line_index += 1) {
		if (region_index <= region_count && line_index == region_starts[region_index]) {
			block_indent = target_lines[line_index]
			sub(/[^[:space:]].*$$/, "", block_indent)
			append_block(block_indent, 0, region_index)
			line_index = region_ends[region_index]
			region_index += 1
			continue
		}
		if (!region_count && !is_block_written && anchor == "" && line_index == insert_index) append_block("", 1, 1)
		line_text = trim(target_lines[line_index])
		code_text = target_codes[line_index]
		if (deduplication_mode == "sections" && line_text ~ /^\[/) {
			is_duplicate = is_after_block && line_text == block_last_section
			is_after_block = 0
			section = line_text
		} else is_duplicate = deduplication_mode != "" && line_text != "" && (section SUBSEP strip_comma(line_text)) in body_texts
		if (deduplication_mode == "keys") {
			if (depth == 1 && get_entry_key(code_text) in body_keys) is_dropping_entry = 1
			is_duplicate = is_dropping_entry || (is_duplicate && line_text ~ /^\/\//)
			depth += get_depth_change(code_text)
			if (depth == 1) is_dropping_entry = 0
		}
		if (!is_duplicate) keep(target_lines[line_index], code_text, target_ends[line_index])
		if (!region_count && !is_block_written && anchor != "" && target_lines[line_index] ~ anchor && get_depth_change(code_text) == 1)
			append_block(ENVIRON["_MANAGED_BLOCK_INDENT"], 0, 1)
	}
	if (!is_block_written && anchor != "") exit 1
	if (!is_block_written) append_block("", 1, 1)
	for (line_index = 1; line_index <= kept_count; line_index += 1) {
		line_text = trim(kept_lines[line_index])
		if (deduplication_mode == "sections" && line_text ~ /^\[/ && get_next_text(kept_lines, kept_count, line_index + 1) ~ /^(\[|$$)/) continue
		if (deduplication_mode == "" || line_text != "" || (printed_count && trim(printed_lines[printed_count]) != "")) {
			printed_lines[++printed_count] = kept_lines[line_index]
			printed_codes[printed_count] = kept_codes[line_index]
			printed_ends[printed_count] = kept_ends[line_index]
		}
	}
	while (printed_count && trim(printed_lines[printed_count]) == "") printed_count -= 1
	for (line_index = 1; line_index <= printed_count; line_index += 1) {
		code_end = printed_ends[line_index]
		if (code_end) {
			following_code = get_next_text(printed_codes, printed_count, line_index + 1)
			last_character = substr(printed_lines[line_index], code_end, 1)
			if (following_code ~ /^[]}]/ && last_character == ",")
				printed_lines[line_index] = substr(printed_lines[line_index], 1, code_end - 1) substr(printed_lines[line_index], code_end + 1)
			else if (following_code != "" && following_code !~ /^[]}]/ && last_character !~ /[,{[]/)
				printed_lines[line_index] = substr(printed_lines[line_index], 1, code_end) "," substr(printed_lines[line_index], code_end + 1)
		}
		print printed_lines[line_index]
	}
}
endef

define _SEMGREP_RULES_PROGRAM_SOURCE
import json, os, sys, tempfile
arguments = sys.argv[1:]
separator = arguments.index('--')
target, sources, ignores = arguments[0], arguments[1:separator], arguments[separator + 1:]
everywhere = {ignore[1:] for ignore in ignores if ignore.startswith('=')}
globs_by_rule = {}
for ignore in ignores:
    if ignore.startswith('~'):
        rule, glob = ignore[1:].split('@', 1)
        globs_by_rule.setdefault(rule, []).append(glob)
rules = {}
for source in sources:
    with open(source) as handle:
        for rule in json.load(handle).get('rules', []):
            rules.setdefault(rule['id'], rule)
for rule_id, globs in globs_by_rule.items():
    if rule_id in rules:
        rules[rule_id].setdefault('paths', {}).setdefault('exclude', []).extend(globs)
descriptor, part = tempfile.mkstemp(dir=os.path.dirname(target))
with os.fdopen(descriptor, 'w') as handle:
    json.dump({'rules': [rule for rule_id, rule in rules.items() if rule_id not in everywhere]}, handle)
os.replace(part, target)
endef

define _SEMGREP_OVERRIDES_AWK
function close_entry() { if (rule != "" && !has_files) { print "!" rule_line; has_failed = 1; exit } rule = ""; has_files = 0; in_files = 0 }
/^x-brnshkr:[[:space:]]*\{\}[[:space:]]*$$/ { next }
/^x-brnshkr:[[:space:]]*$$/ { in_block = 1; next }
!in_block { next }
/^[[:space:]]*(#.*)?$$/ { next }
/^[^[:space:]]/ { close_entry(); in_block = 0; in_ignores = 0; next }
/^  [^[:space:]]/ { close_entry(); in_ignores = 0 }
/^  ignores:[[:space:]]*\[\][[:space:]]*$$/ { next }
/^  ignores:[[:space:]]*$$/ { in_ignores = 1; next }
/^  [a-z0-9-]+:[[:space:]]+(true|false)[[:space:]]*$$/ { \
	name = $$1; \
	sub(/:$$/, "", name); \
	print ($$2 == "true" ? "+" : "-") name; \
	next \
}
in_ignores && /^    - [a-z0-9._-]+[[:space:]]*$$/ { close_entry(); print "=" $$2; next }
in_ignores && /^    - rule:[[:space:]]+[a-z0-9._-]+[[:space:]]*$$/ { close_entry(); rule = $$3; rule_line = NR; next }
in_ignores && rule != "" && /^      files:[[:space:]]*$$/ { in_files = 1; next }
in_ignores && in_files && /^        - [^[:space:]"'"'"']+[[:space:]]*$$/ { print "~" rule "@" $$2; has_files = 1; next }
{ print "!" NR; has_failed = 1; exit }
END { if (!has_failed) close_entry() }
endef

define _GITATTRIBUTES_PROGRAM_SOURCE
$$manifest = json_decode((string) file_get_contents($$argv[1]), true, flags: JSON_THROW_ON_ERROR);

$$shippedPaths = ['composer.json', 'LICENSE', 'README.md'];
$$declaredPaths = array_filter(explode(' ', $$argv[2] ?? ''));

foreach (['psr-4', 'psr-0', 'classmap', 'files'] as $$section) {
    foreach ((array) ($$manifest['autoload'][$$section] ?? []) as $$sectionPaths) {
        foreach ((array) $$sectionPaths as $$sectionPath) {
            $$shippedPaths[] = $$sectionPath;
        }
    }
}

foreach ((array) ($$manifest['bin'] ?? []) as $$binaryPath) {
    $$shippedPaths[] = $$binaryPath;
}

$$projectDirectory = dirname($$argv[1]);
$$doesExist = static fn (string $$path): bool => file_exists(
    $$projectDirectory . '/' . mb_trim((string) preg_replace('#^(?:\./)+#', '', $$path), '/'),
);
$$shippedPaths = array_filter($$shippedPaths, $$doesExist);
$$declaredPaths = array_filter($$declaredPaths, $$doesExist);

if ($$shippedPaths === [] && $$declaredPaths === []) {
    exit(0);
}

$$expand = static function (array $$paths, bool $$wholeTree) use ($$projectDirectory): array {
    $$expandedPaths = [];

    foreach ($$paths as $$path) {
        $$path = (string) preg_replace('#^(?:\./)+#', '', $$path);
        $$isWholeTree = $$wholeTree
            ? is_dir($$projectDirectory . '/' . mb_trim($$path, '/'))
            : str_ends_with($$path, '/');
        $$path = '/' . mb_trim($$path, '/');
        $$expandedPaths[] = $$path;

        if ($$isWholeTree) {
            $$expandedPaths[] = $$path . '/**';
        }
    }

    return $$expandedPaths;
};

$$shippedPaths = [...$$expand($$shippedPaths, true), ...$$expand($$declaredPaths, false)];
$$shippedLookup = array_flip($$shippedPaths);

foreach ($$shippedPaths as $$path) {
    while (($$path = dirname($$path)) !== '/' && $$path !== '.') {
        $$shippedPaths[] = $$path;
    }
}

$$shippedPaths = array_values(array_unique($$shippedPaths));

sort($$shippedPaths);

$$pathWidth = max(array_map(mb_strlen(...), [...$$shippedPaths, '*']));
$$renderedLines = [
    mb_str_pad('*', $$pathWidth) . '  export-ignore',
    mb_str_pad('.*', $$pathWidth) . '  export-ignore',
];

foreach ($$shippedPaths as $$path) {
    $$renderedLines[] = mb_str_pad($$path, $$pathWidth) . ' -export-ignore'
        . (isset($$shippedLookup[$$path]) ? '' : ' -export-subst');
}

echo implode("\n", $$renderedLines), "\n";
endef

define _NAME_INTERNAL_TAG_PROGRAM_SOURCE
require $$argv[1];

$$path = $$argv[2];
$$namespace = Brnshkr\Config\ComposerJson::forProjectUsingThisLibrary()->getRootNamespace() ?? 'App';

file_put_contents($$path, preg_replace('/@internal(?: \S+)?$$/m', '@internal ' . $$namespace, file_get_contents($$path), 1));
endef

#**
#* A release body: the file's section where there is one, without its heading and with the compare link.
#*
#* returns: void
#*
define _changelog_notes
section_text=$$([ ! -r '$(_CHANGELOG_FILE)' ] || _CHANGELOG_VERSION='$(_CHANGELOG_VERSION)' \
	$(AWK) "$$_CHANGELOG_SECTION_AWK" '$(_CHANGELOG_FILE)'); \
if [ -n "$$section_text" ]; then \
	$(CAT) >/dev/null; \
	$(PRINTF) '%s\n' "$$section_text"; \
else \
	$(AWK) 'NR == 1 && /^## / { next } { body = body $$0 "\n" } \
		END { sub(/^\n+/, "", body); printf "%s", body }'; \
fi; \
[ -z '$(_CHANGELOG_COMPARE)' ] || $(PRINTF) '\n**Full Changelog**: %s\n' '$(_CHANGELOG_COMPARE)'
endef

#**
#* Prints the generated changelog under the title, as the file carries it.
#*
#* returns: void
#*
define _changelog_print
{ $(PRINTF) '%s\n\n' '$(CHANGELOG_TITLE)'; $(CAT); }
endef

#**
#* Merges the generated sections into `CHANGELOG_DIR`, keeping every note already written there.
#*
#* returns: void
#*
define _changelog_write
if [ '$(_CHANGELOG_MAJOR)' = '0' ] \
	&& ! $(call confirm,`$(_CHANGELOG_VERSION)` is below `1.0.0`$(_COMMA) write it anyway?); then \
	$(CAT) >/dev/null; \
	$(call log,Nothing written.,$(COLOR_NOTICE)); \
else \
	$(MKDIR) -p '$(CHANGELOG_DIR)'; \
	$(CAT) > '$(_CHANGELOG_FILE).added'; \
	{ \
		$(AWK) 'NR == 1 { has_title = $$0 == "$(CHANGELOG_TITLE)" } END { exit !has_title }' \
			'$(_CHANGELOG_FILE)' 2>/dev/null \
			|| $(PRINTF) '%s\n\n' '$(CHANGELOG_TITLE)'; \
		_CHANGELOG_ADDED='$(_CHANGELOG_FILE).added' \
			_CHANGELOG_FORCE='$(filter force f,$(_ARG_WORDS))' \
			$(AWK) "$$_CHANGELOG_MERGE_AWK" \
			'$(_CHANGELOG_FILE).added' \
			$$([ -r '$(_CHANGELOG_FILE)' ] && $(PRINTF) '%s' '$(_CHANGELOG_FILE)'); \
	} > '$(_CHANGELOG_FILE).new'; \
	$(RM) -f '$(_CHANGELOG_FILE).added'; \
	if [ ! -r '$(_CHANGELOG_FILE)' ]; then \
		$(MV) '$(_CHANGELOG_FILE).new' '$(_CHANGELOG_FILE)' \
			&& $(call log,Created `%s`.,$(COLOR_SUCCESS),'$(call _named_path,$(_CHANGELOG_FILE))'); \
	elif $(CMP) -s '$(_CHANGELOG_FILE).new' '$(_CHANGELOG_FILE)'; then \
		$(RM) -f '$(_CHANGELOG_FILE).new'; \
		$(call log,Nothing written.,$(COLOR_NOTICE)); \
	else \
		$(MV) '$(_CHANGELOG_FILE).new' '$(_CHANGELOG_FILE)' \
			&& $(call log,Updated `%s`.,$(COLOR_SUCCESS),'$(call _named_path,$(_CHANGELOG_FILE))'); \
	fi; \
fi
endef

define _RULES_PROGRAM_SOURCE
[, $$autoloaderPath, $$toolName, $$configPath, $$urlTemplate, $$appDirectory, $$hostDirectory] = $$argv;

require $$autoloaderPath;

if ($$toolName === 'rector') {
    $$rectorConfig = new Rector\DependencyInjection\LazyContainerFactory()->create();

    (require $$configPath)($$rectorConfig);

    $$rectorConfig->boot();

    $$rules = $$rectorConfig->findByContract(Rector\Contract\Rector\RectorInterface::class);

    $$skippedRuleNames = array_keys(array_filter(
        $$rectorConfig->make(Rector\Skipper\SkipCriteriaResolver\SkippedClassResolver::class)->resolve(),
        static fn (?array $$paths): bool => $$paths === null,
    ));
} else {
    $$rules            = (require $$configPath)->getRuleset()->getRules();
    $$skippedRuleNames = [];
}

$$ruleNames = array_values(array_unique(array_map(static fn (object $$rule): string => $$rule::class, $$rules)));

sort($$ruleNames);
sort($$skippedRuleNames);

$$link = static function (string $$name) use ($$urlTemplate, $$appDirectory, $$hostDirectory): string {
    $$fileName = $$urlTemplate === '' || !class_exists($$name) ? false : new ReflectionClass($$name)->getFileName();

    if ($$fileName === false) {
        return $$name;
    }

    $$isInApp = str_starts_with($$fileName, $$appDirectory . '/');
    $$url     = strtr($$urlTemplate, [
        '{cwd}'  => $$isInApp ? $$hostDirectory : '',
        '{file}' => $$isInApp ? mb_substr($$fileName, mb_strlen($$appDirectory) + 1) : mb_ltrim($$fileName, '/'),
        '{line}' => (string) new ReflectionClass($$name)->getStartLine(),
    ]);

    return "\033]8;;" . $$url . "\033\\" . $$name . "\033]8;;\033\\";
};

$$printSection = static function (string $$title, array $$names) use ($$link): void {
    echo $$title, PHP_EOL, str_repeat('=', mb_strlen($$title)), PHP_EOL, PHP_EOL;

    foreach ($$names as $$name) {
        echo ' * ', $$link($$name), PHP_EOL;
    }

    echo PHP_EOL;
};

$$printSection(sprintf('Loaded %s rules', $$toolName), $$ruleNames);

if ($$skippedRuleNames !== []) {
    $$printSection(sprintf('Skipped %s rules', $$toolName), $$skippedRuleNames);
}

printf('Loaded %d rules%s', count($$ruleNames), PHP_EOL);
endef

define _CHANGELOG_SECTION_AWK_SOURCE
function get_version(line,    value) {
	value = line
	sub(/^## \[?/, "", value)
	sub(/[]( ].*$$/, "", value)
	return value
}
BEGIN { wanted_version = ENVIRON["_CHANGELOG_VERSION"] }
/^## / {
	inside = (get_version($$0) == wanted_version)
	next
}
inside { body = body $$0 "\n" }
END {
	sub(/^\n+/, "", body)
	sub(/\n+$$/, "", body)
	if (body != "") print body
}
endef

define _CHANGELOG_MERGE_AWK_SOURCE
function pad(value,    padded_value) {
	padded_value = "0000000000" value
	return substr(padded_value, length(padded_value) - 9)
}
function get_version(line,    value) {
	value = line
	sub(/^## \[?/, "", value)
	sub(/[]( ].*$$/, "", value)
	return value
}
function get_semver_key(version,    value, prerelease, parts, count, position, key, tokens, token_count, token) {
	value = version
	sub(/^v/, "", value)
	sub(/\+.*$$/, "", value)
	prerelease = ""
	if (match(value, /-/)) {
		prerelease = substr(value, RSTART + 1)
		value = substr(value, 1, RSTART - 1)
	}
	count = split(value, parts, ".")
	key = ""
	for (position = 1; position <= 3; position += 1) key = key pad((position <= count) ? parts[position] + 0 : 0)
	if (prerelease == "") return key "1"
	key = key "0"
	token_count = split(prerelease, tokens, ".")
	for (position = 1; position <= token_count; position += 1) {
		token = tokens[position]
		key = key ((token ~ /^[0-9]+$$/) ? "0" pad(token + 0) : "1" substr(token "..........", 1, 10))
	}
	return key
}
function get_hash(line,    value) {
	if (!match(line, /\(\[?[0-9a-f]{7}\]?[)(]/)) return ""
	value = substr(line, RSTART, RLENGTH)
	gsub(/[^0-9a-f]/, "", value)
	return value
}
function is_entry(line) {
	return (line ~ /^- /) && get_hash(line) != ""
}
function is_heading(line) {
	return line ~ /^#{3,4} /
}

function get_section_text(index_,    position, out) {
	out = ""
	for (position = 1; position <= added_lines[index_]; position += 1) out = out added_line[index_, position] "\n"
	sub(/\n+$$/, "", out)
	return out "\n"
}

function merge_text(existing_index, added_index,    position, entry, group, pending, line, out, held, tail, seen_entry, regenerated_entry) {
	pending = 0
	for (position = 1; position <= added_entries[added_index]; position += 1) {
		if ((existing_index, added_entry_hash[added_index, position]) in existing_hash) continue
		pending += 1
		pending_group[pending] = added_entry_group[added_index, position]
		pending_entry[pending] = added_entry[added_index, position]
	}
	out = ""
	group = ""
	held = ""
	tail = ""
	seen_entry = 0
	for (position = 1; position <= existing_lines[existing_index]; position += 1) {
		line = existing_line[existing_index, position]
		if (is_heading(line) || line ~ /^## /) {
			out = out flush_group(group, held, tail, seen_entry, pending)
			group = is_heading(line) ? line : ""
			held = ""
			tail = ""
			seen_entry = 0
			out = out line "\n"
			continue
		}
		if (is_entry(line)) {
			seen_entry = 1
			regenerated_entry = ENVIRON["_CHANGELOG_FORCE"] == "" ? "" : added_by_hash[added_index, group, get_hash(line)]
			held = held tail (regenerated_entry == "" ? line : regenerated_entry) "\n"
			tail = ""
		} else if (seen_entry) {
			tail = tail line "\n"
		} else {
			out = out line "\n"
		}
	}
	out = out flush_group(group, held, tail, seen_entry, pending)
	for (position = 1; position <= pending; position += 1) {
		if (used_pending_positions[position]) continue
		out = out "\n" (pending_group[position] == "" ? "" : pending_group[position] "\n\n") pending_entry[position] "\n"
	}
	split("", used_pending_positions)
	sub(/\n+$$/, "", out)
	return out "\n"
}

function flush_group(group, held, tail, seen_entry, pending,    position, out) {
	out = held
	for (position = 1; position <= pending; position += 1) {
		if (used_pending_positions[position] || pending_group[position] != group) continue
		out = out pending_entry[position] "\n"
		used_pending_positions[position] = 1
	}
	return out tail
}

# Every section the generator produced, with its entries and the group each sits under.
BEGIN { _added = ENVIRON["_CHANGELOG_ADDED"] }
FILENAME == _added {
	if ($$0 ~ /^## /) {
		added_count += 1
		added_version[added_count] = get_version($$0)
		group = ""
	} else if (is_heading($$0)) {
		group = $$0
	}
	if (added_count == 0) next
	added_lines[added_count] += 1
	added_line[added_count, added_lines[added_count]] = $$0
	if (is_entry($$0)) {
		added_entries[added_count] += 1
		added_entry[added_count, added_entries[added_count]] = $$0
		added_entry_group[added_count, added_entries[added_count]] = group
		added_entry_hash[added_count, added_entries[added_count]] = get_hash($$0)
		added_by_hash[added_count, group, get_hash($$0)] = $$0
	}
	next
}

# The file as it stands, kept verbatim.
{
	if ($$0 ~ /^## /) {
		existing_count += 1
		existing_version[existing_count] = get_version($$0)
	}
	if (existing_count == 0) {
		preamble = preamble $$0 "\n"
		next
	}
	existing_lines[existing_count] += 1
	existing_line[existing_count, existing_lines[existing_count]] = $$0
	if (is_entry($$0)) existing_hash[existing_count, get_hash($$0)] = 1
}

# An existing section, with any entry it is missing placed after the last entry of its own group.
END {
	document = preamble

	for (existing_index = 1; existing_index <= existing_count; existing_index += 1) {
		existing_index_by_version[existing_version[existing_index]] = existing_index
	}
	for (added_index = 1; added_index <= added_count; added_index += 1) {
		added_index_by_version[added_version[added_index]] = added_index
	}

	# Sections the file does not carry yet, newest first, placed among the ones it does.
	for (added_index = 1; added_index <= added_count; added_index += 1) {
		if (added_version[added_index] in existing_index_by_version) continue
		insert_count += 1
		insert_key[insert_count] = get_semver_key(added_version[added_index])
		insert_index[insert_count] = added_index
	}
	for (position = 2; position <= insert_count; position += 1) {
		key_held = insert_key[position]
		value_held = insert_index[position]
		sorted_position = position - 1
		while (sorted_position >= 1 && insert_key[sorted_position] < key_held) {
			insert_key[sorted_position + 1] = insert_key[sorted_position]
			insert_index[sorted_position + 1] = insert_index[sorted_position]
			sorted_position -= 1
		}
		insert_key[sorted_position + 1] = key_held
		insert_index[sorted_position + 1] = value_held
	}

	pending_insert = 1
	for (existing_index = 1; existing_index <= existing_count; existing_index += 1) {
		while (pending_insert <= insert_count \
			&& insert_key[pending_insert] > get_semver_key(existing_version[existing_index])) {
			document = document get_section_text(insert_index[pending_insert]) "\n"
			pending_insert += 1
		}
		replacement = ((existing_version[existing_index] in added_index_by_version) \
			? merge_text(existing_index, added_index_by_version[existing_version[existing_index]]) : "")
		if (replacement == "") {
			for (position = 1; position <= existing_lines[existing_index]; position += 1) {
				document = document existing_line[existing_index, position] "\n"
			}
		} else {
			document = document replacement
		}
		sub(/\n+$$/, "", document)
		document = document "\n\n"
	}
	while (pending_insert <= insert_count) {
		document = document get_section_text(insert_index[pending_insert]) "\n"
		pending_insert += 1
	}

	sub(/\n+$$/, "", document)
	print document
}
endef

define _CHANGELOG_AWK_SOURCE
function sort_pair(keys, values, count,    position, sorted_position, key_held, value_held) {
	for (position = 2; position <= count; position += 1) {
		key_held = keys[position]
		value_held = values[position]
		sorted_position = position - 1
		while (sorted_position >= 1 && keys[sorted_position] > key_held) {
			keys[sorted_position + 1] = keys[sorted_position]
			values[sorted_position + 1] = values[sorted_position]
			sorted_position -= 1
		}
		keys[sorted_position + 1] = key_held
		values[sorted_position + 1] = value_held
	}
}
function to_readable_scope(scope,    parts, count, index_, word, out) {
	if (scope in names) return names[scope]
	count = split(scope, parts, /-/)
	out = ""
	for (index_ = 1; index_ <= count; index_ += 1) {
		word = parts[index_]
		out = out (index_ == 1 ? "" : " ") toupper(substr(word, 1, 1)) substr(word, 2)
	}
	return out
}
function render(section, label,    position, inner, seconds, second_names, keys, entries, second, out) {
	out = unscoped_entries[section, label]
	for (position = 1; position <= second_count[section, label]; position += 1) {
		seconds[position] = second_name[section, label, position]
		second_names[position] = second_name[section, label, position]
	}
	sort_pair(seconds, second_names, second_count[section, label])
	for (position = 1; position <= second_count[section, label]; position += 1) {
		second = seconds[position]
		if (out != "") out = out "\n"
		out = out "#### " to_readable_scope(second) "\n\n" unscoped_second_entries[section, label, second]
		split("", keys)
		split("", entries)
		for (inner = 1; inner <= sub_count[section, label, second]; inner += 1) {
			keys[inner] = sub_key[section, label, second, inner]
			entries[inner] = sub_entry[section, label, second, inner]
		}
		sort_pair(keys, entries, sub_count[section, label, second])
		for (inner = 1; inner <= sub_count[section, label, second]; inner += 1) out = out entries[inner] "\n"
	}
	return out
}
function get_major(version,    value) {
	value = version
	sub(/^v/, "", value)
	sub(/\..*$$/, "", value)
	return value
}
function flush(section, version, compare,    position, label, printed_labels, out) {
	if (_all != "" && !(section in section_commit_count)) return ""
	if (_major != "" && get_major(version) != _major) return ""
	out = (version == "") ? "" : "## " ((compare != "") ? "[" version "](" compare ")" : version) "\n"
	if (!(section in has_content)) return out ((out == "") ? "" : "\n") "_No changes_\n"
	if ((section in breaking)) out = out "\n### " breaking_label "\n\n" breaking[section]
	for (position = 1; position <= rank; position += 1) {
		label = label_by_rank[position]
		if (((section, label) in seen_labels) && !(label in printed_labels)) {
			out = out "\n### " label "\n\n" render(section, label)
			printed_labels[label] = 1
		}
	}
	if (((section, other) in seen_labels)) out = out "\n### " other "\n\n" render(section, other)
	return out
}
BEGIN {
	RS = "\x1e"
	FS = "\x1f"
	other = "📌 Other"
	breaking_label = "⚠ Breaking changes"
	_URL = ENVIRON["_CHANGELOG_URL"]
	_version = ENVIRON["_CHANGELOG_VERSION"]
	_compare = ENVIRON["_CHANGELOG_COMPARE"]
	_all = ENVIRON["_CHANGELOG_ALL"]
	_commits = ENVIRON["_CHANGELOG_COMMITS"]
	_types = ENVIRON["_CHANGELOG_TYPES"]
	_major = ENVIRON["_CHANGELOG_MAJOR"]
	section = 0
	section_version[0] = _version
	pair_count = split(ENVIRON["_CHANGELOG_GROUPS"], pairs, " ")
	for (index_ = 1; index_ <= pair_count; index_ += 1) {
		if (pairs[index_] !~ /=/) continue
		root = pairs[index_]
		sub(/=.*$$/, "", root)
		label = pairs[index_]
		sub(/^[^=]*=/, "", label)
		gsub(/_/, " ", label)
		groups[root] = label
		if (!(label in rank_by_label)) { rank_by_label[label] = ++rank; label_by_rank[rank] = label }
	}
	name_count = split(ENVIRON["_CHANGELOG_NAMES"], pairs, " ")
	for (index_ = 1; index_ <= name_count; index_ += 1) {
		if (pairs[index_] !~ /=/) continue
		scope_name = pairs[index_]
		sub(/=.*$$/, "", scope_name)
		readable = pairs[index_]
		sub(/^[^=]*=/, "", readable)
		gsub(/_/, " ", readable)
		names[scope_name] = readable
	}
}
{
	hash = $$1
	refs = $$2
	subject = $$3
	body = $$4
	sub(/^\n+/, "", hash)

	if (_all != "" && match(refs, /tag: [^,]+/)) {
		tag = substr(refs, RSTART + 5, RLENGTH - 5)
		section_compare[section] = (_URL != "") ? _URL "/compare/" tag "..." section_version[section] : ""
		section += 1
		section_version[section] = tag
	}

	section_commit_count[section] += 1
	if (subject !~ /^[a-z]+(\([^)]+\))?!?: /) next

	scope = ""
	if (subject ~ /^[a-z]+\(/) {
		scope = subject
		sub(/^[a-z]+\(/, "", scope)
		sub(/\).*$$/, "", scope)
	}

	text = subject
	sub(/^[^:]+: /, "", text)
	is_breaking = (subject ~ /^[a-z]+(\([^)]+\))?!:/) || (body ~ /BREAKING[ -]CHANGE/)
	type = subject
	sub(/[(!:].*$$/, "", type)
	if (!is_breaking && index(" " _types " ", " " type " ") > 0) next

	if (!is_breaking && index(" " _commits " ", " " type "(" scope ")" " ") > 0) next

	root = scope
	sub(/\/.*$$/, "", root)

	is_grouped = (root in groups)
	label = is_grouped ? groups[root] : other
	nested_scope = scope
	if (is_grouped) sub(/^[^\/]+\/?/, "", nested_scope)
	second = nested_scope
	sub(/\/.*$$/, "", second)
	rest = nested_scope
	sub(/^[^\/]+\/?/, "", rest)
	short = substr(hash, 1, 7)
	reference = (_URL != "") ? " ([" short "](" _URL "/commit/" hash "))" : " (" short ")"

	entry = "- "
	if (rest != "") entry = entry "**" rest ":** "
	entry = entry text
	if (is_breaking) entry = entry " — **breaking**"
	entry = entry reference

	if (is_breaking) {
		breaking[section] = breaking[section] "- " (scope != "" ? "**" scope ":** " : "") text \
			" — **breaking**" reference "\n"
	}

	if (nested_scope == "") {
		unscoped_entries[section, label] = unscoped_entries[section, label] entry "\n"
	} else {
		if (!((section, label, second) in seen_seconds)) {
			seen_seconds[section, label, second] = 1
			second_count[section, label] += 1
			second_name[section, label, second_count[section, label]] = second
		}
		if (rest == "") {
			unscoped_second_entries[section, label, second] = unscoped_second_entries[section, label, second] entry "\n"
		} else {
			sub_count[section, label, second] += 1
			sub_key[section, label, second, sub_count[section, label, second]] = rest
			sub_entry[section, label, second, sub_count[section, label, second]] = entry
		}
	}

	seen_labels[section, label] = 1
	has_content[section] = 1
}
END {
	for (index_ = 0; index_ <= section; index_ += 1) {
		rendered_section = flush(index_, section_version[index_], (_all != "") ? section_compare[index_] : _compare)
		if (rendered_section == "") continue
		document = (document == "") ? rendered_section : document "\n" rendered_section
	}
	if (document == "") exit
	sub(/\n+$$/, "", document)
	print document
}
endef

define _FRESH_ESCAPE_AWK_SOURCE
{
	escaped_line = ""
	while (match($$0, /[][*?\\]/)) {
		escaped_line = escaped_line substr($$0, 1, RSTART - 1) "\\" substr($$0, RSTART, 1)
		$$0 = substr($$0, RSTART + 1)
	}
	print escaped_line $$0
}
endef

define _FRESH_KEPT_AWK_SOURCE
previous != "" && !(previous ~ /\/$$/ && index($$0, previous) == 1) { print previous }
{ previous = $$0 }
END { if (previous != "") print previous }
endef

define _GROUP_AWK_SOURCE
function repeat(text, times,    out) { out = ""; while (times-- > 0) out = out text; return out }
function get_value(record, key,    pattern) {
	pattern = "\"" key "\"[ \t]*:[ \t]*\""
	if (!match(record, pattern)) return ""
	record = substr(record, RSTART + RLENGTH)
	if (!match(record, /^([^"\\]|\\.)*/)) return ""
	return substr(record, RSTART, RLENGTH)
}
function unescape(text) {
	gsub(/\\"/, "\"", text)
	gsub(/\\\//, "/", text)
	gsub(/\\\\/, "\\", text)
	return text
}
function add(identifier, message) {
	if (identifier == "" || identifier == "null") return
	identifier = unescape(identifier)
	message = unescape(message)
	if (!(identifier in seen_identifiers)) { seen_identifiers[identifier] = 1; order[++total] = identifier }
	count[identifier] += 1
	if (example[identifier] == "") example[identifier] = message
}
BEGIN { if (_IDENTIFIER != "" && _MESSAGE != "") RS = "}" }
{ raw = raw (NR > 1 ? RS : "") $$0; if ($$0 ~ /(^|\n)[ \t]*[[{]/) has_structured_output = 1 }
_IDENTIFIER == "" && match($$0, /(\([0-9]+,[0-9]+\):|:[0-9]+(:[0-9]+)?) error [A-Za-z0-9_.\/-]+:? /) { token = substr($$0, RSTART, RLENGTH); sub(/^[^ ]* error /, "", token); sub(/:? $$/, "", token); add(token, substr($$0, RSTART + RLENGTH)) }
_IDENTIFIER != "" && _MESSAGE != "" {
	pending = pending $$0
	quotes = pending
	gsub(/\\./, "", quotes)
	if (gsub(/"/, "", quotes) % 2) { pending = pending "}"; next }
	if (index(pending, "\"suppressedMessages\"")) is_in_suppressed_messages = 1
	if (!is_in_suppressed_messages) add(get_value(pending, _IDENTIFIER), get_value(pending, _MESSAGE))
	if (index(pending, "\"errorCount\"")) is_in_suppressed_messages = 0
	pending = ""
}
_IDENTIFIER != "" && _MESSAGE == "" { buffer = buffer " " $$0 }
END {
	pattern = "\"" _IDENTIFIER "\":[ \t]*\\["
	rest = buffer
	while (_IDENTIFIER != "" && _MESSAGE == "" && match(rest, pattern)) {
		items = substr(rest, RSTART + RLENGTH)
		rest = items
		sub(/\].*/, "", items)
		size = split(items, parts, ",")
		for (item = 1; item <= size; item += 1) {
			gsub(/^[ \t]*"|"[ \t]*$$/, "", parts[item])
			add(parts[item], "")
		}
	}
	for (outer = 1; outer < total; outer += 1)
		for (inner = 1; inner <= total - outer; inner += 1)
			if (count[order[inner]] < count[order[inner + 1]]) {
				swap = order[inner]; order[inner] = order[inner + 1]; order[inner + 1] = swap
			}
	padding = repeat(" ", 2 * _INDENT)
	if (total == 0 && (_STATUS != 0 || (_IDENTIFIER != "" && !has_structured_output))) {
		sub(/\n+$$/, "", raw)
		gsub(/\n/, "\n" padding, raw)
		if (raw ~ /[^ \t\n]/) printf "%s%s\n", padding, raw
		exit _STATUS
	}
	if (total == 0) { printf "%s$(call text,✔,$(COLOR_SUCCESS)) $(call text,No findings,$(COLOR_DESCRIPTION))\n", padding; exit }
	for (position = 1; position <= total; position += 1) {
		if (length(order[position]) > width) width = length(order[position])
		if (length(count[order[position]]) > number_width) number_width = length(count[order[position]])
		findings += count[order[position]]
	}
	for (position = 1; position <= total; position += 1) {
		identifier = order[position]
		message = example[identifier]
		printf "%s%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s,$(COLOR_ERROR))%s", \
			padding, repeat(" ", number_width - length(count[identifier])), count[identifier], identifier, \
			(message == "" ? "\n" : repeat(" ", width - length(identifier)) "  ")
		if (message != "") printf "$(call text,%s,$(COLOR_DESCRIPTION))\n", message
	}
	printf "%s$(call text,✘,$(COLOR_ERROR)) $(call text,%s %s,$(COLOR_DESCRIPTION))\n", padding, findings, (findings == 1 ? "finding" : "findings")
}
endef

define _HELP_AWK_SOURCE
function trim(string) { gsub(/^[ \t\r\n]+|[ \t\r\n]+$$/, "", string); return string }
function get_leaf(path) { sub(/.*\//, "", path); return path }
function repeat(text, count,    out) { out = ""; while (count-- > 0) out = out text; return out }
function get_character_bytes(leading_byte) {
	return (leading_byte < "\200") ? 1 : ((leading_byte < "\340") ? 2 : ((leading_byte < "\360") ? 3 : 4))
}
function get_character_width(character) {
	if (character < "\340") return 1
	if (character < "\360") return (character >= "\342\230\200" && character <= "\342\237\277") ? 2 : 1
	return 2
}
function get_display_width(text,    position, byte_count, total_width) {
	total_width = 0
	for (position = 1; position <= length(text); position += byte_count) {
		byte_count = get_character_bytes(substr(text, position, 1))
		total_width += get_character_width(substr(text, position, byte_count))
	}
	return total_width
}
function truncate(text, width_limit,    position, byte_count, character, taken_width, truncated_text) {
	if (get_display_width(text) <= width_limit) return text
	taken_width = 0
	truncated_text = ""
	for (position = 1; position <= length(text); position += byte_count) {
		byte_count = get_character_bytes(substr(text, position, 1))
		character = substr(text, position, byte_count)
		if (taken_width + get_character_width(character) > width_limit - 1) break
		truncated_text = truncated_text character
		taken_width += get_character_width(character)
	}
	return truncated_text "…"
}
function render_url(template, file, line) {
	if (template == "") return ""
	if (index(template, "{cwd}") && substr(file, 1, 1) == "/") { gsub(/\{cwd\}/, "", template); file = substr(file, 2) }
	else gsub(/\{cwd\}/, ENVIRON["_CURDIR"], template)
	gsub(/\{wslDistro\}/, ENVIRON["WSL_DISTRO_NAME"], template)
	gsub(/\{file\}/, file, template)
	gsub(/\{line\}/, line, template)
	return template
}
function render_link(text, path, line_number,    url) {
	if (index(path, ENVIRON["_APP_DIR"] "/") == 1) path = substr(path, length(ENVIRON["_APP_DIR"]) + 2)
	if (index(path, ENVIRON["_CURDIR"] "/") == 1) path = substr(path, length(ENVIRON["_CURDIR"]) + 2)
	url = (path != "" ? render_url(ENVIRON["_EDITOR_URL_TEMPLATE"], path, line_number) : "")
	if (url == "" || ENVIRON["_IS_NO_COLOR"]) return text
	return "\033]8;;" url "\033\\" text "\033]8;;\033\\"
}
function get_named_path(path) { return index(path, ENVIRON["_CURDIR"] "/") == 1 ? "./" substr(path, length(ENVIRON["_CURDIR"]) + 2) : path }
function strip_verbosity(text) { sub(/[ \t]*#v+([ \t]|$$)/, "", text); return text }
function get_line_verbosity(line,    verbosity_match) { if (line == "") line = $$0; if (!match(line, /#v+([[:space:]]|$$)/)) return 0; verbosity_match = substr(line, RSTART + 1, RLENGTH - 1); sub(/[[:space:]]$$/, "", verbosity_match); if (length(verbosity_match) > max_verbosity) max_verbosity = length(verbosity_match); return length(verbosity_match) }
function get_description() { if (!match($$0, /#~~[ ]*/)) return ""; return trim(strip_verbosity(substr($$0, RSTART + RLENGTH))) }
function render_message(text,    out) {
	out = ""
	while (match(text, /`[^`]+`/)) {
		out = out substr(text, 1, RSTART - 1) "$(if $(_IS_NO_COLOR),`,$(call _ansi,$(COLOR_HIGHLIGHT)))" substr(text, RSTART + 1, RLENGTH - 2) "$(if $(_IS_NO_COLOR),`,$(_ANSI_RESET))"
		text = substr(text, RSTART + RLENGTH)
	}
	return out text
}
function render_inline_code(text,    out, ansi_open, ansi_close) {
	ansi_open = ENVIRON["_INLINE_CODE_OPEN"]
	ansi_close = ENVIRON["_INLINE_CODE_CLOSE"]
	out = ""
	while (match(text, /`[^`]+`/)) {
		out = out substr(text, 1, RSTART - 1) ansi_open substr(text, RSTART + 1, RLENGTH - 2) ansi_close
		text = substr(text, RSTART + RLENGTH)
	}
	return out text
}
function derive_description(documentation,    line) {
	match(documentation, /^[^\n]+/)
	line = tolower(substr(documentation, RSTART, 1)) substr(documentation, RSTART + 1, RLENGTH - 1)
	sub(/\.[[:space:]]*$$/, "", line)
	return line
}
function expand_make(expr,    result, varname, previous) {
	result = expr
	while (match(result, /\$$[({][A-Za-z0-9_]+[)}]/)) {
		previous = result
		varname = substr(result, RSTART + 2, RLENGTH - 3)
		result = substr(result, 1, RSTART - 1) (varname in ENVIRON ? ENVIRON[varname] : "") substr(result, RSTART + RLENGTH)
		if (result == previous) break
	}
	return result
}
function does_path_exist(path,    line, does_exist) { does_exist = (getline line < path) >= 0; close(path); return does_exist }
function eval_wildcard(line,    count, paths, i, path) {
	count = split(substr(line, index(line, "wildcard") + 9), paths, /[[:space:]]+/)
	for (i = 1; i <= count; i += 1) {
		path = paths[i]
		if (!index(path, "/")) continue
		path = substr(path, index(path, "/"))
		sub(/[^A-Za-z0-9_.\/-]+$$/, "", path)
		if (does_path_exist(ENVIRON["_CURDIR"] path)) return 1
	}
	return 0
}
function eval_condition(line,    varname) {
	if (line ~ /^ifneq/ && index(line, "$$(wildcard ")) return eval_wildcard(line)
	if (match(line, /^ifdef[ \t]+/)) {
		varname = trim(substr(line, RLENGTH + 1))
		return (varname in ENVIRON && ENVIRON[varname] != "")
	}
	if (line ~ /^ifndef[ \t]+/) return 1
	return eval_ifeq(line)
}
function eval_ifeq(line,    op_eq, rest, depth, comma, i, c, arg1, arg2, quote) {
	if (!match(line, /^ifn?eq[ \t]*/)) return 1
	op_eq = (substr(line, 1, 4) == "ifeq")
	rest = substr(line, RLENGTH + 1)
	if (substr(rest, 1, 1) == "\"" || substr(rest, 1, 1) == SQ) {
		quote = substr(rest, 1, 1)
		if (!match(rest, "^" quote "[^" quote "]*" quote "[ \t]+" quote "[^" quote "]*" quote)) return 1
		i = index(substr(rest, 2), quote) + 1
		arg1 = expand_make(substr(rest, 2, i - 2))
		rest = substr(rest, i + 1)
		sub(/^[ \t]+/, "", rest)
		i = index(substr(rest, 2), substr(rest, 1, 1)) + 1
		arg2 = expand_make(substr(rest, 2, i - 2))
		return op_eq ? (arg1 == arg2) : (arg1 != arg2)
	}
	if (substr(rest, 1, 1) != "(") return 1
	for (i = 2; i <= length(rest); i++) {
		c = substr(rest, i, 1)
		if (c == "(") depth++
		else if (c == ")" && depth > 0) depth--
		else if (c == ")") break
		else if (c == "," && depth == 0 && !comma) comma = i
	}
	arg1 = expand_make(trim(substr(rest, 2, comma - 2)))
	arg2 = expand_make(trim(substr(rest, comma + 1, i - comma - 1)))
	return op_eq ? (arg1 == arg2) : (arg1 != arg2)
}
function begin_condition(line) {
	condition_depth++
	if (line ~ /^ifndef[ \t]/) is_in_ifndef = 1
	is_parent_active = (condition_depth == 1 || is_condition_active[condition_depth - 1])
	is_condition_active[condition_depth]      = is_parent_active ? eval_condition(line) : 0
	has_condition_matched[condition_depth] = is_condition_active[condition_depth]
}
function else_condition(line,    else_rest) {
	if (condition_depth == 0) return
	is_parent_active = (condition_depth == 1 || is_condition_active[condition_depth - 1])
	else_rest = line; sub(/^else[ \t]*/, "", else_rest)
	if (is_parent_active && else_rest ~ /^(ifeq|ifneq|ifdef|ifndef)[ \t]/) {
		if (has_condition_matched[condition_depth]) is_condition_active[condition_depth] = 0
		else {
			is_condition_active[condition_depth] = eval_condition(else_rest)
			if (is_condition_active[condition_depth]) has_condition_matched[condition_depth] = 1
		}
	} else if (is_parent_active) is_condition_active[condition_depth] = !has_condition_matched[condition_depth]
}
function does_match_scope(path,    i, f) {
	if (scope_filter_count == 0) return 1
	if (path == "main") return 1
	for (i = 1; i <= scope_filter_count; i += 1) {
		f = scope_filter[i]
		if (f == "") continue
		if (path == f) return 1
		if (index(path, f "/") == 1) return 1
		if (index(f, path "/") == 1) return 1
	}
	return 0
}
function is_scope_target(path,    i, f) {
	if (scope_filter_count == 0) return 1
	for (i = 1; i <= scope_filter_count; i += 1) {
		f = scope_filter[i]
		if (f == "") continue
		if (path == f) return 1
		if (index(path, f "/") == 1) return 1
	}
	return 0
}
function is_scope_named(path,    i, f) {
	for (i = 1; i <= scope_filter_count; i += 1) {
		f = scope_filter[i]
		if (f != "" && (f == path || index(f, path "/") == 1)) return 1
	}
	return 0
}
function is_scope_hidden(path) { return hidden_scopes[path] && !is_listing_hidden && !is_scope_named(path) }
function is_scope_visible(path) { return !is_scope_hidden(path) && scope_verbosity[path] + 0 <= verbosity }
function is_entry_visible(scope_path, section, idx) {
	if ((scope_path, section, idx) in entry_override_level)
		return !hidden_entry_overrides[scope_path, section, idx] && entry_override_level[scope_path, section, idx] <= verbosity
	if (section_verbosity[scope_path, section, idx] + 0 > verbosity) return 0
	if (section_file[scope_path, section, idx] == scope_override_file[scope_path]) return scope_base_verbosity[scope_path] <= verbosity
	return is_scope_visible(scope_path)
}
function has_visible_entries(scope_path, section,    idx) {
	if (!does_match_scope(scope_path)) return 0
	if (!is_scope_target(scope_path)) return 0
	for (idx = 1; idx <= section_count[scope_path, section] + 0; idx += 1)
		if (is_entry_visible(scope_path, section, idx)) return 1
	return 0
}
function record_scope_error(level, name,    location, above) {
	if (scope_error != "") return
	location = get_source_location()
	if (name == "") scope_error = sprintf("Scope header at %s has no name.", location)
	else if (index(name, "/") || index(name, ".")) {
		scope_error = sprintf("Scope `%s` at %s has a `%s` in its name.", name, location, index(name, "/") ? "/" : ".")
		scope_notice = "Rename it without `.` or `/`."
	} else if (level < 0) scope_error = sprintf("Scope `%s` at %s is depth %d; scopes start at `#--`.", \
		name, location, level)
	else {
		if (scope_depth >= 0) { above = scope_at[scope_depth]; gsub(/\//, ".", above) }
		scope_error = sprintf("Scope `%s` at %s is depth %d, but %s.", name, location, level, \
			scope_depth < 0 ? "no scope above it is open" : sprintf("`%s` above it is depth %d", above, scope_depth))
		scope_notice = sprintf("Write `#%s %s`, or put a depth-%d scope %s.", \
			repeat("-", scope_depth + 3), name, scope_depth + 1, scope_depth < 0 ? "above it" : "between them")
	}
}
function close_empty_scope() {
	if (scope_error == "") {
		scope_error = sprintf("Scope `%s` at %s holds nothing.", open_scope_name, open_scope_location)
		scope_notice = "Add a command, variable, function or description, or remove it."
	}
	open_scope_name = ""
}
function push_scope(level, name, verbosity,    current, parent_verbosity, parent) {
	if (name == "" || index(name, "/") || index(name, ".") || level < 0 || level > scope_depth + 1) {
		record_scope_error(level, name)
		return
	}
	if (open_scope_name != "" && level <= open_scope_level) close_empty_scope()
	open_scope_name = name
	open_scope_level = level
	open_scope_location = get_source_location()
	scope_at[level] = (level > 0 && scope_at[level - 1] != "" ? scope_at[level - 1] "/" name : name)
	scope_depth = level
	current = scope_at[level]
	scope_marker_verbosity[current] = verbosity
	if (!((current, FILENAME) in scope_declared_in)) scope_declaration_count[current] += 1
	scope_declared_in[current, FILENAME] = 1
	scope_parent[current] = (level > 0 ? scope_at[level - 1] : "")
	parent_verbosity = (level > 0 ? scope_verbosity[scope_at[level - 1]] + 0 : 0)
	scope_verbosity[current] = (verbosity > parent_verbosity ? verbosity : parent_verbosity)
	if (current in seen_scope) return
	seen_scope[current] = 1
	parent = (level > 0 ? scope_at[level - 1] : "")
	if (parent == "" || parent == "main") {
		if (parent == "main" && !("main" in seen_scope)) { seen_scope["main"] = 1; top_count += 1; top_order[top_count] = "main" }
		top_count += 1
		top_order[top_count] = current
	} else {
		child_count[parent] += 1
		children[parent, child_count[parent]] = current
	}
}
function get_source_location(    shown_path) {
	shown_path = get_named_path(FILENAME)
	if (shown_path !~ /^\.?\//) shown_path = "./" shown_path
	return render_link(shown_path ":" FNR, FILENAME, FNR)
}
function record_override(kind, marker, depth,    count) {
	count = ++override_count
	match(marker, /^v*/)
	override_level[count] = RLENGTH
	if (RLENGTH > max_verbosity) max_verbosity = RLENGTH
	marker = substr(marker, RLENGTH + 1)
	hidden_overrides[count] = (substr(marker, 1, 2) == "!!")
	override_name[count] = trim(substr(marker, hidden_overrides[count] ? 3 : 2))
	override_kind[count] = kind
	override_depth[count] = depth
	override_file[count] = FILENAME
	override_location[count] = get_source_location()
}
function fail_override(message, notice) {
	printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) %s\n", render_message(message)
	if (notice != "") printf "$(call text,[$(_LABEL)],$(COLOR_NOTICE)) %s\n", render_message(notice)
	exit $(BRNSHKR_CONFIG_ERROR_CODE)
}
function fail_unknown_override(count, notice) {
	fail_override(sprintf("Unknown %s `%s` at %s.", \
		override_kind[count], override_name[count], override_location[count]), notice)
}
function is_symbol_named(entry, name) { sub(/, .*/, "", entry); return index("/" entry "/", "/" name "/") > 0 }
function apply_symbol_override(count,    sections, section_index, section, scope_path, idx, is_matched) {
	split("variables functions commands", sections, " ")
	for (scope_path in seen_scope)
		for (section_index = 1; section_index <= 3; section_index += 1) {
			section = sections[section_index]
			for (idx = 1; idx <= section_count[scope_path, section] + 0; idx += 1) {
				if (section_file[scope_path, section, idx] == override_file[count]) continue
				if (!is_symbol_named(section_name[scope_path, section, idx], override_name[count])) continue
				is_matched = 1
				entry_override_level[scope_path, section, idx] = override_level[count]
				hidden_entry_overrides[scope_path, section, idx] = hidden_overrides[count]
			}
		}
	if (!is_matched) fail_unknown_override(count, "")
}
function apply_overrides(    count, key, segment_count, segments, first) {
	for (count = 1; count <= override_count; count += 1) {
		key = override_name[count]
		if (override_kind[count] == "scope") {
			segment_count = split(key, segments, ".")
			if (segment_count != override_depth[count] + 1)
				fail_override(sprintf("`%s` at %s is a depth-%d scope, not depth %d.", \
					key, override_location[count], segment_count - 1, override_depth[count]), \
					sprintf("Write `#%s!` for it.", repeat("-", segment_count + 1)))
			gsub(/\./, "/", key)
			if (scope_declaration_count[key] + 0 <= ((key, override_file[count]) in scope_declared_in))
				fail_unknown_override(count, "Run `make help ls` to see available scopes.")
		}
		if ((override_kind[count], key) in override_index) {
			first = override_index[override_kind[count], key]
			fail_override(sprintf("Duplicate override of `%s` at %s and %s.", \
				override_name[count], override_location[first], override_location[count]), "")
		}
		override_index[override_kind[count], key] = count
		if (override_kind[count] == "symbol") apply_symbol_override(count)
	}
}
function resolve_scope_level(path,    parent, count) {
	if (path in is_scope_level_resolved) return
	is_scope_level_resolved[path] = 1
	parent = scope_parent[path]
	if (parent != "") resolve_scope_level(parent)
	scope_base_verbosity[path] = scope_marker_verbosity[path] + 0
	if (parent != "" && scope_base_verbosity[parent] > scope_base_verbosity[path])
		scope_base_verbosity[path] = scope_base_verbosity[parent]
	scope_verbosity[path] = scope_marker_verbosity[path] + 0
	if (parent != "" && scope_verbosity[parent] > scope_verbosity[path]) scope_verbosity[path] = scope_verbosity[parent]
	hidden_scopes[path] = (parent != "" ? hidden_scopes[parent] : 0)
	scope_override_file[path] = (parent != "" ? scope_override_file[parent] : "")
	if (("scope", path) in override_index) {
		count = override_index["scope", path]
		scope_verbosity[path] = override_level[count]
		hidden_scopes[path] = hidden_overrides[count]
		scope_override_file[path] = override_file[count]
	}
}
function get_alias(name,    map, position, remainder) {
	map = " " ENVIRON["_TARGET_ALIAS_MAP"] " "
	position = index(map, " " name "=")
	if (!position) return ""
	remainder = substr(map, position + length(name) + 2)
	return ", " substr(remainder, 1, index(remainder, " ") - 1)
}
function add_resolved_variables(    keys, key_count, key_idx, name, pair_count, pairs, record_count, records, record_fields, record_idx) {
	record_count = split(ENVIRON["_DOTENV_REPORT"], records, "\n")
	for (record_idx = 1; record_idx <= record_count; record_idx += 1) {
		if (split(records[record_idx], record_fields, "\037") < 5 || record_fields[4] != "") continue
		variable_source[record_fields[2]] = get_named_path(record_fields[1])
		variable_source_file[record_fields[2]] = record_fields[1]
		variable_source_line[record_fields[2]] = record_fields[5]
	}
	key_count = split(ENVIRON["_DOTENV_FILE_KEYS"], keys, /[[:space:]]+/)
	pair_count = split(ENVIRON["_VARIABLE_SOURCES"], pairs, /[[:space:]]+/)
	for (key_idx = 1; key_idx <= pair_count; key_idx += 1) {
		if (!index(pairs[key_idx], "=")) continue
		name = substr(pairs[key_idx], 1, index(pairs[key_idx], "=") - 1)
		variable_source[name] = substr(pairs[key_idx], index(pairs[key_idx], "=") + 1)
		variable_source_file[name] = ""
		keys[++key_count] = name
	}
	scope_depth = 0
	scope_at[0] = "main"
	for (key_idx = 1; key_idx <= key_count; key_idx += 1) {
		name = keys[key_idx]
		if (name == "" || name in scraped_variable || !(name in ENVIRON)) continue
		add("variables", name, ENVIRON[name], "?=", "", 0, 0)
		section_file["main", "variables", section_count["main", "variables"]] = ""
	}
}
function resolve_scope(    current) {
	current = (scope_depth > 0 ? scope_at[scope_depth] : scope_at[0] != "" ? scope_at[0] : "main")
	if (current == "main" && !("main" in seen_scope)) { seen_scope["main"] = 1; top_count += 1; top_order[top_count] = "main" }
	return current
}
function add(section, name, text, extra, description, line, entry_verbosity,    current, idx) {
	current = resolve_scope()
	if (!current) return
	if (name != "" && seen_symbol[current, section, name]++) return
	idx = ++section_count[current, section]
	section_name[current, section, idx] = name (section == "commands" ? get_alias(name) : "")
	section_text[current, section, idx] = text
	section_extra[current, section, idx] = extra
	section_description[current, section, idx] = description
	section_file[current, section, idx] = FILENAME
	section_line[current, section, idx] = line
	section_verbosity[current, section, idx] = (entry_verbosity != "" ? entry_verbosity : get_line_verbosity())
}
function handle_define(documentation,    description) {
	if ($$2 ~ /^_/) return
	if ($$2 ~ /^[A-Z][A-Z0-9_]*$$/) {
		add("variables", $$2, "(macro)", (is_in_ifndef ? "?=" : ":="), get_description(), FNR)
		scraped_variable[$$2] = 1
	} else {
		description = get_description()
		if (documentation != "") add("functions", $$2, documentation, (description != "" ? description : derive_description(documentation)), "", FNR)
		else add("functions", $$2, "", description, "", FNR)
	}
}
function has_content(scope_path, section,    idx) {
	if (!does_match_scope(scope_path)) return 0
	if (has_visible_entries(scope_path, section)) return 1
	for (idx = 1; idx <= child_count[scope_path]; idx += 1)
		if (has_content(children[scope_path, idx], section)) return 1
	return 0
}
function has_any_content(scope_path) {
	return has_content(scope_path, "description") || has_content(scope_path, "variables") \
		|| has_content(scope_path, "functions") || has_content(scope_path, "commands")
}
function get_lowest_verbosity(scope_path,    shown, level) {
	shown = verbosity
	for (level = 0; level <= max_verbosity; level += 1) { verbosity = level; if (has_any_content(scope_path)) break }
	verbosity = shown
	return (level <= max_verbosity ? level : -1)
}
function get_tree_max(scope_path, depth, section, mode,    best, idx, width, child_width, value) {
	if (!does_match_scope(scope_path)) return 0
	if (is_scope_target(scope_path)) {
		for (idx = 1; idx <= section_count[scope_path, section] + 0; idx += 1) {
			if (!is_entry_visible(scope_path, section, idx)) continue
			if (mode == "value" && section_description[scope_path, section, idx] == "") continue
			value = section_text[scope_path, section, idx]
			if (mode == "value" && ENVIRON["_HAS_RESOLVE_ARGUMENT"] != "" && section_name[scope_path, section, idx] in ENVIRON) value = trim(ENVIRON[section_name[scope_path, section, idx]])
			width = (mode == "value") ? length(value) : (mode == "source") ? length(variable_source[section_name[scope_path, section, idx]]) : (depth + 2) * ENVIRON["INDENT"] + length(section_name[scope_path, section, idx])
			if (width > best) best = width
		}
	}
	for (idx = 1; idx <= child_count[scope_path]; idx += 1) {
		child_width = get_tree_max(children[scope_path, idx], depth + 1, section, mode)
		if (child_width > best) best = child_width
	}
	return best
}
function walk(scope_path, depth, section, column,    render_scope, padding, width, format, idx, entry, text, extra, description, count, lines, line_idx, has_pending_blank, was_function_printed) {
	if (!does_match_scope(scope_path)) return
	if (!has_content(scope_path, section)) return
	if (scope_path != "main") {
		printf "%s$(call text,%s,$(COLOR_SCOPE))\n", repeat(" ", (depth + 1) * ENVIRON["INDENT"]), get_leaf(scope_path)
	}
	render_scope = has_visible_entries(scope_path, section)
	if (render_scope) {
		padding = repeat(" ", (depth + 2) * ENVIRON["INDENT"])
		width = column - (depth + 2) * ENVIRON["INDENT"]
		for (idx = 1; idx <= section_count[scope_path, section] + 0; idx += 1) {
			if (!is_entry_visible(scope_path, section, idx)) continue
			entry = section_name[scope_path, section, idx]
			text = section_text[scope_path, section, idx]
			extra = section_extra[scope_path, section, idx]
			description = section_description[scope_path, section, idx]
			if (section == "variables" && ENVIRON["_HAS_RESOLVE_ARGUMENT"] != "" && entry in ENVIRON) text = trim(ENVIRON[entry])
			if (section == "variables" && source_width) extra = extra "  " variable_source[entry] repeat(" ", source_width - length(variable_source[entry]))
			if (section == "variables") description = render_inline_code(description)
			else if (section == "commands") text = render_inline_code(text)
			else if (section == "functions") { extra = render_inline_code(extra); text = render_inline_code(text) }
			else if (section == "description") text = render_inline_code(text)
			linked_entry = (section == "variables" && variable_source_file[entry] != "") \
				? render_link(entry, variable_source_file[entry], variable_source_line[entry]) \
				: render_link(entry, section_file[scope_path, section, idx], section_line[scope_path, section, idx])
			entry_pad = width - length(entry)
			if (entry_pad < 0) entry_pad = 0
			padded_entry = linked_entry repeat(" ", entry_pad)
			if (section == "variables") {
				if (description != "") {
					text = truncate(text, description_column)
					format = sprintf("%2s  %s%s", extra, text, repeat(" ", description_column - get_display_width(text)))
					printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s (%s),$(COLOR_DESCRIPTION))\n", padding, padded_entry, format, description
				} else {
					printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%2s  %s,$(COLOR_DESCRIPTION))\n", padding, padded_entry, extra, text
				}
			} else if (section == "commands") {
				printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s,$(COLOR_DESCRIPTION))\n", padding, padded_entry, text
			} else if (section == "functions") {
				if (was_function_printed && verbosity >= 2 && text != "") printf "\n"
				was_function_printed = (text != "" && verbosity >= 2)
				if (extra != "" && (verbosity < 2 || text == "")) {
					printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s,$(COLOR_DESCRIPTION))\n", padding, padded_entry, extra
				} else {
					printf "%s$(call text,%s,$(COLOR_ENTRY))\n", padding, linked_entry
				}
				if (verbosity >= 2 && text != "") {
					count = split(text, lines, "\n")
					has_pending_blank = 0
					for (line_idx = 1; line_idx <= count; line_idx += 1) {
						if (lines[line_idx] == "") { if (verbosity < 3) break; has_pending_blank = 1; continue }
						if (has_pending_blank) { printf "\n"; has_pending_blank = 0 }
						printf "%s$(call text,%s,$(COLOR_DESCRIPTION))\n", padding " ", lines[line_idx]
					}
				}
			} else {
				printf "%s$(call text,%s,$(COLOR_DESCRIPTION))\n", padding, text
			}
		}
	}
	for (idx = 1; idx <= child_count[scope_path]; idx += 1) {
		if (!has_content(children[scope_path, idx], section)) continue
		walk(children[scope_path, idx], depth + 1, section, column)
	}
}
function to_title_case(text,    words, word_count, word_index, titled_text) {
	word_count = split(text, words, "-")
	for (word_index = 1; word_index <= word_count; word_index += 1) titled_text = titled_text (word_index > 1 ? " " : "") toupper(substr(words[word_index], 1, 1)) substr(words[word_index], 2)
	return titled_text
}
function print_header(    logo, logo_open, logo_reset, logo_lines, line_count, line_idx, header_title) {
	logo = ENVIRON["_HEADER_LOGO"]; sub(/__BRNSHKR_LOGO_END__$$/, "", logo); logo_open = ""; logo_reset = ""
	if (match(logo, /^\033\[[0-9;]*m/)) { logo_open = substr(logo, 1, RLENGTH); logo = substr(logo, RLENGTH + 1) }
	if (match(logo, /\033\[[0-9;]*m$$/)) { logo_reset = substr(logo, RSTART); logo = substr(logo, 1, RSTART - 1) }
	if (length(trim(logo)) > 0) {
		line_count = split(logo, logo_lines, "\n")
		for (line_idx = 1; line_idx <= line_count; line_idx += 1) printf "%s%s%s\n", logo_open, logo_lines[line_idx], logo_reset
	}
	header_title = ENVIRON["_HEADER_TITLE"]; sub(/@@brnshkr-package@@/, to_title_case(ENVIRON["_HEADER_PACKAGE"]), header_title)
	if (length(trim(header_title)) > 0) printf "%s\n\n", header_title
	printf "%s\n", ENVIRON["_HEADER_USAGE"]
}
function render_environment_value(value) { gsub(/\n/, " ", value); return truncate(value, ENVIRON["VALUE_WIDTH"] + 0) }
function print_environment_row(name, path, line_number, value, replacement_note,    indentation, linked_name) {
	indentation = repeat(" ", 2 * ENVIRON["INDENT"])
	linked_name = render_link(name, path, line_number) repeat(" ", environment_name_width - length(name))
	if (replacement_note == "") printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s,$(COLOR_DESCRIPTION))\n", indentation, linked_name, value
	else printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s  (%s),$(COLOR_DESCRIPTION))\n", indentation, linked_name, value repeat(" ", environment_value_width - get_display_width(value)), render_inline_code(replacement_note)
}
function print_environment(    listed_names, name_index, exported_name, exported_names, exported_name_count, sort_index, compare_index, held_name, record_fields, record_index, record_path, previous_record_path, replacement_note) {
	split(ENVIRON["_HIDDEN_NAMES"], listed_names, /[[:space:]]+/)
	for (name_index in listed_names) if (listed_names[name_index] != "") is_hidden_name[listed_names[name_index]] = 1
	split(ENVIRON["_COMMAND_LINE_NAMES"], listed_names, /[[:space:]]+/)
	for (name_index in listed_names) if (listed_names[name_index] != "") is_command_line_name[listed_names[name_index]] = 1
	dotenv_record_count = split(ENVIRON["_DOTENV_REPORT"], dotenv_records, "\n")
	for (record_index = 1; record_index <= dotenv_record_count; record_index += 1) {
		if (split(dotenv_records[record_index], record_fields, "\037") < 5) continue
		is_dotenv_name[record_fields[2]] = 1
		if (length(record_fields[2]) > environment_name_width) environment_name_width = length(record_fields[2])
		if (get_display_width(render_environment_value(record_fields[3])) > environment_value_width) environment_value_width = get_display_width(render_environment_value(record_fields[3]))
	}
	split(ENVIRON["_EXPORTED_NAMES"], listed_names, /[[:space:]]+/)
	for (name_index in listed_names) {
		exported_name = listed_names[name_index]
		if (exported_name == "" || exported_name in is_hidden_name || exported_name in is_dotenv_name || !(exported_name in ENVIRON)) continue
		if (verbosity < 3 && (exported_name ~ /^_/ || exported_name == "MAKEFLAGS" || exported_name == "SHELL")) continue
		exported_names[++exported_name_count] = exported_name
		if (length(exported_name) > environment_name_width) environment_name_width = length(exported_name)
	}
	for (sort_index = 2; sort_index <= exported_name_count; sort_index += 1) {
		held_name = exported_names[sort_index]; compare_index = sort_index - 1
		while (compare_index >= 1 && exported_names[compare_index] > held_name) { exported_names[compare_index + 1] = exported_names[compare_index]; compare_index -= 1 }
		exported_names[compare_index + 1] = held_name
	}
	if (exported_name_count > 0) printf "\n$(call text,Exported:,$(COLOR_SECTION_LABEL))\n"
	for (name_index = 1; name_index <= exported_name_count; name_index += 1) {
		exported_name = exported_names[name_index]
		print_environment_row(exported_name, variable_definition_file[exported_name], variable_definition_line[exported_name], render_environment_value(ENVIRON[exported_name]), "")
	}
	for (record_index = 1; record_index <= dotenv_record_count; record_index += 1) {
		if (split(dotenv_records[record_index], record_fields, "\037") < 5) continue
		record_path = get_named_path(record_fields[1])
		if (previous_record_path == "") printf "\n$(call text,Environment files:,$(COLOR_SECTION_LABEL))\n"
		if (record_path != previous_record_path) printf "%s$(call text,%s,$(COLOR_SCOPE))\n", repeat(" ", ENVIRON["INDENT"]), record_path
		previous_record_path = record_path
		replacement_note = ""
		if (record_fields[2] in is_command_line_name) replacement_note = "replaced by the command line"
		else if (record_fields[4] == "environment") replacement_note = "replaced by the environment"
		else if (record_fields[4] != "") replacement_note = "replaced by `" get_named_path(record_fields[4]) "`"
		print_environment_row(record_fields[2], record_fields[1], record_fields[5], render_environment_value(record_fields[3]), replacement_note)
	}
}
function print_section(has_previous, section, label, column,    top_idx, top_path, found, needs_separator) {
	for (top_idx = 1; top_idx <= top_count; top_idx += 1) {
		top_path = top_order[top_idx]
		if (!has_content(top_path, section)) continue
		if (!found++) { if (has_previous) printf "\n"; printf "$(call text,%s,$(COLOR_SECTION_LABEL))\n", label }
		else if (needs_separator) printf "\n"
		walk(top_path, 0, section, column)
		needs_separator = (section == "functions" && verbosity >= 2)
	}
	return found + 0
}
BEGIN { SQ = sprintf("%c", 39); verbosity = ENVIRON["_VERBOSITY"] + 0; max_verbosity = verbosity; seen_scope["main"] = 1; top_count = 1; top_order[1] = "main"; scope_filter_count = (ENVIRON["_SCOPES"] != "" ? split(ENVIRON["_SCOPES"], scope_filter, /[[:space:]]+/) : 0); name_count = split(ENVIRON["_TARGET_NAMES"], name_pairs, /[[:space:]]+/); for (i = 1; i <= name_count; i += 1) { at = index(name_pairs[i], "="); resolved_name[substr(name_pairs[i], 1, at - 1)] = substr(name_pairs[i], at + 1) } }
current_file != FILENAME { if (open_scope_name != "") close_empty_scope(); current_file = FILENAME; for (i in scope_at) delete scope_at[i]; for (i in is_condition_active) delete is_condition_active[i]; for (i in has_condition_matched) delete has_condition_matched[i]; scope_depth = -1; is_in_define = 0; is_in_documentation = 0; is_in_ifndef = 0; condition_depth = 0 }
NF && !/^#-/ && !/^#([[:space:]]|$$)/ { open_scope_name = "" }
/^endef([[:space:]].*)?$$/ { is_in_define = 0; next }
is_in_define { next }
/^(ifeq|ifneq|ifdef|ifndef)[[:space:]]/ { begin_condition($$0); next }
/^else([[:space:]]|$$)/ { else_condition($$0); next }
/^endif([[:space:]]|$$)/ { if (condition_depth > 0) { condition_depth--; is_in_ifndef = 0 } next }
condition_depth > 0 && !is_condition_active[condition_depth] { next }
/^#-+v*!/ { match($$0, /^#-+/); record_override("scope", substr($$0, RLENGTH + 1), RLENGTH - 3); next }
/^#~~v*!/ { record_override("symbol", substr($$0, 4), -1); next }
/^#-+/ { match($$0, /^#-+/); scope_dash_count = RLENGTH - 1; scope_suffix = substr($$0, RLENGTH + 1); match(scope_suffix, /^v*/); scope_verbosity_count = RLENGTH; push_scope(scope_dash_count - 2, trim(substr(scope_suffix, scope_verbosity_count + 1)), scope_verbosity_count); next }
/^#\*\*/ { is_in_documentation = 1; documentation_text = ""; next }
is_in_documentation {
	if (/^#\*/) { sub(/^#\*[ ]?/, ""); documentation_text = documentation_text $$0 "\n"; next }
	if (/^define[ \t]+/) { sub(/\n+$$/, "", documentation_text); handle_define(documentation_text); is_in_documentation = 0; is_in_define = 1; next }
	if (!/^#/) { is_in_documentation = 0; next }
}
/^define[[:space:]]+/ { handle_define(""); is_in_define = 1; next }
/^#!!/ { sub(/^#!![ ]*/, ""); add("description", "", strip_verbosity($$0), "", "", FNR); next }
/^export[ \t]+[A-Za-z0-9_]+[ \t]*[?:+!]*=/ {
	match($$0, /^export[ \t]+/)
	exported_variable_name = substr($$0, RLENGTH + 1)
	sub(/[ \t?:+!=].*/, "", exported_variable_name)
	if (!(exported_variable_name in variable_definition_file)) { variable_definition_file[exported_variable_name] = FILENAME; variable_definition_line[exported_variable_name] = FNR }
	next
}
/^[A-Za-z0-9_]+[ \t]*[?:+!]*=/ {
	line_number = FNR
	var_source = $$0
	var_description = ""
	var_verbosity = ""
	if (match(var_source, /#~~[ ]*/)) {
		var_description = trim(strip_verbosity(substr(var_source, RSTART + RLENGTH)))
		var_source = substr(var_source, 1, RSTART - 1)
	}
	match(var_source, /^[A-Za-z0-9_]+/)
	var_name = substr(var_source, RSTART, RLENGTH)
	var_rest = substr(var_source, RSTART + RLENGTH)
	match(var_rest, /^[ \t]*[?:+!]*=/)
	var_operator = substr(var_rest, RSTART, RLENGTH)
	gsub(/[ \t]/, "", var_operator)
	if (!(var_name in variable_definition_file)) { variable_definition_file[var_name] = FILENAME; variable_definition_line[var_name] = line_number }
	if (var_name !~ /^_/) {
		raw = substr(var_source, index(var_source, "=") + 1)
		while (raw ~ /\\[ \t]*$$/) {
			sub(/[ \t]*\\[ \t]*$$/, "", raw)
			if ((getline continuation) <= 0) break
			sub(/^[ \t]+/, "", continuation)
			if (var_verbosity == "") {
				continuation_verbosity = get_line_verbosity(continuation)
				if (continuation_verbosity) var_verbosity = continuation_verbosity
			}
			if (match(continuation, /#~~[ ]*/)) {
				if (var_description == "")
					var_description = trim(strip_verbosity(substr(continuation, RSTART + RLENGTH)))
				continuation = substr(continuation, 1, RSTART - 1)
				sub(/[ \t]*\\[ \t]*$$/, "", continuation)
				if (continuation != "") raw = raw " " continuation
				break
			}
			raw = raw " " continuation
		}
		shown_value = trim(strip_verbosity(raw))
		referenced_internal_name = substr(shown_value, 3, length(shown_value) - 3)
		if (shown_value ~ /^\$$\(_[A-Z0-9_]+\)$$/ && referenced_internal_name in literal_internal_value)
			shown_value = literal_internal_value[referenced_internal_name]
		add("variables", var_name, shown_value, (is_in_ifndef ? "?=" : var_operator), var_description, line_number, var_verbosity)
		scraped_variable[var_name] = 1
	} else {
		internal_value = trim(substr(var_source, index(var_source, "=") + 1))
		if (!index(internal_value, "$$") && !index(internal_value, "\\")) literal_internal_value[var_name] = internal_value
	}
	next
}
{ continued_line = 0 }
/^[^#\t=]*\\$$/ {
	continued_line = FNR
	while ($$0 ~ /\\$$/ && (getline continuation) > 0) { sub(/[[:space:]]*\\$$/, " "); sub(/^[[:space:]]+/, "", continuation); $$0 = $$0 continuation }
}
function get_target_name(name) { return (name in resolved_name) ? resolved_name[name] : ENVIRON["_TARGET_PREFIX"] name }
{ while (match($$0, /\$$\(call _target,[a-zA-Z0-9_.-]+\)/)) $$0 = substr($$0, 1, RSTART - 1) get_target_name(substr($$0, RSTART + 15, RLENGTH - 16)) substr($$0, RSTART + RLENGTH) }
{ gsub(/\$$\(_TARGET_PREFIX\)/, ENVIRON["_TARGET_PREFIX"]) }

/^[A-Za-z0-9_.-]+([ \t]+[A-Za-z0-9_.-]+)*:([^=]|$$)/ {
	split($$0, parts, ":")
	description = get_description()
	count = split(parts[1], names, /[ \t]+/)
	joined_commands = ""
	for (i = 1; i <= count; i += 1) {
		name = names[i]
		if (name == "" || name ~ /^[_\.]/) continue
		if (name in seen_command_names) continue
		seen_command_names[name] = 1
		joined_commands = (joined_commands == "" ? name : joined_commands "/" name)
	}
	if (joined_commands != "") add("commands", joined_commands, description, "", "", continued_line ? continued_line : FNR)
	next
}
END {
	if (ENVIRON["_EDITOR_UNKNOWN"] != "") {
		editor_count = split(ENVIRON["_EDITORS"], editor_names, " ")
		editor_choices = ""
		for (editor_idx = 1; editor_idx <= editor_count; editor_idx += 1)
			editor_choices = editor_choices (editor_idx == 1 ? "" : "|") editor_names[editor_idx]
		printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) %s\n", render_message(sprintf("Unknown editor `%s`.", ENVIRON["_EDITOR_UNKNOWN"]))
		printf "$(call text,[$(_LABEL)],$(COLOR_NOTICE)) %s\n", render_message(sprintf("Run with `EDITOR=<%s>` or `EDITOR=` to disable.", editor_choices))
		exit $(BRNSHKR_CONFIG_ERROR_CODE)
	}
	if (open_scope_name != "") close_empty_scope()
	if (scope_error != "") {
		printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) %s\n", render_message(scope_error)
		if (scope_notice != "") printf "$(call text,[$(_LABEL)],$(COLOR_NOTICE)) %s\n", render_message(scope_notice)
		exit $(BRNSHKR_CONFIG_ERROR_CODE)
	}
	for (scope_path in seen_scope)
		if (!index(scope_path, "/") && (scope_path ~ /^v+$$/ || index(" " ENVIRON["_HELP_WORDS"] " ", " " scope_path " "))) {
			printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) %s\n", render_message(sprintf("Scope `%s` takes a name that help reserves.", scope_path))
			printf "$(call text,[$(_LABEL)],$(COLOR_NOTICE)) %s\n", render_message(sprintf("Rename it; help reads `%s` as a word of its own.", scope_path))
			exit $(BRNSHKR_CONFIG_ERROR_CODE)
		}
	apply_overrides()
	for (scope_path in seen_scope) resolve_scope_level(scope_path)
	for (filter_idx = 1; filter_idx <= scope_filter_count; filter_idx += 1) {
		filter = scope_filter[filter_idx]
		if (filter == "" || index(filter, "/") || (filter in seen_scope)) continue
		match_count = 0
		for (scope_path in seen_scope) {
			if (substr(scope_path, length(scope_path) - length(filter)) != "/" filter) continue
			scope_filter[++match_count == 1 ? filter_idx : ++scope_filter_count] = scope_path
		}
	}
	unknown_filter_count = 0
	for (filter_idx = 1; filter_idx <= scope_filter_count; filter_idx += 1) {
		filter = scope_filter[filter_idx]
		if (filter == "") continue
		filter_verbosity = get_lowest_verbosity(filter)
		if (filter_verbosity >= 0 && filter_verbosity <= verbosity) continue
		filter_display = filter; gsub(/\//, ".", filter_display)
		if (filter_verbosity < 0) unknown_filters[++unknown_filter_count] = filter_display
		else { printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) %s\n", render_message(sprintf("Scope `%s` shows nothing below %s.", filter_display, repeat("v", filter_verbosity))); is_hidden = 1 }
	}
	if (is_hidden && !unknown_filter_count) exit $(BRNSHKR_CONFIG_ERROR_CODE)
	if (unknown_filter_count > 0) {
		unknown_message = ""
		for (filter_idx = 1; filter_idx <= unknown_filter_count; filter_idx += 1) {
			separator = (filter_idx == 1 ? "" : (filter_idx == unknown_filter_count ? " and " : ", "))
			unknown_message = unknown_message separator sprintf("`%s`", unknown_filters[filter_idx])
		}
		unknown_label = (unknown_filter_count == 1 ? "scope" : "scopes")
		printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) %s\n", render_message(sprintf("Unknown %s %s.", unknown_label, unknown_message))
		printf "$(call text,[$(_LABEL)],$(COLOR_NOTICE)) %s\n", render_message("Run `make help list-scopes` or `make help ls` to see available scopes.")
		exit $(BRNSHKR_CONFIG_ERROR_CODE)
	}
	if (ENVIRON["_HAS_LIST_SCOPES_ARGUMENT"]) {
		is_listing_hidden = 1
		listed_count = 0
		for (scope_path in seen_scope) {
			if (scope_path == "main") continue
			if (!does_match_scope(scope_path)) continue
			if (!is_scope_target(scope_path) && scope_filter_count > 0) continue
			if (!has_any_content(scope_path)) continue
			listed_paths[++listed_count] = scope_path
		}
		for (sort_idx = 2; sort_idx <= listed_count; sort_idx += 1) {
			current_path = listed_paths[sort_idx]; compare_idx = sort_idx - 1
			while (compare_idx >= 1 && listed_paths[compare_idx] > current_path) {
				listed_paths[compare_idx + 1] = listed_paths[compare_idx]
				compare_idx -= 1
			}
			listed_paths[compare_idx + 1] = current_path
		}
		for (listed_idx = 1; listed_idx <= listed_count; listed_idx += 1) {
			listed_suffix = (hidden_scopes[listed_paths[listed_idx]] ? " (hidden)" : "")
			gsub(/\//, ".", listed_paths[listed_idx])
			print listed_paths[listed_idx] listed_suffix
		}
		exit 0
	}
	if (ENVIRON["_HAS_ENV_ARGUMENT"] != "") { print_header(); print_environment(); exit 0 }
	if (ENVIRON["_HAS_RESOLVE_ARGUMENT"] != "") add_resolved_variables()
	print_header()
	for (top_idx = 1; top_idx <= top_count; top_idx += 1) {
		top_path = top_order[top_idx]
		column = get_tree_max(top_path, 0, "variables", "name"); if (column > variable_column) variable_column = column
		column = get_tree_max(top_path, 0, "variables", "value"); if (column > description_column) description_column = column
		column = get_tree_max(top_path, 0, "variables", "source"); if (column > source_width) source_width = column
		if (description_column > ENVIRON["VALUE_WIDTH"] + 0) description_column = ENVIRON["VALUE_WIDTH"] + 0
		column = get_tree_max(top_path, 0, "functions", "name"); if (column > function_column) function_column = column
		column = get_tree_max(top_path, 0, "commands", "name"); if (column > command_column) command_column = column
	}
	has_printed = 1
	has_printed += print_section(has_printed, "description", "Description:", 0)
	has_printed += print_section(has_printed, "variables", "Variables:", variable_column)
	has_printed += print_section(has_printed, "functions", "Functions:", function_column)
	has_printed += print_section(has_printed, "commands", "Available commands:", command_column)
}
endef

export EDITOR_URL

$(call _target,cc): _ARG_VALUES = $(_CACHE_NAMES)
$(call _target,cc): _ARG_FILES = $(_CACHES)
$(call _target,changelog): export _CHANGELOG_AWK = $(_CHANGELOG_AWK_SOURCE)
$(call _target,changelog): export _CHANGELOG_MERGE_AWK = $(_CHANGELOG_MERGE_AWK_SOURCE)
$(call _target,changelog): export _CHANGELOG_SECTION_AWK = $(_CHANGELOG_SECTION_AWK_SOURCE)
$(call _target,configs): export _GITATTRIBUTES_PROGRAM = $(_GITATTRIBUTES_PROGRAM_SOURCE)
$(call _target,configs): export _MANAGED_BLOCK_AWK = $(_MANAGED_BLOCK_AWK_SOURCE)
$(call _target,configs): export _NAME_INTERNAL_TAG_PROGRAM = $(_NAME_INTERNAL_TAG_PROGRAM_SOURCE)
$(call _target,configs): _ARG_VALUES = $(_CONFIG_NAMES) tools t local l force f
$(call _target,configs): _ARG_FILES = $(_CONFIG_FILES)
$(call _target,fresh): export _FRESH_ESCAPE_AWK = $(_FRESH_ESCAPE_AWK_SOURCE)
$(call _target,fresh): export _FRESH_KEPT_AWK = $(_FRESH_KEPT_AWK_SOURCE)
$(call _target,help): export _HELP_AWK = $(_HELP_AWK_SOURCE)
$(call _target,help): export _DOTENV_REPORT_AWK = $(_DOTENV_AWK)
$(_GROUPS): export _GROUP_AWK = $(_GROUP_AWK_SOURCE)
