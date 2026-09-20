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
#     - Arguments shaped 'name=value' are taken by make itself, so an option's value is written with a space
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
# All make runs can be prepend with the NO_ANSI flag to disable any ANSI escape sequences in the output:
#   - make NO_ANSI=1 help
#   - NO_ANSI=1 make help
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
#   Printing what each variable expands to instead of its source text:
#     make help resolve
#     make help r
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

_ARG_WORDS = $(patsubst -%,%,$(patsubst --%,%,$(_ARGS)))

_HELP_VERBOSITY_ARGS       = $(strip $(foreach ARG_WORD,$(_ARG_WORDS),$(if $(subst v,,$(ARG_WORD)),,$(ARG_WORD))))
_HELP_VERBOSITY            = $(words $(subst v,v ,$(lastword $(sort $(_HELP_VERBOSITY_ARGS)))))
_HELP_WORDS               := list-scopes ls resolve r
_HELP_RESERVED_ARGS        = $(_HELP_WORDS) $(_HELP_VERBOSITY_ARGS)
_HELP_SCOPES               = $(strip $(filter-out $(_HELP_RESERVED_ARGS),$(_ARG_WORDS)))
_HAS_LIST_SCOPES_ARGUMENT  = $(if $(filter list-scopes ls,$(_ARG_WORDS)),1)
_HAS_RESOLVE_ARGUMENT      = $(if $(filter resolve r,$(_ARG_WORDS)),1)

_DEFAULT_GOAL := $(.DEFAULT_GOAL)

#--- includes

define _include_makefiles_in_directory
$(eval include $(filter-out $(abspath $(MAKEFILE_LIST)),$(wildcard $1/Makefile $1/*.mk)))
endef

_SEARCH_BASES := \
	conf \
	.local \
	.local/conf

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

SHELL  := $(if $(filter-out default,$(origin SHELL)),$(SHELL),/bin/sh)#~~ shell the recipes run in
CP     ?= cp#~~ path to `cp` binary
CAT    ?= cat#~~ path to `cat` binary
RM     := $(if $(filter-out default,$(origin RM)),$(RM),rm)#~~ path to `rm` binary
MKDIR  ?= mkdir#~~ path to `mkdir` binary
MV     ?= mv#~~ path to `mv` binary
CMP    ?= cmp#~~ path to `cmp` binary
CHMOD  ?= chmod#~~ path to `chmod` binary
LN     ?= ln#~~ path to `ln` binary
PRINTF ?= printf#~~ path to `printf` binary
AWK    ?= awk#~~ path to `awk` binary
PHP    ?= $(_RUN) php#~~ command that runs `php`
TAR    ?= tar#~~ path to `tar` binary
GIT    ?= git#~~ path to `git` binary
MKTEMP ?= mktemp#~~ path to `mktemp` binary
SCRIPT ?= script#~~ path to `script` binary

#---v settings

RUN  ?= #~~ command the tools are run through, such as `docker compose exec <service>`
_RUN  = $(if $(wildcard /.dockerenv),,$(RUN))

WORKDIR  ?= $(if $(_RUN),/app,$(CURDIR))#~~ where the tools see the sources, which differs from `CURDIR` in a container
HOST_UID ?= $(or $(shell id -u 2>/dev/null),1000)#vv #~~ user a container writes the bind mount as
HOST_GID ?= $(or $(shell id -g 2>/dev/null),1000)#vv #~~ group a container writes the bind mount as

TARGET_PREFIX  ?= #~~ namespaces every target this file defines, so `brnshkr` gives `brnshkr-lint`
_TARGET_PREFIX  = $(if $(TARGET_PREFIX),$(TARGET_PREFIX)$(if $(filter %- %.,$(TARGET_PREFIX)),,-))

COLLISION_PREFIX  ?= brnshkr#~~ namespace given to a shared target whose name this project already uses
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

VENDOR  ?= $(if $(findstring /,$(_MANIFEST_NAME)),$(firstword $(subst /, ,$(_MANIFEST_NAME))))#~~ vendor name, taken from the manifest when unset
PACKAGE ?= $(or $(lastword $(subst /, ,$(_MANIFEST_NAME))),$(notdir $(CURDIR)))#~~ package name, taken from the manifest or the directory when unset
VERSION ?= 0.0.0-dev#vv #~~ package version
_LABEL   = $(VENDOR)$(if $(and $(VENDOR),$(PACKAGE)),/)$(PACKAGE)

DEBUG          ?= #~~ whether the commands are echoed before they run
unexport DEBUG  # NOTICE: the `debug` package reads this from the environment
DEBUG_PREFIX   ?= $(_ANNOUNCE)$(if $(or $(_IS_DEBUG),$(_IS_TRACE)),,@)#vvv #~~ prefix for a command
_IS_DEBUG       = $(filter-out $(_FALSE),$(DEBUG))

TRACE        ?= #~~ whether the guards are echoed too, which `DEBUG` leaves out
TRACE_PREFIX ?= $(if $(_IS_TRACE),,@)#vvv #~~ prefix for a guard command
_IS_TRACE     = $(filter-out $(_FALSE),$(TRACE))

ANNOUNCEMENT ?= Running `%s`#~~ message a verb prints before each target it runs, `%s` being the target, empty for none
_ANNOUNCE     = $(if $(and $(_IS_ANNOUNCING),$(ANNOUNCEMENT),$(filter-out _% %-group $(_ANNOUNCED),$@)),$(eval _ANNOUNCED += $@)$(info $(shell $(call log,$(ANNOUNCEMENT),$(COLOR_NOTICE),'$@'))))

_FALSE := 0 false off no
_TRUE  := 1 true on yes
_IS_CI  = $(filter-out $(_FALSE),$(CI))

_MAKE_FLAGS  = $(if $(or $(_IS_DEBUG),$(_IS_TRACE)),--no-print-directory,-s) _IS_ANNOUNCING=
_VERB_FLAGS  = $(_MAKE_FLAGS) $(_INHERITED_FLAGS) $(if $(_IS_PARALLEL),--output-sync=recurse) --keep-going _IS_ANNOUNCING=1
_IS_PARALLEL = $(filter-out -j1,$(filter -j%,$(MAKEFLAGS)))

_UNEXPORTED_VARIABLES = COMPOSER DEBUG ESLINT_FLAGS VITEST

_INHERITED_FLAGS = $(foreach VARIABLE,$(_UNEXPORTED_VARIABLES), \
	$(if $(filter environment,$(origin $(VARIABLE))),$(VARIABLE)='$($(VARIABLE))'))

BRNSHKR_CONFIG_ERROR_CODE ?= 69#vvv #~~ the error code for custom errors thrown by this Makefile

#---vv theming

INDENT      ?= 1#~~ indent used in `make help` output
VALUE_WIDTH ?= 48#~~ widest a value column gets in `make help`, past which a value is truncated

NO_ANSI         ?= #~~ whether ANSI escapes are suppressed
_IS_NO_ANSI      = $(filter-out $(_FALSE),$(NO_ANSI))
_NO_ANSI_OPTION  = $(if $(_IS_NO_ANSI),--no-ansi)

THEME         ?= #~~ the theme to use
THEME_BRNSHKR := brnshkr
THEME_SYMFONY := symfony

_THEMES := $(THEME_BRNSHKR) \
	$(THEME_SYMFONY)

_THEME_DEFAULT := $(THEME_BRNSHKR)
_THEME         := $(if $(filter $(THEME),$(_THEMES)),$(THEME),$(_THEME_DEFAULT))

EDITOR_URL      ?= #~~ URL template the entries link to, in `{cwd}`, `{file}`, `{line}` and `{wslDistro}`
EDITOR_VSCODE   := vscode
EDITOR_PHPSTORM := phpstorm

_EDITORS := $(EDITOR_VSCODE) \
	$(EDITOR_PHPSTORM)

_EDITOR_URL_VSCODE     := vscode://file/{file}:{line}
_EDITOR_URL_VSCODE_WSL := vscode://vscode-remote/wsl+{wslDistro}{file}:{line}
_EDITOR_URL_PHPSTORM   := phpstorm://open?file={file}&line={line}
_DETECTED_EDITOR        = $(or $(call _editor_from_env),$(call _editor_from_command))

EDITOR          ?= $(_DETECTED_EDITOR)#~~ the editor to use, empty for no links
_EDITOR          = $(or $(filter $(EDITOR),$(_EDITORS)),$(if $(strip $(EDITOR)),$(if $(findstring environment,$(origin EDITOR)),$(_DETECTED_EDITOR))))
_EDITOR_UNKNOWN  = $(if $(_EDITOR)$(findstring environment,$(origin EDITOR)),,$(strip $(EDITOR)))

_EDITOR_URL = $(subst {wslDistro},$(WSL_DISTRO_NAME),$(subst {cwd},$(CURDIR),$(or \
	$(EDITOR_URL), \
	$(if $(filter $(EDITOR_VSCODE),$(_EDITOR)),$(if $(WSL_DISTRO_NAME),$(_EDITOR_URL_VSCODE_WSL),$(_EDITOR_URL_VSCODE))), \
	$(if $(filter $(EDITOR_PHPSTORM),$(_EDITOR)),$(_EDITOR_URL_PHPSTORM)) \
)))

_INLINE_CODE_URL = $(if $(_IS_NO_ANSI),,$(_EDITOR_URL))

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

#---vv arguments

_TARGET_SOURCES := $(shell $(AWK) -v _PREFIX='$(_TARGET_PREFIX)' -v _CURDIR='$(CURDIR)' \
	'function does_path_exist(path,    line, does_exist) { does_exist = (getline line < path) >= 0; close(path); return does_exist } \
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
	/^ifdef|^ifndef|^ifeq|^ifneq/ { depth += 1; next } \
	/^endif/ { if (skip == depth) skip = 0; depth -= 1; next } \
	skip { next } \
	{ while (match($$0, /\$$\(call _target,[a-zA-Z0-9_.-]+\)/)) \
		$$0 = substr($$0, 1, RSTART - 1) "@" substr($$0, RSTART + 15, RLENGTH - 16) substr($$0, RSTART + RLENGTH) } \
	{ gsub(/\$$\(_TARGET_PREFIX\)/, _PREFIX) } \
	/^@?[a-zA-Z0-9_.-]+([[:space:]]+@?[a-zA-Z0-9_.-]+)*:([^=]|$$)/ { \
		count = split(substr($$0, 1, index($$0, ":") - 1), ns, /[[:space:]]+/); \
		for (i = 1; i <= count; i += 1) \
			if (ns[i] != "" && ns[i] !~ /^[.]/) printf "%s|%s:%d ", ns[i], FILENAME, FNR \
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
#*
#* parameters:
#*   name: string
#*
#* returns: string
#*
define _target
$(or $(firstword $(filter-out $(_CONSUMER_TARGETS),$(_TARGET_PREFIX)$1 $(_COLLISION_PREFIX)$(_TARGET_PREFIX)$1 $(foreach \
	NUMBER,1 2 3 4 5 6 7 8 9,$(_COLLISION_PREFIX)$(_TARGET_PREFIX)$1-$(NUMBER)))),$(_COLLISION_PREFIX)$(_TARGET_PREFIX)$1)
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

TARGET := $(firstword $(filter $(_ALL_TARGETS),$(MAKECMDGOALS)))#~~ the first target of this run
ARG1    = $(word 1,$(ARGS))#~~ the running target's first argument, and so on through `ARG9`
ARG2    = $(word 2,$(ARGS))
ARG3    = $(word 3,$(ARGS))
ARG4    = $(word 4,$(ARGS))
ARG5    = $(word 5,$(ARGS))
ARG6    = $(word 6,$(ARGS))
ARG7    = $(word 7,$(ARGS))
ARG8    = $(word 8,$(ARGS))
ARG9    = $(word 9,$(ARGS))
ARGS    = $(call _shell_quoted,$(_ARGS))#~~ every argument the running target was handed, quoted for the shell

_ARGS       = $(if $(filter command line,$(origin ARGS)),$(ARGS),$(strip $(filter-out $(_ALL_TARGETS),$(call _collect_args,$@,$(MAKECMDGOALS)))))
_FIXER_ARGS = $(call _shell_quoted,$(call _without_dry_run,$(_ARGS)))

#**
#* The goal a name reaches, empty where it names nothing.
#*
#* parameters:
#*   name: string
#*
#* returns: string
#*
define _goal_of
$(or $(filter $1,$(_ALL_TARGETS)),$(filter $(call _target,$1),$(_ALL_TARGETS)))
endef

TARGET_ALIASES ?= h=help \
	c=check \
	t=test \
	f=fix#~~ short names for targets, as `<alias>=<target>` pairs

_TARGET_ALIAS_MAP = $(foreach PAIR,$(TARGET_ALIASES),$(lastword $(subst =, ,$(PAIR)))=$(firstword $(subst =, ,$(PAIR))))

_ALIAS_TARGET = $(strip $(foreach PAIR,$(TARGET_ALIASES), \
	$(if $(filter $(patsubst -%,%,$@),$(firstword $(subst =, ,$(PAIR)))),$(lastword $(subst =, ,$(PAIR))))))

_ALIAS_GOAL = $(call _goal_of,$(_ALIAS_TARGET))

_TAKEN_ALIASES := $(strip $(foreach PAIR,$(TARGET_ALIASES), \
	$(filter $(firstword $(subst =, ,$(PAIR))),$(_ALL_TARGETS))))

_MALFORMED_ALIASES := $(strip $(foreach PAIR,$(TARGET_ALIASES), \
	$(if $(filter 2,$(words $(subst =, ,$(PAIR)))),,$(PAIR))))

_DANGLING_ALIASES := $(strip $(foreach PAIR,$(filter-out $(_MALFORMED_ALIASES),$(TARGET_ALIASES)), \
	$(if $(call _goal_of,$(lastword $(subst =, ,$(PAIR)))),,$(PAIR))))

ifneq ($(_TAKEN_ALIASES),) # taken
$(error [$(_LABEL)] `$(firstword $(_TAKEN_ALIASES))` is a target, so it cannot also be an alias)
endif # /taken

ifneq ($(_MALFORMED_ALIASES),) # malformed
$(error [$(_LABEL)] `$(firstword $(_MALFORMED_ALIASES))` is not an `<alias>=<target>` pair)
endif # /malformed

ifneq ($(_DANGLING_ALIASES),) # dangling
$(error [$(_LABEL)] `$(subst =,` aliases `,$(firstword $(_DANGLING_ALIASES)))`, which is not a target)
endif # /dangling

_IS_JUST_PRINT = $(findstring n,$(firstword $(MAKEFLAGS)))
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
$(shell $(PRINTF) '%s\n' $1 \
	| $(AWK) -v _UNKNOWN='$(or $2,$(_SUGGESTED_FOR))' ' \
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
	{ measured = levenshtein(_UNKNOWN, $$0); if (measured <= (length(_UNKNOWN) > 4 ? 2 : 1) || (length(_UNKNOWN) >= 3 && index($$0, _UNKNOWN))) printf "%d %s\n", measured, $$0 }' \
	| sort -n \
	| $(AWK) 'NR == 1 { nearest = $$1 } $$1 == nearest && NR <= 3 { print $$2 }')
endef

_SUGGESTED_TARGETS = $(if $(filter -%,$(_SUGGESTED_FOR)),,$(or \
	$(call _suggestions,$(_TARGET_GOALS)),$(call _suggestions,$(_STAGE_GOALS))))

_ARG_VALUES       =
_UNKNOWN_VALUES   = $(if $(_ARG_VALUES),$(filter-out $(_ARG_VALUES),$(_ARG_WORDS)))
_MISTYPED_VALUE   = $(firstword $(_UNKNOWN_VALUES))
_SUGGESTED_VALUES = $(call _suggestions,$(_ARG_VALUES),$(_MISTYPED_VALUE))

#--- common

_COMMA := ,
_QUOTE := '
_SPACE := $(subst ,, )

define _NEWLINE


endef

_PACKAGE_CONF_DIRS = $(WORKDIR)/vendor/brnshkr/config/conf \
	$(WORKDIR)/node_modules/@brnshkr/config/conf

_AUTOLOADER          = $(WORKDIR)/vendor/autoload.php
_TRACKED_EXTENSIONS := $(sort $(suffix $(shell $(GIT) ls-files 2>/dev/null)))
_FIXER_NAMES        :=
_ANALYZER_NAMES     :=
_GROUP_NAMES        :=
_TEST_NAMES         :=
_COVERAGE_NAMES     :=
_PACK_NAMES         :=
_PLAIN_CONFIGS       = $(WORKDIR)/.gitignore
_LAYERED_CONFIGS     =
_LINKED_CONFIGS      =

_PLACED_CONFIGS = $(WORKDIR)/.editorconfig|$(_BRNSHKR_CONFIG_DIR)/editorconfig.dist \
	$(WORKDIR)/.vscode/settings.json|$(_BRNSHKR_CONFIG_DIR)/vscode-settings.dist.jsonc \
	$(WORKDIR)/.vscode/extensions.json|$(_BRNSHKR_CONFIG_DIR)/vscode-extensions.dist.json \
	$(WORKDIR)/.vscode/css-custom-data.json|$(_BRNSHKR_CONFIG_DIR)/vscode-css-custom-data.dist.json \
	$(if $(wildcard $(CURDIR)/package.json),$(WORKDIR)/bunfig.toml|$(_BRNSHKR_CONFIG_DIR)/bunfig.dist.toml) \
	$(if $(wildcard $(CURDIR)/composer.json),$(WORKDIR)/.gitattributes|$(_BRNSHKR_CONFIG_DIR)/gitattributes.dist)

$(call _target,help): #~~ show this help, narrowed by any scope named and widened by `v`, `vv` or `vvv`
	$(DEBUG_PREFIX)$(call _make_vars_as_env) \
		_CURDIR="$(abspath $(CURDIR))" \
		_WORKDIR="$(WORKDIR)" \
		_TARGET_PREFIX="$(_TARGET_PREFIX)" \
		_HEADER_LOGO="$(if $(LOGO),$$($(PRINTF) '%b' '$(call _color,$(COLOR_LOGO))$(subst $(_NEWLINE),\n,$(LOGO))$(_ANSI_RESET)__BRNSHKR_LOGO_END__'),)" \
		_HEADER_TITLE="$(if $(or $(PACKAGE),$(VERSION)),$$($(PRINTF) '%b' '$(call text,$(if $(PACKAGE),$(call _to_title_case,$(PACKAGE)))$(if $(and $(PACKAGE),$(VERSION)), )$(if $(VERSION),($(strip $(VERSION)))),$(COLOR_TITLE))'),)" \
		_HEADER_USAGE="$$($(PRINTF) '%b' '$(call text,Usage:,$(COLOR_SECTION_LABEL))\n  $(call text,make,$(COLOR_USAGE_PROGRAM)) $(call text,<command> [--] [arguments],$(COLOR_USAGE_ARGUMENTS))')" \
		_SCOPES="$(subst .,/,$(_HELP_SCOPES))" \
		_HAS_LIST_SCOPES_ARGUMENT="$(_HAS_LIST_SCOPES_ARGUMENT)" \
		_HAS_RESOLVE_ARGUMENT="$(_HAS_RESOLVE_ARGUMENT)" \
		_INLINE_CODE_OPEN="$$($(PRINTF) '%b' '$(call _ansi,$(COLOR_INLINE_CODE))')" \
		_INLINE_CODE_CLOSE="$$($(PRINTF) '%b' '$(_ANSI_RESET)$(call _ansi,$(COLOR_DESCRIPTION))')" \
		_VERBOSITY="$(or $(V),$(_HELP_VERBOSITY))" \
		LC_ALL=C \
		$(AWK) "$$_HELP_AWK" \
		$(MAKEFILE_LIST)

STARTUP_TARGETS ?= #v #~~ this project's own targets, run by `startup` after the dependencies

_STARTUP := _$(_TARGET_PREFIX)install-missing-hooks \
	$(if $(_IS_INSTALLING),,$(if $(wildcard $(CURDIR)/composer.json),_$(_TARGET_PREFIX)composer-install) \
	$(if $(wildcard $(CURDIR)/package.json),_$(_TARGET_PREFIX)bun-install))

$(call _target,startup): $(_STARTUP) #~~ brings a fresh checkout to a workable state #v
	$(DEBUG_PREFIX)$(MAKE) $(_VERB_FLAGS) --no-keep-going $(call _target,configs)
	$(DEBUG_PREFIX)$(if $(STARTUP_TARGETS),$(MAKE) $(_VERB_FLAGS) --no-keep-going $(STARTUP_TARGETS),:)

GIT_HOOKS ?= #v #~~ git hooks this project installs, as `<hook>=<target>` pairs

$(call _target,install-hooks): #~~ installs this project's git hooks, replacing any that exist #v
	$(TRACE_PREFIX)$(GIT) rev-parse --is-inside-work-tree >/dev/null 2>&1 || { \
		$(call log,`install-hooks` needs a git checkout.,$(COLOR_ERROR)) >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}
	$(DEBUG_PREFIX)$(call _install_git_hooks,yes)

_$(_TARGET_PREFIX)install-missing-hooks:
	$(DEBUG_PREFIX)$(call _install_git_hooks)

CONFIG              ?= #v #~~ `local` or `dist` to pin which config every tool reads, rather than the first that is there
ARCHIVE_EXTRA_PATHS ?= #v #~~ paths an archive ships beside the autoload roots, a trailing `/` taking the whole tree

_PINNED_CONFIG = $(filter local dist,$(CONFIG))
_TEMPLATE_DIRS = $(_BRNSHKR_CONFIG_DIR) $(filter-out $(_BRNSHKR_CONFIG_DIR),$(wildcard $(CURDIR)/vendor/brnshkr/config/conf $(CURDIR)/node_modules/@brnshkr/config/conf))

_NAMED_CONFIGS    = $(filter-out local l force f,$(_ARG_WORDS))
_IS_FORCED_CONFIG = $(filter force f,$(_ARG_WORDS))
_CONFIG_NAMES     = $(sort $(foreach CONFIG,$(_CONFIGS) $(_PLACED_CONFIGS) $(_LINKED_CONFIGS),$(call _config_name,$(CONFIG))))

_CONFIGS = $(call _in_project,$(_PLAIN_CONFIGS) $(foreach CONFIG,$(_LAYERED_CONFIGS),$(call _tracked,$(CONFIG))) \
	$(if $(filter local l,$(_ARG_WORDS)),$(subst .dist.,.,$(_LAYERED_CONFIGS))))

$(call _target,configs): #~~ writes the tool configs this project is missing, `local` its private ones too #v
	$(TRACE_PREFIX)$(call _require_known_value,config)
	$(DEBUG_PREFIX)for target in $(call _host_path,$(call _selected_configs,$(_CONFIGS))); do \
		if [ -f "$$target" ]; then \
			$(if $(_IS_FORCED_CONFIG),:,$(call log,`%s` already exists.,$(COLOR_NOTICE),"./$${target#$(CURDIR)/}")); \
			$(if $(_IS_FORCED_CONFIG),:,$(if $(_NAMED_CONFIGS),$(call confirm,Overwrite it?),false)) || continue; \
		fi; \
		name="$${target##*/}"; \
		source="$${target}.example"; \
		case "$$name" in \
			*.dist.*) shipped="$$name"; tracked="";; \
			*.mjs|*.php) shipped=""; tracked="";; \
			?*.?*) shipped=""; tracked="$${name%.*}.dist.$${name##*.}";; \
			*) shipped=""; tracked="$${name}.dist";; \
		esac; \
		for directory in $(_TEMPLATE_DIRS); do \
			[ -f "$$source" ] && break; \
			for candidate in "$$shipped" "$$name.example" "$$tracked"; do \
				[ -n "$$candidate" ] || continue; \
				source="$$directory/$$candidate"; \
				[ -f "$$source" ] && break; \
			done; \
		done; \
		case "$$target" in */*) $(MKDIR) -p "$${target%/*}";; esac; \
		if [ -f "$$source" ]; then \
			$(CP) "$$source" "$$target" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
		else \
			extended="$${target%/*}/$${name%.*}.dist.$${name##*.}"; \
			case "$$name" in \
				*.mjs) $(PRINTF) '%s\n' "export { default } from './$${extended##*/}';" > "$$target" \
					|| exit $(BRNSHKR_CONFIG_ERROR_CODE);; \
				*.php) $(PRINTF) '%s\n' '<?php' '' 'declare(strict_types=1);' '' '/**' ' * @internal' ' */' \
					"\$$config = include __DIR__ . '/$${extended##*/}';" '' 'return $$config;' > "$$target" \
					|| exit $(BRNSHKR_CONFIG_ERROR_CODE);; \
				*) extended=""; continue;; \
			esac; \
		fi; \
		case "$$target" in $(CURDIR)/*.php) $(call _name_internal_tag,$${target#$(CURDIR)/});; esac; \
		$(call log,Created `%s`.,$(COLOR_SUCCESS),"./$${target#$(CURDIR)/}"); \
		if [ -n "$$extended" ]; then \
			$(call log,Built on `%s` — keep that include.,$(COLOR_NOTICE),"./$${extended#$(CURDIR)/}"); \
			extended=""; \
		fi; \
	done
	$(DEBUG_PREFIX)for entry in $(foreach PLACED_CONFIG,$(call _selected_configs,$(_PLACED_CONFIGS)),'$(PLACED_CONFIG)'); do \
		target="$${entry%%|*}"; \
		source="$${entry#*|}"; \
		[ -f "$$source" ] || continue; \
		if [ -f "$$target" ]; then \
			$(if $(_IS_FORCED_CONFIG),:,$(call log,`%s` already exists.,$(COLOR_NOTICE),"./$${target#$(CURDIR)/}")); \
			$(if $(_IS_FORCED_CONFIG),:,$(if $(_NAMED_CONFIGS),$(call confirm,Overwrite it?),false)) || continue; \
		fi; \
		case "$$target" in */*) $(MKDIR) -p "$${target%/*}";; esac; \
		$(CP) "$$source" "$$target" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
		$(call log,Created `%s`.,$(COLOR_SUCCESS),"./$${target#$(CURDIR)/}"); \
	done
	$(DEBUG_PREFIX)$(if $(wildcard $(CURDIR)/composer.json),[ ! -f $(WORKDIR)/.gitattributes ] || { \
		updated_path=$$($(PHP) -r "$$_GITATTRIBUTES_PROGRAM" $(WORKDIR)/.gitattributes $(WORKDIR)/composer.json '$(strip $(ARCHIVE_EXTRA_PATHS))') || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
		[ -z "$$updated_path" ] || $(call log,Updated `%s`.,$(COLOR_SUCCESS),'$(call _named_path,$(WORKDIR)/.gitattributes)'); \
	},:)
	$(DEBUG_PREFIX)for entry in $(foreach LINKED_CONFIG,$(call _selected_configs,$(_LINKED_CONFIGS)),'$(LINKED_CONFIG)'); do \
		link="$${entry%%|*}"; \
		source="$${entry#*|}"; \
		relative="$${source#$(CURDIR)/}"; \
		if [ -e "$$link" ]; then \
			$(if $(_IS_FORCED_CONFIG),:,$(call log,`%s` already exists.,$(COLOR_NOTICE),"./$${link#$(CURDIR)/}")); \
			$(if $(_IS_FORCED_CONFIG),:,$(if $(_NAMED_CONFIGS),$(call confirm,Overwrite it?),false)) || continue; \
		fi; \
		[ -f "$$source" ] || continue; \
		if $(LN) -sf "$$relative" "$$link" 2>/dev/null; then \
			$(call log,Linked `%s` to `%s`.,$(COLOR_SUCCESS),"./$${link#$(CURDIR)/}" "./$$relative"); \
		else \
			$(PRINTF) '%s\n' '{' "  \"extends\": \"./$$relative\"" '}' > "$$link" \
				|| exit $(BRNSHKR_CONFIG_ERROR_CODE); \
			$(call log,Created `%s`.,$(COLOR_SUCCESS),"./$${link#$(CURDIR)/}"); \
		fi; \
	done

CACHE_DIR   ?= $(CURDIR)/.cache#v #~~ directory the tools keep their caches in
_CACHES      = $(if $(CACHE_DIR),$(notdir $(wildcard $(CACHE_DIR)/*)))

$(call _target,cc): #~~ removes the caches named, or all of them when given none #v
	$(TRACE_PREFIX)$(call _require_known_value,cache)
	$(DEBUG_PREFIX)if [ -z "$(_CACHES)" ]; then \
		$(call log,No caches to remove.,$(COLOR_NOTICE)); \
	elif [ -n "$(strip $(ARGS))" ]; then \
		for name in $(sort $(ARGS)); do \
			$(RM) -rf "$(CACHE_DIR)/$$name" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
			$(call log,Removed `%s/%s`.,$(COLOR_SUCCESS),'$(call _named_path,$(CACHE_DIR))' "$$name"); \
		done; \
	else \
		$(call log,This removes every cache in `%s`.,$(COLOR_WARNING),'$(call _named_path,$(CACHE_DIR))'); \
		$(call log,Name %s to remove only some.,$(COLOR_NOTICE),'$(call _join_as_quoted_list,$(_CACHES),disjunction)',no-autolink); \
		if $(call confirm,Continue?); then \
			$(RM) -rf "$(CACHE_DIR)" || exit $(BRNSHKR_CONFIG_ERROR_CODE); \
			$(call log,Removed `%s`.,$(COLOR_SUCCESS),'$(call _named_path,$(CACHE_DIR))'); \
		else \
			$(call log,Nothing removed.,$(COLOR_NOTICE)); \
		fi; \
	fi

_GIT_CLEAN_BASE_FLAGS  = $(if $(filter --all -a,$(_ARGS)),,-e /.local -e .env*.local)
_GIT_CLEAN_DRY_FLAGS   = -xdffn $(_GIT_CLEAN_BASE_FLAGS)
_GIT_CLEAN_FORCE_FLAGS = -xdff $(_GIT_CLEAN_BASE_FLAGS)

$(call _target,fresh): #~~ removes every untracked file and runs `startup`, `--all` the private ones too #vv
	$(TRACE_PREFIX)$(GIT) rev-parse --is-inside-work-tree >/dev/null 2>&1 || { \
		$(call log,`fresh` needs a git checkout.,$(COLOR_ERROR)) >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}
	$(DEBUG_PREFIX)LC_ALL=C $(GIT) clean $(_GIT_CLEAN_DRY_FLAGS) | while read -r path; do \
		$(call log,Would remove `./%s`.,$(COLOR_NOTICE),"$${path#Would remove }"); \
	done
	$(DEBUG_PREFIX)$(if $(_IS_JUST_PRINT)$(call _is_dry_run,$(_ARGS)),:,$(if $(filter --force -f,$(_ARGS)),:,$(call confirm,Remove them and reinstall?) || { \
			$(call log,Nothing removed.,$(COLOR_NOTICE)); \
			exit 0; \
		}); \
		$(GIT) clean $(_GIT_CLEAN_FORCE_FLAGS))
	$(DEBUG_PREFIX)$(if $(_IS_JUST_PRINT)$(call _is_dry_run,$(_ARGS)),:,[ -n "$$($(GIT) clean $(_GIT_CLEAN_DRY_FLAGS))" ] || { \
		{ [ -f $(_BRNSHKR_CONFIG_MAKEFILE) ] \
			|| { $(if $(_COMPOSER_INSTALL),$(_COMPOSER_INSTALL) &&) $(if $(_BUN_INSTALL),$(_BUN_INSTALL) &&) is_installing=_IS_INSTALLING=1; }; } \
			&& $(MAKE) $(_VERB_FLAGS) --no-keep-going $$is_installing $(call _target,startup); \
	})

$(call _target,fresh-dry-run): #~~ lists what `fresh` would remove #vv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,fresh) -- --dry-run $(ARGS)

#---vvv semver

#---- constants

SEMVER_NUMBER            ?= (0|[1-9][0-9]*)#~~ a numeric identifier, which carries no leading zero
SEMVER_PRERELEASE_PART   ?= (0|[1-9][0-9]*|[0-9]*[A-Za-z-][0-9A-Za-z-]*)#~~ one prerelease identifier, which carries no leading zero when it is numeric
SEMVER_PRERELEASE        ?= $(SEMVER_PRERELEASE_PART)(\.$(SEMVER_PRERELEASE_PART))*#~~ the prerelease grammar semver itself permits
SEMVER_PRERELEASE_STRICT ?= (alpha|beta|rc)(\.(0|[1-9][0-9]*))?#~~ the narrower one: what npm and Composer can both express, spelled npm's way
SEMVER_BUILD             ?= [0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*#~~ build metadata, which neither registry accepts in a published version
SEMVER_REGEX             ?= $(SEMVER_NUMBER)\.$(SEMVER_NUMBER)\.$(SEMVER_NUMBER)(-$(SEMVER_PRERELEASE))?(\+$(SEMVER_BUILD))?#~~ the semantic version grammar, as a POSIX extended regular expression

#---v dotenv

DOTENV             ?= 1#~~ base path of the environment files, `1` for `.env`, or `0` to load none
DOTENV_ENV_KEY     ?= APP_ENV#~~ variable naming the current environment
DOTENV_DEFAULT_ENV ?= dev#~~ environment assumed when the variable is unset
DOTENV_TEST_ENVS   ?= test#~~ environments that skip the `.local` file, as a test run must be reproducible
_DOTENV_BASE       := $(if $(filter-out $(_FALSE),$(DOTENV)),$(CURDIR)/$(or $(filter-out $(_TRUE),$(DOTENV)),.env))
_DOTENV_SEPARATOR  := @@brnshkr-dotenv-newline@@
_DOTENV_STAGES     := $(filter-out local %.local %.example,$(patsubst .env.%,%,$(notdir $(wildcard $(CURDIR)/.env.*))))

_DOTENV_RESET = $(if $(_DOTENV_FILE_KEYS),env $(foreach KEY,$(sort $(_DOTENV_FILE_KEYS)),-u $(KEY)) )

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
	$$(TRACE_PREFIX)$$(if $$(call _goal_of,$$*),:,$$(call log,Unknown command `%s`.,$$(COLOR_ERROR),'$$@'); \
		$$(call _suggest,$1-))
	$$(DEBUG_PREFIX)$$(if $$(call _goal_of,$$*),$$(_DOTENV_RESET)$$(MAKE) $$(_MAKE_FLAGS) $$(_INHERITED_FLAGS) $(DOTENV_ENV_KEY)=$1 $$* \
		$$(if $$(_UNKNOWN_ARGS),-- $$(_UNKNOWN_ARGS)),:)
endef

$(foreach STAGE,$(_DOTENV_STAGES),$(eval $(call _stage_rule,$(STAGE))))

#---v helpers

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
#* The value it is given when the path is there, so a list only claims what this project has.
#*
#* parameters:
#*   value: list<string>
#*   path: string
#*
#* returns: list<string>
#*
define _when_installed
$(if $(wildcard $(CURDIR)/$2),$1)
endef

ifneq ($(wildcard $(CURDIR)/composer.json),) # php

#--- php

PHP_EXTENSIONS ?= php#vv #~~ extensions the PHP tools read

#--- composer

COMPOSER              ?= $(_RUN) composer#v #~~ command that runs `composer`
unexport COMPOSER      # NOTICE: Composer reads this as the manifest to open
COMPOSER_FLAGS        ?= $(if $(_IS_CI),--no-interaction --no-progress) $(if $(_IS_DEBUG),-vvv)#v #~~ additional flags passed to `composer`
_COMPOSER_PACKAGE_DIR := $(CURDIR)/.local/$(if $(VENDOR),$(patsubst @%,%,$(VENDOR))/)$(PACKAGE)
_COMPOSER_INSTALL      = $(COMPOSER) install $(COMPOSER_FLAGS)

_PACK_NAMES += composer-pack

$(call _target,composer): #~~ runs `composer`
	$(DEBUG_PREFIX)$(COMPOSER) $(COMPOSER_FLAGS) $(ARGS)

$(call _target,composer-list): #~~ lists the files the composer package would ship #vv
	$(DEBUG_PREFIX)$(COMPOSER) archive --quiet --format=tar --dir=$(WORKDIR) --file=package \
		&& $(TAR) -tf package.tar \
		&& $(RM) -f package.tar

$(call _target,composer-pack): #~~ packs the composer package into `./.local/<VENDOR>/<PACKAGE>` #vvv
	$(DEBUG_PREFIX)$(RM) -rf $(_COMPOSER_PACKAGE_DIR) \
		&& $(MKDIR) -p $(_COMPOSER_PACKAGE_DIR) \
		&& $(COMPOSER) archive --quiet --format=tar --dir=$(WORKDIR) --file=package \
		&& $(TAR) -xf package.tar -C $(_COMPOSER_PACKAGE_DIR) \
		&& $(RM) -f package.tar

$(call _target,composer-print): #~~ prints the resolved `composer` configuration #vv
	$(DEBUG_PREFIX)$(COMPOSER) config --list $(COMPOSER_FLAGS) $(ARGS)

_$(_TARGET_PREFIX)composer-install:
	$(DEBUG_PREFIX)$(_COMPOSER_INSTALL)

#--- phpunit

ifneq ($(wildcard $(CURDIR)/vendor/bin/pest $(CURDIR)/vendor/bin/phpunit),) # phpunit

PEST                          ?= $(_RUN) $(_PEST_BINARY)#vvv #~~ command that runs `pest`
PHP_UNIT                      ?= $(if $(wildcard $(CURDIR)/vendor/bin/pest),$(PEST),$(_RUN) $(WORKDIR)/vendor/bin/phpunit)#v #~~ command that runs the tests, which is Pest when it is installed
PHP_UNIT_CONFIG               ?= $(call _config,$(WORKDIR)/conf/phpunit.xml $(WORKDIR)/conf/phpunit.dist.xml,php)#v #~~ path to the test runner config
PHP_UNIT_FLAGS                ?= $(if $(_IS_DEBUG),--debug) $(if $(_IS_NO_ANSI),--colors=never)#v #~~ additional flags passed to the test runner
PHP_UNIT_SNAPSHOT_FLAGS       ?= $(if $(_IS_PEST),--update-snapshots) --do-not-fail-on-incomplete#v #~~ flags that make the runner rewrite its snapshots
PHP_UNIT_COVERAGE_DIR         ?= $(WORKDIR)/.cache/phpunit.cache/coverage#v #~~ directory the HTML coverage report is written to
PHP_UNIT_COVERAGE_FILE        ?= $(PHP_UNIT_COVERAGE_DIR)/coverage.txt#vv #~~ text report the minimum is read from, where the runner has no minimum of its own
PHP_UNIT_MIN_COVERAGE         ?= 100#v #~~ percentage every metric must reach, `0` to collect coverage without a floor
PHP_UNIT_MIN_COVERAGE_CLASSES ?= $(PHP_UNIT_MIN_COVERAGE)#vv #~~ percentage the class metric must reach, which Pest cannot read
PHP_UNIT_MIN_COVERAGE_METHODS ?= $(PHP_UNIT_MIN_COVERAGE)#vv #~~ percentage the method metric must reach, which Pest cannot read
PHP_UNIT_MIN_COVERAGE_LINES   ?= $(PHP_UNIT_MIN_COVERAGE)#vv #~~ percentage the line metric must reach
PHP_UNIT_COVERAGE_FLAGS       ?= $(if $(_IS_PEST),--coverage $(if $(_PHP_UNIT_FLOOR),--min=$(_PHP_UNIT_FLOOR)),$(if $(_PHP_UNIT_FLOORS),--coverage-text=$(PHP_UNIT_COVERAGE_FILE))) --coverage-html $(PHP_UNIT_COVERAGE_DIR)#v #~~ additional flags passed when collecting coverage

_PEST_BINARY     := $(WORKDIR)/vendor/bin/pest
_IS_PEST          = $(findstring pest,$(PHP_UNIT))
_PHP_UNIT_UPDATE  = $(if $(wildcard $(CURDIR)/vendor/bin/update-snapshots),$(subst /vendor/bin/phpunit,/vendor/bin/update-snapshots,$(PHP_UNIT)),$(PHP_UNIT))
_PHP_UNIT_FLOOR   = $(filter-out 0,$(strip $(PHP_UNIT_MIN_COVERAGE_LINES)))

_PHP_UNIT_FLOORS = Classes=$(strip $(PHP_UNIT_MIN_COVERAGE_CLASSES)) \
	Methods=$(strip $(PHP_UNIT_MIN_COVERAGE_METHODS)) \
	Lines=$(strip $(PHP_UNIT_MIN_COVERAGE_LINES))

_TEST_NAMES      += $(if $(wildcard $(CURDIR)/tests),$(call _when_tracked,phpunit,$(PHP_EXTENSIONS)))
_COVERAGE_NAMES  += $(if $(wildcard $(CURDIR)/tests),$(call _when_tracked,phpunit,$(PHP_EXTENSIONS)))
_LAYERED_CONFIGS += $(if $(wildcard $(CURDIR)/tests),$(call _when_tracked,$(call _layered,$(PHP_UNIT_CONFIG),xml),$(PHP_EXTENSIONS)))

_$(_TARGET_PREFIX)assert-pest:
	$(TRACE_PREFIX)test -f '$(call _host_path,$(_PEST_BINARY))' || { \
		$(call log,`pest` is not installed.,$(COLOR_ERROR)) >&2; \
		$(call log,Require `pestphp/pest` or run `make $(call _target,phpunit)` to use the runner this project has.,$(COLOR_NOTICE)) >&2; \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}

$(call _target,pest): _$(_TARGET_PREFIX)assert-pest #~~ runs the PHP tests through `pest` in particular #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,phpunit) PHP_UNIT='$(PEST)' -- $(ARGS)

$(call _target,pest-coverage): _$(_TARGET_PREFIX)assert-pest #~~ runs them through `pest` and reports its coverage #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,phpunit-coverage) PHP_UNIT='$(PEST)' -- $(ARGS)

$(call _target,pest-debug): _$(_TARGET_PREFIX)assert-pest #~~ runs them through `pest` with debugging information #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,phpunit-debug) PHP_UNIT='$(PEST)' -- $(ARGS)

$(call _target,pest-list): _$(_TARGET_PREFIX)assert-pest #~~ lists the tests `pest` collects #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,phpunit-list) PHP_UNIT='$(PEST)' -- $(ARGS)

$(call _target,pest-update): _$(_TARGET_PREFIX)assert-pest #~~ runs them through `pest` and updates their snapshots #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,phpunit-update) PHP_UNIT='$(PEST)' -- $(ARGS)

$(call _target,phpunit): #~~ runs the PHP tests
	$(TRACE_PREFIX)$(call _require_file,$(PHP_UNIT_CONFIG),PHP_UNIT_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(PHP_UNIT) --configuration $(PHP_UNIT_CONFIG) $(PHP_UNIT_FLAGS) $(ARGS))

$(call _target,phpunit-coverage): #~~ runs the PHP tests and reports how much of the source they reach #v
	$(TRACE_PREFIX)$(call _require_file,$(PHP_UNIT_CONFIG),PHP_UNIT_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(PHP_UNIT) --configuration $(PHP_UNIT_CONFIG) $(PHP_UNIT_FLAGS) $(PHP_UNIT_COVERAGE_FLAGS) $(ARGS))
	$(TRACE_PREFIX)$(if $(or $(_IS_PEST),$(if $(_PHP_UNIT_FLOORS),,1)),:,$(foreach FLOOR,$(_PHP_UNIT_FLOORS),$(call _require_coverage,$(call _host_path,$(PHP_UNIT_COVERAGE_FILE)),$(firstword $(subst =, ,$(FLOOR))),$(lastword $(subst =, ,$(FLOOR))),PHP_UNIT_COVERAGE_FILE)$(_NEWLINE)))

$(call _target,phpunit-debug): #~~ runs the PHP tests with debugging information instead of progress #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,phpunit) -- --debug $(ARGS)

$(call _target,phpunit-list): #~~ lists the tests the runner collects #vv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_UNIT_CONFIG),PHP_UNIT_CONFIG)
	$(DEBUG_PREFIX)$(PHP_UNIT) --configuration $(PHP_UNIT_CONFIG) $(ARGS) --list-tests

$(call _target,phpunit-update): #~~ runs the PHP tests and updates their snapshots #v
	$(TRACE_PREFIX)$(call _require_file,$(PHP_UNIT_CONFIG),PHP_UNIT_CONFIG)
	$(DEBUG_PREFIX)$(_PHP_UNIT_UPDATE) --configuration $(PHP_UNIT_CONFIG) $(PHP_UNIT_FLAGS) $(PHP_UNIT_SNAPSHOT_FLAGS) $(ARGS)

endif # /phpunit

#--- phpstan

ifneq ($(wildcard $(CURDIR)/vendor/bin/phpstan),) # phpstan

PHP_STAN        ?= $(_RUN) $(WORKDIR)/vendor/bin/phpstan#v #~~ path to `phpstan` binary
PHP_STAN_CONFIG ?= $(call _config,$(WORKDIR)/conf/phpstan.php $(WORKDIR)/conf/phpstan.dist.php,php)#v #~~ path to `phpstan` config
PHP_STAN_FLAGS  ?= --memory-limit=-1 $(_NO_ANSI_OPTION) $(if $(_IS_DEBUG),-vvv,-vv)#v #~~ additional flags passed to `phpstan`

_ANALYZER_NAMES  += $(call _when_tracked,phpstan,$(PHP_EXTENSIONS))
_GROUP_NAMES     += $(call _when_tracked,phpstan,$(PHP_EXTENSIONS))
_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(PHP_STAN_CONFIG),php),$(PHP_EXTENSIONS))

$(call _target,phpstan): #~~ runs `phpstan analyze`
	$(TRACE_PREFIX)$(call _require_file,$(PHP_STAN_CONFIG),PHP_STAN_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(PHP_STAN) analyze --configuration $(PHP_STAN_CONFIG) $(PHP_STAN_FLAGS) $(ARGS))

$(call _target,phpstan-debug): #~~ runs `phpstan analyze` in debug mode #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,phpstan) -- --debug $(ARGS)

$(call _target,phpstan-group): #~~ counts the `phpstan` findings by identifier #v
	$(TRACE_PREFIX)$(call _require_file,$(PHP_STAN_CONFIG),PHP_STAN_CONFIG)
	$(DEBUG_PREFIX)$(call _group,$(PHP_STAN) analyze --configuration $(PHP_STAN_CONFIG) $(PHP_STAN_FLAGS) --no-progress --error-format=json $(ARGS),identifier,message)

$(call _target,phpstan-list): #~~ lists all files that are processed by `phpstan` #vv
	$(DEBUG_PREFIX)$(call _capture,$(MAKE) $(_MAKE_FLAGS) $(call _target,phpstan-raw) -- --debug 2>&1) \
		| $(AWK) 'match($$0, /.+\.php$$/) { print substr($$0, RSTART, RLENGTH) }'

$(call _target,phpstan-print): #~~ prints the resolved `phpstan` configuration #vv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_STAN_CONFIG),PHP_STAN_CONFIG)
	$(DEBUG_PREFIX)$(PHP_STAN) dump-parameters --configuration $(PHP_STAN_CONFIG) $(_NO_ANSI_OPTION) $(ARGS)

$(call _target,phpstan-raw): #~~ runs `phpstan analyze` with raw output #vvv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_STAN_CONFIG),PHP_STAN_CONFIG)
	$(DEBUG_PREFIX)$(PHP_STAN) analyze --configuration $(PHP_STAN_CONFIG) $(PHP_STAN_FLAGS) --error-format=raw $(ARGS)

endif # /phpstan

#--- php-cs-fixer

ifneq ($(wildcard $(CURDIR)/vendor/bin/php-cs-fixer),) # php-cs-fixer

PHP_CS_FIXER        ?= $(_RUN) $(WORKDIR)/vendor/bin/php-cs-fixer#v #~~ path to `php-cs-fixer` binary
PHP_CS_FIXER_CONFIG ?= $(call _config,$(WORKDIR)/conf/php-cs-fixer.php $(WORKDIR)/conf/php-cs-fixer.dist.php,php)#v #~~ path to `php-cs-fixer` config
PHP_CS_FIXER_FLAGS  ?= --show-progress=dots $(_NO_ANSI_OPTION) $(if $(_IS_DEBUG),-vvv,-v)#v #~~ additional flags passed to `php-cs-fixer`

_FIXER_NAMES     += $(call _when_tracked,php-cs-fixer,$(PHP_EXTENSIONS))
_GROUP_NAMES     += $(call _when_tracked,php-cs-fixer,$(PHP_EXTENSIONS))
_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(PHP_CS_FIXER_CONFIG),php),$(PHP_EXTENSIONS))

$(call _target,php-cs-fixer): #~~ runs `php-cs-fixer fix`
	$(TRACE_PREFIX)$(call _require_file,$(PHP_CS_FIXER_CONFIG),PHP_CS_FIXER_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(PHP_CS_FIXER) fix --config $(PHP_CS_FIXER_CONFIG) $(PHP_CS_FIXER_FLAGS) $(ARGS))

$(call _target,php-cs-fixer-dry-run): #~~ runs `php-cs-fixer fix` in dry run mode #v
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,php-cs-fixer) -- --dry-run $(ARGS)

$(call _target,php-cs-fixer-group): #~~ counts the `php-cs-fixer` findings by fixer #v
	$(TRACE_PREFIX)$(call _require_file,$(PHP_CS_FIXER_CONFIG),PHP_CS_FIXER_CONFIG)
	$(DEBUG_PREFIX)$(call _group,$(PHP_CS_FIXER) fix --config $(PHP_CS_FIXER_CONFIG) $(PHP_CS_FIXER_FLAGS) $(ARGS) --dry-run --format=json,appliedFixers)

$(call _target,php-cs-fixer-list): #~~ lists all files that are processed by `php-cs-fixer` #vv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_CS_FIXER_CONFIG),PHP_CS_FIXER_CONFIG)
	$(DEBUG_PREFIX)$(call _capture,$(PHP_CS_FIXER) list-files --config $(PHP_CS_FIXER_CONFIG) $(_NO_ANSI_OPTION) 2>&1) \
		| $(AWK) '/^'\''\.\/.*\.php'\''$$/ { sub(/^'\''\.\//, ""); sub(/'\''$$/, ""); print }'

$(call _target,php-cs-fixer-print): #~~ prints the `php-cs-fixer` rules in use #vv
	$(TRACE_PREFIX)$(call _require_file,$(PHP_CS_FIXER_CONFIG),PHP_CS_FIXER_CONFIG)
	$(DEBUG_PREFIX)$(PHP_CS_FIXER) describe --config $(PHP_CS_FIXER_CONFIG) $(_NO_ANSI_OPTION) -n @ $(ARGS)

endif # /php-cs-fixer

#--- rector

ifneq ($(wildcard $(CURDIR)/vendor/bin/rector),) # rector

RECTOR        ?= $(_RUN) $(WORKDIR)/vendor/bin/rector#v #~~ path to `rector` binary
RECTOR_CONFIG ?= $(call _config,$(WORKDIR)/conf/rector.php $(WORKDIR)/conf/rector.dist.php,php)#v #~~ path to `rector` config
RECTOR_FLAGS  ?= --memory-limit=-1 $(_NO_ANSI_OPTION)#v #~~ additional flags passed to `rector`

_FIXER_NAMES     += $(call _when_tracked,rector,$(PHP_EXTENSIONS))
_GROUP_NAMES     += $(call _when_tracked,rector,$(PHP_EXTENSIONS))
_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(RECTOR_CONFIG),php),$(PHP_EXTENSIONS))

$(call _target,rector): #~~ runs `rector process`
	$(TRACE_PREFIX)$(call _require_file,$(RECTOR_CONFIG),RECTOR_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(RECTOR) process --config $(RECTOR_CONFIG) $(RECTOR_FLAGS) $(ARGS))

$(call _target,rector-debug): #~~ runs `rector process` in debug mode #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,rector) -- --debug $(ARGS)

$(call _target,rector-dry-run): #~~ runs `rector process` in dry run mode #v
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,rector) -- --dry-run $(ARGS)

$(call _target,rector-group): #~~ counts the `rector` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(RECTOR_CONFIG),RECTOR_CONFIG)
	$(DEBUG_PREFIX)$(call _group,$(RECTOR) process --config $(RECTOR_CONFIG) $(RECTOR_FLAGS) $(ARGS) --dry-run --output-format=json,applied_rectors)

$(call _target,rector-list): #~~ lists all files that are processed by `rector` #vv
	$(TRACE_PREFIX)$(call _require_file,$(RECTOR_CONFIG),RECTOR_CONFIG)
	$(DEBUG_PREFIX)$(call _capture,$(RECTOR) process --config $(RECTOR_CONFIG) --debug --dry-run) | $(AWK) '/\[file\]/ { sub(".*\\[file\\] $(WORKDIR)/", ""); if (/\.php$$/) print }'

$(call _target,rector-print): #~~ prints the `rector` rules in use #vv
	$(TRACE_PREFIX)$(call _require_file,$(RECTOR_CONFIG),RECTOR_CONFIG)
	$(DEBUG_PREFIX)$(RECTOR) list-rules --config $(RECTOR_CONFIG) $(_NO_ANSI_OPTION) $(ARGS)

endif # /rector

#--- twig-cs-fixer

ifneq ($(wildcard $(CURDIR)/vendor/bin/twig-cs-fixer),) # twig-cs-fixer

TWIG_CS_FIXER        ?= $(_RUN) $(WORKDIR)/vendor/bin/twig-cs-fixer#v #~~ path to `twig-cs-fixer` binary
TWIG_CS_FIXER_CONFIG ?= $(call _config,$(WORKDIR)/conf/twig-cs-fixer.php $(WORKDIR)/conf/twig-cs-fixer.dist.php,php)#v #~~ path to `twig-cs-fixer` config
TWIG_CS_FIXER_FLAGS  ?= -v $(_NO_ANSI_OPTION)#v #~~ additional flags passed to `twig-cs-fixer`
TWIG_CS_FIXER_EXTENSIONS ?= twig#vv #~~ extensions `twig-cs-fixer` reads

_FIXER_NAMES     += $(call _when_tracked,twig-cs-fixer,$(TWIG_CS_FIXER_EXTENSIONS))
_GROUP_NAMES     += $(call _when_tracked,twig-cs-fixer,$(TWIG_CS_FIXER_EXTENSIONS))
_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(TWIG_CS_FIXER_CONFIG),php),$(TWIG_CS_FIXER_EXTENSIONS))

$(call _target,twig-cs-fixer): #~~ runs `twig-cs-fixer fix`, or lints only when `--dry-run` is passed
	$(TRACE_PREFIX)$(call _require_file,$(TWIG_CS_FIXER_CONFIG),TWIG_CS_FIXER_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(TWIG_CS_FIXER) lint $(if $(call _is_dry_run,$(_ARGS)),,--fix) --config $(TWIG_CS_FIXER_CONFIG) $(TWIG_CS_FIXER_FLAGS) $(_FIXER_ARGS))

$(call _target,twig-cs-fixer-debug): #~~ runs `twig-cs-fixer` in debug mode #vvv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,twig-cs-fixer) -- --debug $(ARGS)

$(call _target,twig-cs-fixer-dry-run): #~~ runs `twig-cs-fixer` in dry run mode #v
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,twig-cs-fixer) -- --dry-run $(ARGS)

$(call _target,twig-cs-fixer-group): #~~ counts the `twig-cs-fixer` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(TWIG_CS_FIXER_CONFIG),TWIG_CS_FIXER_CONFIG)
	$(DEBUG_PREFIX)$(call _group,$(TWIG_CS_FIXER) lint --config $(TWIG_CS_FIXER_CONFIG) --report gitlab $(ARGS),check_name,description)

endif # /twig-cs-fixer

endif # /php

ifneq ($(wildcard $(CURDIR)/package.json),) # js

#--- bun

BUN       ?= $(_RUN) bun#v #~~ command that runs `bun`
BUN_FLAGS ?= --bun#v #~~ flags passed to `bun` before the command

_BUN_PACKAGE_DIR := $(CURDIR)/.local/$(if $(VENDOR),$(VENDOR)/)$(PACKAGE)
_BUN_INSTALL      = $(BUN) install

_PACK_NAMES += bun-pack

$(call _target,bun): #~~ runs `bun`
	$(DEBUG_PREFIX)$(BUN) $(ARGS)

$(call _target,bun-list): #~~ lists the files the bun package would ship #vv
	$(DEBUG_PREFIX)$(call _capture,NO_COLOR=1 $(BUN) pm pack --dry-run) | $(AWK) '/^packed /{ print $$3 }'

$(call _target,bun-pack): #~~ packs the bun package into `./.local/<VENDOR>/<PACKAGE>` #vvv
	$(DEBUG_PREFIX)$(RM) -rf $(_BUN_PACKAGE_DIR) \
		&& $(MKDIR) -p $(_BUN_PACKAGE_DIR) \
		&& $(BUN) pm pack --quiet --filename package.tgz >/dev/null \
		&& $(TAR) -xzf package.tgz -C $(_BUN_PACKAGE_DIR) --strip-components 1 \
		&& $(RM) -f package.tgz

ifneq ($(wildcard $(CURDIR)/node_modules/.bin/node-modules-inspector),) # bun-inspect

NODE_MODULES_INSPECTOR       ?= $(BUN) $(BUN_FLAGS) x node-modules-inspector#v #~~ command that runs `node-modules-inspector`
NODE_MODULES_INSPECTOR_FLAGS ?= #v #~~ additional flags passed to `node-modules-inspector`

$(call _target,bun-inspect): #~~ runs `node-modules-inspector` #v
	$(DEBUG_PREFIX)$(NODE_MODULES_INSPECTOR) $(ARGS) $(NODE_MODULES_INSPECTOR_FLAGS)

endif # /bun-inspect

_$(_TARGET_PREFIX)bun-install:
	$(DEBUG_PREFIX)$(_BUN_INSTALL)

#--- commitlint

ifneq ($(wildcard $(CURDIR)/node_modules/.bin/commitlint),) # commitlint

COMMITLINT        ?= $(BUN) $(BUN_FLAGS) x commitlint#v #~~ command that runs `commitlint`
COMMITLINT_CONFIG ?= $(call _config,$(WORKDIR)/conf/commitlint.mjs $(WORKDIR)/conf/commitlint.dist.mjs,js)#v #~~ path to `commitlint` config
COMMITLINT_SOURCE ?= $(if $(wildcard $(CURDIR)/.git/COMMIT_EDITMSG),--edit,--last)#vv #~~ which message `commitlint` reads
COMMITLINT_FLAGS  ?= $(if $(_IS_DEBUG),--verbose) $(if $(_IS_NO_ANSI),--color false)#v #~~ additional flags passed to `commitlint`

GIT_HOOKS += commit-msg=$(call _target,commitlint)

_ANALYZER_NAMES  += commitlint
_LAYERED_CONFIGS += $(call _layered,$(COMMITLINT_CONFIG),mjs)

$(call _target,commitlint): _$(_TARGET_PREFIX)install-missing-hooks #~~ lints a commit message
	$(TRACE_PREFIX)$(call _require_file,$(COMMITLINT_CONFIG),COMMITLINT_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(COMMITLINT) --config $(COMMITLINT_CONFIG) $(COMMITLINT_FLAGS) $(if $(ARGS),$(ARGS),$(COMMITLINT_SOURCE)))

$(call _target,commitlint-print): #~~ prints the resolved `commitlint` configuration #vv
	$(TRACE_PREFIX)$(call _require_file,$(COMMITLINT_CONFIG),COMMITLINT_CONFIG)
	$(DEBUG_PREFIX)$(COMMITLINT) --config $(COMMITLINT_CONFIG) $(COMMITLINT_FLAGS) --print-config $(ARGS)

endif # /commitlint

#--- eslint

ifneq ($(wildcard $(CURDIR)/node_modules/.bin/eslint),) # eslint

ESLINT                ?= $(BUN) $(BUN_FLAGS) x eslint#v #~~ command that runs `eslint`
ESLINT_CONFIG         ?= $(call _config,$(WORKDIR)/conf/eslint.ts $(WORKDIR)/conf/eslint.mjs $(WORKDIR)/conf/eslint.dist.mjs,js)#v #~~ path to `eslint` config
ESLINT_CACHE          ?= $(WORKDIR)/.cache/eslint.cache.json#v #~~ path to the `eslint` cache
ESLINT_FLAGS          ?= --max-warnings 0 $(if $(_IS_DEBUG),--debug) $(if $(_IS_NO_ANSI),--no-color)#v #~~ additional flags passed to `eslint`
unexport ESLINT_FLAGS  # NOTICE: ESLint reads this as its feature flags

_ESLINT_OUTPUT := $(WORKDIR)/.cache/eslint.output

_ESLINT_EXTENSIONS := js cjs mjs jsx \
	$(call _when_installed,ts cts mts tsx,node_modules/typescript-eslint) \
	$(call _when_installed,svelte,node_modules/eslint-plugin-svelte) \
	$(call _when_installed,json,node_modules/@eslint/json) \
	$(call _when_installed,jsonc json5,node_modules/eslint-plugin-jsonc) \
	$(call _when_installed,yml yaml,node_modules/eslint-plugin-yml) \
	$(call _when_installed,toml,node_modules/eslint-plugin-toml) \
	$(call _when_installed,css,node_modules/@eslint/css) \
	$(call _when_installed,md,node_modules/@eslint/markdown)

ESLINT_EXTENSIONS ?= $(_ESLINT_EXTENSIONS)#vv #~~ extensions `eslint` lints, which follows the modules its config enables

_FIXER_NAMES     += $(call _when_tracked,eslint,$(ESLINT_EXTENSIONS))
_GROUP_NAMES     += $(call _when_tracked,eslint,$(ESLINT_EXTENSIONS))
_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(ESLINT_CONFIG),mjs),$(ESLINT_EXTENSIONS))

# NOTICE: ESLint writes through `--output-file` since Bun truncates its piped output
$(call _target,eslint): #~~ runs `eslint`, and writes its fixes unless `--dry-run` is passed
	$(TRACE_PREFIX)$(call _require_file,$(ESLINT_CONFIG),ESLINT_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(RM) -f '$(call _host_path,$(_ESLINT_OUTPUT))'; \
		$(ESLINT) --config $(ESLINT_CONFIG) --cache --cache-location $(ESLINT_CACHE) $(ESLINT_FLAGS) --output-file $(_ESLINT_OUTPUT) $(_FIXER_ARGS) $(if $(call _is_dry_run,$(_ARGS)),,--fix); \
		status=$$?; \
		[ ! -f '$(call _host_path,$(_ESLINT_OUTPUT))' ] || $(CAT) '$(call _host_path,$(_ESLINT_OUTPUT))'; \
		$(RM) -f '$(call _host_path,$(_ESLINT_OUTPUT))'; \
		exit $$status)

$(call _target,eslint-dry-run): #~~ runs `eslint` without writing fixes #v
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,eslint) -- --dry-run $(ARGS)

$(call _target,eslint-group): #~~ counts the `eslint` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(ESLINT_CONFIG),ESLINT_CONFIG)
	$(DEBUG_PREFIX)$(call _group,$(ESLINT) --config $(ESLINT_CONFIG) --cache --cache-location $(ESLINT_CACHE) $(ESLINT_FLAGS) --format json --output-file /dev/stdout $(ARGS),ruleId,message)

$(call _target,eslint-print): #~~ prints the resolved `eslint` config for a file #vv
	$(TRACE_PREFIX)$(call _require_file,$(ESLINT_CONFIG),ESLINT_CONFIG)
	$(DEBUG_PREFIX)$(ESLINT) --config $(ESLINT_CONFIG) --print-config $(ARGS)

ifneq ($(wildcard $(CURDIR)/node_modules/.bin/eslint-config-inspector),) # eslint-inspect

# NOTICE: runs on node since bun has no `module.registerHooks` (https://github.com/oven-sh/bun/issues/27369)
ESLINT_CONFIG_INSPECTOR       ?= $(BUN) x eslint-config-inspector#v #~~ command that runs `eslint-config-inspector`
ESLINT_CONFIG_INSPECTOR_FLAGS ?= #v #~~ additional flags passed to `eslint-config-inspector`

$(call _target,eslint-inspect): #~~ runs `eslint-config-inspector` #v
	$(TRACE_PREFIX)$(call _require_file,$(ESLINT_CONFIG),ESLINT_CONFIG)
	$(DEBUG_PREFIX)$(ESLINT_CONFIG_INSPECTOR) --config $(ESLINT_CONFIG) $(ARGS) $(ESLINT_CONFIG_INSPECTOR_FLAGS)

$(call _target,eslint-inspect-stats): #~~ runs `eslint-config-inspector` and times every rule with a full lint on startup #vv
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,eslint-inspect) -- $(ARGS) --stats

endif # /eslint-inspect

endif # /eslint

#--- markdownlint

ifneq ($(wildcard $(CURDIR)/node_modules/.bin/markdownlint-cli2),) # markdownlint

MARKDOWNLINT            ?= $(BUN) $(BUN_FLAGS) x markdownlint-cli2#v #~~ command that runs `markdownlint-cli2`
MARKDOWNLINT_CONFIG     ?= $(call _config,$(WORKDIR)/conf/markdownlint.mjs $(WORKDIR)/conf/markdownlint.dist.mjs,js)#v #~~ path to `markdownlint` config
MARKDOWNLINT_GLOB       ?= $(call _brace_glob,$(MARKDOWNLINT_EXTENSIONS))#vv #~~ files `markdownlint` looks at
MARKDOWNLINT_FLAGS      ?= $(if $(_IS_NO_ANSI),--no-color)#v #~~ additional flags passed to `markdownlint-cli2`
MARKDOWNLINT_EXTENSIONS ?= md markdown#vv #~~ extensions `markdownlint` reads

_MARKDOWNLINT_VALUE_FLAGS := --config \
	--configPointer

_FIXER_NAMES     += $(call _when_tracked,markdownlint,$(MARKDOWNLINT_EXTENSIONS))
_GROUP_NAMES     += $(call _when_tracked,markdownlint,$(MARKDOWNLINT_EXTENSIONS))
_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(MARKDOWNLINT_CONFIG),mjs),$(MARKDOWNLINT_EXTENSIONS))

$(call _target,markdownlint): #~~ runs `markdownlint-cli2`, and writes its fixes unless `--dry-run` is passed
	$(TRACE_PREFIX)$(call _require_file,$(MARKDOWNLINT_CONFIG),MARKDOWNLINT_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(MARKDOWNLINT) --config $(MARKDOWNLINT_CONFIG) $(MARKDOWNLINT_FLAGS) \
		$(if $(call _is_dry_run,$(_ARGS)),,--fix) \
		$(if $(call _has_positional_arg,$(call _without_dry_run,$(_ARGS)),$(_MARKDOWNLINT_VALUE_FLAGS)), \
			$(_FIXER_ARGS), \
			$(_FIXER_ARGS) '$(MARKDOWNLINT_GLOB)'))

$(call _target,markdownlint-dry-run): #~~ runs `markdownlint-cli2` without writing fixes #v
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,markdownlint) -- --dry-run $(ARGS)

$(call _target,markdownlint-group): #~~ counts the `markdownlint` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(MARKDOWNLINT_CONFIG),MARKDOWNLINT_CONFIG)
	$(DEBUG_PREFIX)$(call _group,$(MARKDOWNLINT) --config $(MARKDOWNLINT_CONFIG) $(MARKDOWNLINT_FLAGS) $(if $(call _has_positional_arg,$(ARGS),$(_MARKDOWNLINT_VALUE_FLAGS)),$(ARGS),$(ARGS) '$(MARKDOWNLINT_GLOB)'))

endif # /markdownlint

#--- stylelint

ifneq ($(wildcard $(CURDIR)/node_modules/.bin/stylelint),) # stylelint

STYLELINT        ?= $(BUN) $(BUN_FLAGS) x stylelint#v #~~ command that runs `stylelint`
STYLELINT_CONFIG ?= $(call _config,$(WORKDIR)/conf/stylelint.mjs $(WORKDIR)/conf/stylelint.dist.mjs,js)#v #~~ path to `stylelint` config
STYLELINT_GLOB   ?= $(call _brace_glob,$(STYLELINT_EXTENSIONS))#vv #~~ files `stylelint` looks at
STYLELINT_FLAGS  ?= --allow-empty-input --max-warnings 0 $(if $(_IS_NO_ANSI),--no-color)#v #~~ additional flags passed to `stylelint`

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

_STYLELINT_EXTENSIONS := css \
	$(call _when_installed,less,node_modules/stylelint-config-standard-less) \
	$(call _when_installed,scss,node_modules/stylelint-config-standard-scss) \
	$(call _when_installed,ejs html svelte svg vue astro xml,node_modules/stylelint-config-html)

STYLELINT_EXTENSIONS ?= $(_STYLELINT_EXTENSIONS)#vv #~~ extensions `stylelint` can parse, which follows the syntaxes its config pulls in

_FIXER_NAMES     += $(call _when_tracked,stylelint,$(STYLELINT_EXTENSIONS))
_GROUP_NAMES     += $(call _when_tracked,stylelint,$(STYLELINT_EXTENSIONS))
_LAYERED_CONFIGS += $(call _when_tracked,$(call _layered,$(STYLELINT_CONFIG),mjs),$(STYLELINT_EXTENSIONS))

$(call _target,stylelint): #~~ runs `stylelint`, and writes its fixes unless `--dry-run` is passed
	$(TRACE_PREFIX)$(call _require_file,$(STYLELINT_CONFIG),STYLELINT_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(STYLELINT) --config $(STYLELINT_CONFIG) $(STYLELINT_FLAGS) \
		$(if $(call _is_dry_run,$(_ARGS)),,--fix) \
		$(if $(call _has_positional_arg,$(call _without_dry_run,$(_ARGS)),$(_STYLELINT_VALUE_FLAGS)), \
			$(_FIXER_ARGS), \
			$(_FIXER_ARGS) '$(STYLELINT_GLOB)'))

$(call _target,stylelint-dry-run): #~~ runs `stylelint` without writing fixes #v
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) $(call _target,stylelint) -- --dry-run $(ARGS)

$(call _target,stylelint-group): #~~ counts the `stylelint` findings by rule #v
	$(TRACE_PREFIX)$(call _require_file,$(STYLELINT_CONFIG),STYLELINT_CONFIG)
	$(DEBUG_PREFIX)$(call _group,$(STYLELINT) --config $(STYLELINT_CONFIG) $(STYLELINT_FLAGS) --formatter json $(if $(call _has_positional_arg,$(ARGS),$(_STYLELINT_VALUE_FLAGS)),$(ARGS),$(ARGS) '$(STYLELINT_GLOB)'),rule,text)

$(call _target,stylelint-print): #~~ prints the resolved `stylelint` config for a file #vv
	$(TRACE_PREFIX)$(call _require_file,$(STYLELINT_CONFIG),STYLELINT_CONFIG)
	$(DEBUG_PREFIX)$(STYLELINT) --config $(STYLELINT_CONFIG) --print-config $(ARGS)

endif # /stylelint

#--- typescript

ifneq ($(wildcard $(CURDIR)/node_modules/.bin/tsc),) # typescript

TYPESCRIPT        ?= $(BUN) $(BUN_FLAGS) x tsc#v #~~ command that runs `tsc`
TYPESCRIPT_CONFIG ?= $(WORKDIR)/tsconfig.json#v #~~ path to the `tsc` project
TYPESCRIPT_FLAGS  ?= --noEmit#v #~~ additional flags passed to `tsc`
TYPESCRIPT_EXTENSIONS ?= ts tsx cts mts#vv #~~ extensions `tsc` type checks

_TYPESCRIPT_PROJECT := $(WORKDIR)/conf/tsconfig.json

_ANALYZER_NAMES  += $(call _when_tracked,typescript,$(TYPESCRIPT_EXTENSIONS))
_GROUP_NAMES     += $(call _when_tracked,typescript,$(TYPESCRIPT_EXTENSIONS))
_PLAIN_CONFIGS   += $(call _when_tracked,$(_TYPESCRIPT_PROJECT),$(TYPESCRIPT_EXTENSIONS))
_LINKED_CONFIGS  += $(call _when_tracked,$(call _host_path,$(TYPESCRIPT_CONFIG))|$(call _host_path,$(_TYPESCRIPT_PROJECT)),$(TYPESCRIPT_EXTENSIONS))

$(call _target,typescript): #~~ runs the `tsc` type check
	$(TRACE_PREFIX)$(call _require_file,$(TYPESCRIPT_CONFIG),TYPESCRIPT_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(TYPESCRIPT) --project $(TYPESCRIPT_CONFIG) $(TYPESCRIPT_FLAGS) $(ARGS))

$(call _target,typescript-group): #~~ counts the `tsc` errors by code #v
	$(TRACE_PREFIX)$(call _require_file,$(TYPESCRIPT_CONFIG),TYPESCRIPT_CONFIG)
	$(DEBUG_PREFIX)$(call _group,$(TYPESCRIPT) --project $(TYPESCRIPT_CONFIG) $(TYPESCRIPT_FLAGS) --pretty false $(ARGS))

$(call _target,typescript-list): #~~ lists the files the `tsc` project includes #vv
	$(TRACE_PREFIX)$(call _require_file,$(TYPESCRIPT_CONFIG),TYPESCRIPT_CONFIG)
	$(DEBUG_PREFIX)$(TYPESCRIPT) --project $(TYPESCRIPT_CONFIG) --listFilesOnly $(ARGS)

$(call _target,typescript-print): #~~ prints the resolved `tsc` project #vv
	$(TRACE_PREFIX)$(call _require_file,$(TYPESCRIPT_CONFIG),TYPESCRIPT_CONFIG)
	$(DEBUG_PREFIX)$(TYPESCRIPT) --project $(TYPESCRIPT_CONFIG) --showConfig $(ARGS)

endif # /typescript

#--- vitest

ifneq ($(wildcard $(CURDIR)/node_modules/.bin/vitest),) # vitest

VITEST            ?= $(BUN) $(BUN_FLAGS) x vitest#v #~~ command that runs `vitest`
unexport VITEST    # NOTICE: Vitest sets this to mark its own runs
VITEST_CONFIG     ?= $(call _config,$(WORKDIR)/conf/vitest.ts $(WORKDIR)/conf/vitest.mjs $(WORKDIR)/conf/vitest.dist.mjs,js)#v #~~ path to `vitest` config
VITEST_EXTENSIONS ?= ts tsx js jsx mts cts mjs cjs#vv #~~ extensions `vitest` runs tests from
VITEST_FLAGS      ?= --run --passWithNoTests $(if $(_IS_DEBUG),--reporter=verbose)#v #~~ additional flags passed to `vitest`

VITEST_COVERAGE_DIR            ?= $(WORKDIR)/.cache/vitest.cache/coverage#v #~~ directory the HTML coverage report is written to
VITEST_MIN_COVERAGE            ?= 100#v #~~ percentage every metric must reach, `0` to collect coverage without a floor
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

_VITEST_COVERAGE_PROVIDER := $(firstword $(wildcard \
	$(CURDIR)/node_modules/@vitest/coverage-v8 \
	$(CURDIR)/node_modules/@vitest/coverage-istanbul \
))

_TEST_NAMES      += $(if $(wildcard $(CURDIR)/tests),$(call _when_tracked,vitest,$(VITEST_EXTENSIONS)))
_COVERAGE_NAMES  += $(if $(_VITEST_COVERAGE_PROVIDER),$(if $(wildcard $(CURDIR)/tests),$(call _when_tracked,vitest,$(VITEST_EXTENSIONS))))
_LAYERED_CONFIGS += $(if $(wildcard $(CURDIR)/tests),$(call _when_tracked,$(call _layered,$(VITEST_CONFIG),mjs),$(VITEST_EXTENSIONS)))

$(call _target,vitest): #~~ runs `vitest`
	$(TRACE_PREFIX)$(call _require_file,$(VITEST_CONFIG),VITEST_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(VITEST) --config $(VITEST_CONFIG) $(VITEST_FLAGS) $(ARGS))

ifneq ($(_VITEST_COVERAGE_PROVIDER),) # vitest-coverage

$(call _target,vitest-coverage): #~~ runs `vitest` and reports how much of the source it reaches #v
	$(TRACE_PREFIX)$(call _require_file,$(VITEST_CONFIG),VITEST_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(VITEST) --config $(VITEST_CONFIG) $(VITEST_FLAGS) $(VITEST_COVERAGE_FLAGS) $(ARGS))

endif # /vitest-coverage

$(call _target,vitest-list): #~~ lists the tests `vitest` collects #vv
	$(TRACE_PREFIX)$(call _require_file,$(VITEST_CONFIG),VITEST_CONFIG)
	$(DEBUG_PREFIX)$(VITEST) list --config $(VITEST_CONFIG) $(ARGS)

$(call _target,vitest-update): #~~ runs `vitest` and updates its snapshots #v
	$(TRACE_PREFIX)$(call _require_file,$(VITEST_CONFIG),VITEST_CONFIG)
	$(DEBUG_PREFIX)$(call _spinner,$(VITEST) --config $(VITEST_CONFIG) $(VITEST_FLAGS) $(ARGS) --update)

endif # /vitest

endif # /js

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
	js=☕_JS \
	php=🐘_PHP \
	rule=🧩_Rules \
	make=🔨_Make \
	composer=📦_Composer \
	ai=🤖_AI \
	vscode=💻_Editor \
	readme=📚_Documentation \
	docs=📚_Documentation \
	ci=⚙️_CI \
	release=🔖_Release \
	general=🧰_General#vv #~~ headings per scope root, as `<root>=<heading>` pairs, `_` a space

CHANGELOG_NAMES ?= commands=Commands \
	commitlint=commitlint \
	composer=Composer \
	editor-url=EditorUrl \
	file-finder=FileFinder \
	eslint=ESLint \
	jsdoc=JSDoc \
	markdownlint=markdownlint \
	php-cs-fixer=PHP-CS-Fixer \
	phpat=PHPat \
	phpstan=PHPStan \
	phpunit=PHPUnit \
	rector=Rector \
	stylelint=Stylelint \
	twig-cs-fixer=Twig-CS-Fixer \
	typescript=TypeScript \
	mate=Mate \
	shared=Shared \
	string=String \
	test=Test \
	vitest=Vitest \
	yaml=YAML#vv #~~ readable scope names, as `<scope>=<name>` pairs, `_` a space

_CHANGELOG_LAST_TAG = $(shell $(GIT) describe --tags --abbrev=0 2>/dev/null)
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

$(call _target,changelog): #~~ prints the changelog, or writes it to `CHANGELOG_DIR` #v
	$(DEBUG_PREFIX)$(call _capture,$(GIT) log --no-merges --pretty=format:'%H%x1f%D%x1f%s%x1f%b%x1e' $(_CHANGELOG_RANGE)) \
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
		| { $(if $(_IS_CHANGELOG_WRITE),$(call _changelog_write),$(if $(_IS_CHANGELOG_NOTES),$(call _changelog_notes),$(CAT))); }

#--- common

_FIXERS    := $(foreach FIXER_NAME,$(_FIXER_NAMES),$(call _target,$(FIXER_NAME)))
_DRY_RUNS  := $(foreach FIXER_NAME,$(_FIXER_NAMES),$(call _target,$(FIXER_NAME)-dry-run))
_ANALYZERS := $(foreach ANALYZER_NAME,$(_ANALYZER_NAMES),$(call _target,$(ANALYZER_NAME)))
_TESTS     := $(foreach TEST_NAME,$(_TEST_NAMES),$(call _target,$(TEST_NAME)))
_UPDATES   := $(foreach TEST_NAME,$(_TEST_NAMES),$(call _target,$(TEST_NAME)-update))
_COVERAGE  := $(foreach COVERAGE_NAME,$(_COVERAGE_NAMES),$(call _target,$(COVERAGE_NAME)-coverage))
_GROUPS    := $(foreach GROUP_NAME,$(_GROUP_NAMES),$(call _target,$(GROUP_NAME)-group))
_PACKS     := $(foreach PACK_NAME,$(_PACK_NAMES),$(call _target,$(PACK_NAME)))

CHECK_TARGETS ?= #v #~~ this project's own targets, run by `check`

$(call _target,check): #~~ runs the fixers without writing, and every analyzer
	$(TRACE_PREFIX)$(if $(_DRY_RUNS)$(_ANALYZERS)$(CHECK_TARGETS),:,$(call log,No tool to run.,$(COLOR_NOTICE)))
	$(DEBUG_PREFIX)$(if $(_DRY_RUNS)$(_ANALYZERS)$(CHECK_TARGETS),$(MAKE) $(_VERB_FLAGS) $(_DRY_RUNS) $(_ANALYZERS) $(CHECK_TARGETS),:)

_CI_TARGETS := $(call _target,check) \
	$(call _target,test)

CI_TARGETS ?= $(_CI_TARGETS)#vv #~~ targets `ci` runs in order

$(call _target,ci): #~~ runs `CI_TARGETS` one after the other #vv
	$(DEBUG_PREFIX)$(foreach TARGET,$(CI_TARGETS), \
		$(MAKE) $(_VERB_FLAGS) $(TARGET) && \
	):

$(call _target,coverage): #~~ runs the tests and fails below the minimum coverage #vv
	$(TRACE_PREFIX)$(if $(_COVERAGE),:,$(call log,No test to run.,$(COLOR_NOTICE)))
	$(DEBUG_PREFIX)$(if $(_COVERAGE),$(MAKE) $(_VERB_FLAGS) $(_COVERAGE),:)

_FIX_ORDER := rector+php-cs-fixer \
	twig-cs-fixer \
	eslint+stylelint+markdownlint

_FIX_CHAINS := $(strip $(foreach CHAIN,$(_FIX_ORDER) $(filter-out $(subst +, ,$(_FIX_ORDER)),$(_FIXER_NAMES)), \
	$(if $(filter $(_FIXER_NAMES),$(subst +, ,$(CHAIN))),_$(_TARGET_PREFIX)fix-$(CHAIN)) \
))

FIX_TARGETS ?= #v #~~ this project's own targets, run by `fix`

$(call _target,fix): #~~ runs every fixer this project has, writing its fixes
	$(TRACE_PREFIX)$(if $(_FIX_CHAINS)$(FIX_TARGETS),:,$(call log,No fixer to run.,$(COLOR_NOTICE)))
	$(DEBUG_PREFIX)$(if $(_FIX_CHAINS)$(FIX_TARGETS),$(MAKE) $(_VERB_FLAGS) $(_FIX_CHAINS) $(FIX_TARGETS),:)

_$(_TARGET_PREFIX)fix-%:
	$(DEBUG_PREFIX)$(foreach NAME,$(filter $(_FIXER_NAMES),$(subst +, ,$*)), \
		$(MAKE) $(_VERB_FLAGS) $(call _target,$(NAME)) && \
	):

GROUP_TARGETS ?= #v #~~ this project's own targets, run by `group`

$(call _target,group): #~~ counts every tool's findings by identifier #v
	$(TRACE_PREFIX)$(if $(_GROUPS)$(GROUP_TARGETS),:,$(call log,No tool to run.,$(COLOR_NOTICE)))
	$(DEBUG_PREFIX)$(if $(_GROUPS)$(GROUP_TARGETS),$(MAKE) $(_VERB_FLAGS) $(_GROUPS) $(GROUP_TARGETS),:)

$(call _target,pack): $(_PACKS) #~~ packs every stack's package into `./.local` #vvv
	$(TRACE_PREFIX)$(if $(_PACKS),:,$(call log,No package to pack.,$(COLOR_NOTICE)))

TEST_TARGETS ?= #v #~~ this project's own targets, run by `test`

$(call _target,test): #~~ runs the tests of every stack this project has
	$(TRACE_PREFIX)$(if $(_TESTS)$(TEST_TARGETS),:,$(call log,No test to run.,$(COLOR_NOTICE)))
	$(DEBUG_PREFIX)$(if $(_TESTS)$(TEST_TARGETS),$(MAKE) $(_VERB_FLAGS) $(_TESTS) $(TEST_TARGETS),:)

$(call _target,test-update): #~~ runs those tests and updates their snapshots #v
	$(TRACE_PREFIX)$(if $(_UPDATES),:,$(call log,No test to run.,$(COLOR_NOTICE)))
	$(DEBUG_PREFIX)$(if $(_UPDATES),$(MAKE) $(_VERB_FLAGS) $(_UPDATES),:)

FIXTURE_TARGETS ?= #v #~~ this project's own targets, run by `fixtures` to build what its tests read

$(call _target,fixtures): #~~ builds the fixtures this project's tests read #v
	$(TRACE_PREFIX)$(if $(FIXTURE_TARGETS),:,$(call log,No fixture to build.,$(COLOR_NOTICE)))
	$(DEBUG_PREFIX)$(if $(FIXTURE_TARGETS),$(MAKE) $(_VERB_FLAGS) --no-keep-going $(FIXTURE_TARGETS),:)

.DEFAULT:
ifeq ($(TARGET),) # unknown
	$(DEBUG_PREFIX)$(if $(_ALIAS_GOAL),exec $(MAKE) $(_MAKE_FLAGS) $(_ALIAS_GOAL) \
		$(if $(_UNKNOWN_ARGS),-- $(_UNKNOWN_ARGS)),:)
	$(DEBUG_PREFIX)$(if $(_ALIAS_TARGET),$(if $(_ALIAS_GOAL),:,{ \
		$(call log,`%s` aliases `%s`$(_COMMA) which is not a target.,$(COLOR_ERROR),'$@' '$(_ALIAS_TARGET)'); \
		exit $(BRNSHKR_CONFIG_ERROR_CODE); \
	}),:)
	$(DEBUG_PREFIX)$(if $(_ALIAS_TARGET),:,$(if $(_IS_FIRST_GOAL),$(call log,Unknown command `%s`.,$(COLOR_ERROR),'$@'),:))
	$(DEBUG_PREFIX)$(if $(_ALIAS_TARGET),:,$(if $(_IS_FIRST_GOAL),$(if $(_IS_JUST_PRINT),:,$(call _suggest)),:))
else
	$(DEBUG_PREFIX):
endif # /unknown

_PREFLIGHT := _$(_TARGET_PREFIX)assert-no-collisions

$(filter-out $(_PREFLIGHT),$(_ALL_TARGETS)): | $(_PREFLIGHT)

_$(_TARGET_PREFIX)assert-no-collisions:
ifneq ($(_DUPLICATE_TARGETS),) # duplicates
	$(TRACE_PREFIX)$(foreach NAME,$(_DUPLICATE_TARGETS),$(call log,`%s` is defined in %s.,$(COLOR_ERROR),'$(NAME)' '$(call _join_as_quoted_list,$(call _named_path,$(call _definitions_of,$(NAME))))')$(_NEWLINE))
	$(TRACE_PREFIX)$(call log,Rename $(if $(word 2,$(_DUPLICATE_TARGETS)),them,it)$(_COMMA) since this project defines the same name twice.,$(COLOR_NOTICE))
	$(TRACE_PREFIX)exit $(BRNSHKR_CONFIG_ERROR_CODE)
endif # /duplicates
ifneq ($(and $(strip $(TARGET_PREFIX)),$(_COLLIDING_TARGETS)),) # collisions
	$(TRACE_PREFIX)$(foreach NAME,$(_COLLIDING_TARGETS),$(call log,`%s` is defined in %s.,$(COLOR_ERROR),'$(_TARGET_PREFIX)$(NAME)' '$(call _join_as_quoted_list,$(call _named_path,$(call _definitions_of,$(_TARGET_PREFIX)$(NAME))))')$(_NEWLINE))
	$(TRACE_PREFIX)$(call log,Rename $(if $(word 2,$(_COLLIDING_TARGETS)),them,it)$(_COMMA) since `TARGET_PREFIX` is already the namespace this project chose.,$(COLOR_NOTICE))
	$(TRACE_PREFIX)exit $(BRNSHKR_CONFIG_ERROR_CODE)
endif # /collisions

PHONY ?= 1#v #~~ targets marked `.PHONY`, `shared` for the shared ones only or `0` for none

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

_ANSI_RESET       = $(if $(_IS_NO_ANSI),,\033[0m)
_ANSI_CLEAR_LINE  = $(if $(_IS_NO_ANSI),,\033[2K)
_ANSI_HIDE_CURSOR = $(if $(_IS_NO_ANSI),,\033[?25l)
_ANSI_SHOW_CURSOR = $(if $(_IS_NO_ANSI),,\033[?25h)

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
#* The path on this machine of a file the tools see under `WORKDIR`.
#*
#* parameters:
#*   path: string
#*
#* returns: string
#*
define _host_path
$(patsubst $(WORKDIR)/%,$(CURDIR)/%,$1)
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
$(strip $(shell $(PRINTF) '%s\n' $1 | $(AWK) -v _VALUE_FLAGS=' $(strip $2) ' '\
	{ \
		if (skip) { skip = 0; next } \
		if (index(_VALUE_FLAGS, " " $$0 " ") > 0) { skip = 1; next } \
		if ($$0 != "" && substr($$0, 1, 1) != "-") { print 1; exit } \
	}'))
endef

#**
#* The name a config answers to, which is its file name without a leading dot, a `.dist` or a suffix.
#*
#* parameters:
#*   path: string
#*
#* returns: string
#*
define _config_name
$(firstword $(subst ., ,$(patsubst .%,%,$(notdir $(lastword $(subst |, ,$1))))))
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
$(if $(_NAMED_CONFIGS),$(foreach CONFIG,$1,$(if $(filter $(call _config_name,$(CONFIG)),$(_NAMED_CONFIGS)),$(CONFIG))),$1)
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
$(foreach CONFIG,$1,$(if $(filter $(addsuffix /%,$(_PACKAGE_CONF_DIRS)),$(CONFIG)),$(WORKDIR)/conf/$(notdir $(CONFIG)),$(CONFIG)))
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
if [ -f "$(call _host_path,$(_AUTOLOADER))" ]; then \
	$(PHP) -r "$$_NAME_INTERNAL_TAG_PROGRAM" "$(_AUTOLOADER)" "$(WORKDIR)/$1" \
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
$(foreach BASE,$(call _reverse,$(_SEARCH_BASES)),$(if $1,$(WORKDIR)/$(BASE)/$1) $(WORKDIR)/$(BASE))
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
	$(call log,Did you mean `%s`?,$(COLOR_NOTICE),'$1$(_SUGGESTED_TARGETS)'); \
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
	$(call log,No $1 named `%s`.,$(COLOR_ERROR),'$(_MISTYPED_VALUE)'); \
	$(if $(_SUGGESTED_VALUES),$(if $(word 2,$(_SUGGESTED_VALUES)), \
		$(call log,Did you mean %s?,$(COLOR_NOTICE),'$(call _join_as_quoted_list,$(_SUGGESTED_VALUES),disjunction)'), \
		$(call log,Did you mean `%s`?,$(COLOR_NOTICE),'$(_SUGGESTED_VALUES)')), \
		$(call log,Try %s.,$(COLOR_NOTICE),'$(call _join_as_quoted_list,$(_ARG_VALUES),disjunction)',no-autolink)); \
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
directory="$$($(GIT) rev-parse --git-path hooks)"; \
$(MKDIR) -p "$$directory"; \
for pair in $(GIT_HOOKS); do \
	hook="$${pair%%=*}"; \
	$(if $(filter yes,$1),,[ ! -f "$$directory/$$hook" ] || continue;) \
	$(PRINTF) '#!/bin/sh\nexec %s %s\n' '$(MAKE)' "$${pair#*=}" > "$$directory/$$hook"; \
	$(CHMOD) +x "$$directory/$$hook"; \
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
test -f '$(call _host_path,$1)' || { \
	$(call log,`%s` is missing$(_COMMA) set `%s` or create it.,$(COLOR_ERROR),'$(call _named_path,$1)' '$2') >&2; \
	exit $(BRNSHKR_CONFIG_ERROR_CODE); \
}
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
done; done & spinner=$$!
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
label=$(if $(_HAS_GROUP_LABEL),$$($(call log,$(ANNOUNCEMENT),$(COLOR_NOTICE),'$@'))); \
has_spinner=; $(if $(or $(_IS_GROUP_PARALLEL),$(_IS_NO_ANSI)),,[ -t 1 ] && has_spinner=1;) \
$(if $(_IS_GROUP_PARALLEL),,[ -z "$$label" ] || $(PRINTF) '%s\n' "$$label"; \
	[ -z "$$has_spinner" ] || { \
		$(PRINTF) '$(_ANSI_HIDE_CURSOR)'; \
		trap 'kill $$spinner 2>/dev/null; $(_CLEAR_SPINNER); exit $(_INTERRUPT_EXIT_CODE)' $(_SPINNER_SIGNALS); \
		$(call __spinner,$(if $(_HAS_GROUP_LABEL),  )); \
	};) \
output=$$($1 2>&1); status=$$?; \
report=$$($(PRINTF) '%s\n' "$$output" \
	| $(AWK) -v _INDENT='$(if $(_HAS_GROUP_LABEL),$(INDENT),0)' -v _IDENTIFIER='$2' -v _MESSAGE='$3' -v _STATUS="$$status" "$$_GROUP_AWK"); code=$$?; \
$(if $(_IS_GROUP_PARALLEL),[ -z "$$report" ] || $(PRINTF) '%s\n' $${label:+"$$label"} "$$report";, \
	[ -z "$$has_spinner" ] || { \
		kill $$spinner 2>/dev/null; \
		wait $$spinner 2>/dev/null; \
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
spin_command() { $1; }; \
has_spinner=; $(if $(or $(_IS_PARALLEL),$(_IS_NO_ANSI)),,[ -t 1 ] \
	&& [ -n '$(SCRIPT)' ] \
	&& command -v $(SCRIPT) >/dev/null 2>&1 \
	&& has_spinner=1;) \
if [ -z "$$has_spinner" ]; then \
	spin_command; \
else \
	export _SPIN_STATUS=$$($(MKTEMP)); \
	script_command='($(subst $(_QUOTE),$(_QUOTE)\$(_QUOTE)$(_QUOTE),$1)); $(PRINTF) "%s" $$? > "$$_SPIN_STATUS"'; \
	if $(SCRIPT) --version >/dev/null 2>&1; then \
		set -- -qfc "$$script_command" /dev/null; \
	else \
		set -- -q /dev/null $(SHELL) -c "$$script_command"; \
	fi; \
	$(PRINTF) '$(_ANSI_HIDE_CURSOR)'; \
	trap '$(_CLEAR_SPINNER); exit $(_INTERRUPT_EXIT_CODE)' $(_SPINNER_SIGNALS); \
	$(call __spinner); \
	SHELL='$(SHELL)' $(SCRIPT) "$$@" | { \
		IFS= read -r line; has_line=$$?; \
		kill $$spinner 2>/dev/null; \
		wait $$spinner 2>/dev/null; \
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
#* Prints a command's output for a pipe to read, failing the recipe when the command itself failed.
#*
#* parameters:
#*   command: string
#*
#* returns: void
#*
define _capture
output=$$($1) || { $(PRINTF) '%s\n' "$$output" >&2; exit $(BRNSHKR_CONFIG_ERROR_CODE); }; $(PRINTF) '%s\n' "$$output"
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
$(TRACE_PREFIX)$(AWK) -v _METRIC='$2' -v _MINIMUM='$3' '$$0 ~ _METRIC && !found { if (match($$0, /[0-9]+(\.[0-9]+)?%/)) { reached = substr($$0, RSTART, RLENGTH - 1) + 0; found = 1 } } END { exit reached < _MINIMUM + 0 }' '$1' || { \
	$(call log,`%s` coverage is below `%s%%`.,$(COLOR_ERROR),'$2' '$3') >&2; \
	exit $(BRNSHKR_CONFIG_ERROR_CODE); \
}
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
$(foreach WORD,$1,'$(subst $(_QUOTE),$(_QUOTE)\$(_QUOTE)$(_QUOTE),$(WORD))')
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
#* Asks a yes/no question before something destructive happens.
#* It answers itself where nobody can: under `CI` it says yes, and without a terminal to ask at it
#* says no. It succeeds on yes, so the caller reads it as a condition and says what it skipped.
#*
#* parameters:
#*   question: string
#*   default?: OneOf<yes | no> = no
#*
#* returns: void
#*
define confirm
{ \
	[ -n "$(_IS_CI)" ] || { \
		[ -t 0 ] \
			&& $(PRINTF) '%s $(call text,[$(if $(filter yes,$2),Y/n,y/N)],$(COLOR_NOTICE)) ' '$1' \
			| $(call _render_inline_code,printf "%s"$(_COMMA) $$0,yes) >&2 \
			&& trap '$(PRINTF) "\n" >&2; trap - $(_SIGNAL_INTERRUPT); kill -$(_SIGNAL_INTERRUPT) $$$$' $(_SIGNAL_INTERRUPT) \
			&& read -r answer \
			&& trap - $(_SIGNAL_INTERRUPT) \
			&& case "$$answer" in \
				[yY]|[yY][eE][sS]) true;; \
				"") $(if $(filter yes,$2),true,false);; \
				*) false;; \
			esac; \
	}; \
}
endef

#**
#* Prints a message behind the `<VENDOR>/<PACKAGE>` label.
#* Anything in backticks renders as inline code. The message is a `printf` format, so a value goes
#* in as an argument rather than being pasted in. A literal comma has to be written as `$(_COMMA)`.
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
$(PRINTF) '$(call text,[$(_LABEL)],$2) $1\n' $3 \
	| $(call _render_inline_code,print,$(if $(filter no-autolink,$4),,yes))
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
$(AWK) -v _OPEN='$(call _ansi,$(COLOR_HIGHLIGHT))' \
	-v _CLOSE='$(_ANSI_RESET)' \
	-v _URL='$(if $(filter yes,$2),$(_INLINE_CODE_URL))' \
	-v _CWD='$(CURDIR)' \
	-v _PREFIX='$(_TARGET_PREFIX)' \
	-v _STAGES=' $(_DOTENV_STAGES) ' \
	-v _TARGETS=' $(if $(filter yes,$2),$(_TARGET_LINKS)) ' ' \
	function render_url(url, path, line,    placeholder_position) { \
		placeholder_position = index(url, "{file}"); \
		url = substr(url, 1, placeholder_position - 1) path substr(url, placeholder_position + 6); \
		placeholder_position = index(url, "{line}"); \
		if (placeholder_position) url = substr(url, 1, placeholder_position - 1) line substr(url, placeholder_position + 6); \
		return url \
	} \
	function target_definition(name,    key, position, definition, dash) { \
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
	function render_hyperlink(path,    line, absolute_path, definition) { \
		if (_URL == "") return ""; \
		definition = target_definition(path); \
		if (definition != "") path = definition; \
		else if (path !~ /[\/.]/ || path ~ /[[:space:]]/) return ""; \
		line = 1; \
		if (match(path, /:[0-9]+$$/)) { \
			line = substr(path, RSTART + 1); \
			path = substr(path, 1, RSTART - 1) \
		} \
		if (substr(path, 1, 2) == "./") path = substr(path, 3); \
		absolute_path = substr(path, 1, 1) == "/" ? path : _CWD "/" path; \
		if (system("test -f " absolute_path) != 0) return ""; \
		return "\033]8;;" render_url(_URL, absolute_path, line) "\033\\" \
	} \
	{ \
		while (match($$0, /`[^`]*`/)) { \
			code_start = RSTART; \
			code_length = RLENGTH; \
			inline_code = substr($$0, code_start + 1, code_length - 2); \
			hyperlink = render_hyperlink(inline_code); \
			hyperlink_end = hyperlink == "" ? "" : "\033]8;;\033\\"; \
			$$0 = substr($$0, 1, code_start - 1) hyperlink _OPEN inline_code _CLOSE hyperlink_end substr($$0, code_start + code_length) \
		} \
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
$(if $(_IS_NO_ANSI),,\033[$(if $(call _get_modifiers,$2),$(call _get_modifiers,$2);)$(call _get_color,$1,$2)m)
endef

#**
#* Converts a string to title case, treating a hyphen as a word break.
#*
#* parameters:
#*   text: string
#*
#* returns: string
#*
define _to_title_case
$(shell $(PRINTF) '%s\n' '$1' | \
	$(AWK) '{ \
		gsub(/-/, " "); \
		for (i = 1; i <= NF; i += 1 ) { \
			$$i = toupper(substr($$i, 1, 1)) \
			substr($$i, 2) \
		} \
	} \
	1' \
)
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
#* Every UPPER_SNAKE variable as `NAME="value"` shell assignments, escaped and flattened to one line.
#*
#* returns: string
#*
define _make_vars_as_env
$(foreach VARIABLE,$(shell $(AWK) -v _VAR_NAMES="$(.VARIABLES)" '\
	BEGIN { \
		count = split(_VAR_NAMES, names, " "); \
		for (idx = 1; idx <= count; idx++) \
			if (names[idx] ~ /^_?[A-Z][A-Z0-9_]*$$/) \
				print names[idx] \
	}' \
),$(if $(filter simple recursive,$(flavor $(VARIABLE))), \
	$(VARIABLE)="$(subst $$,\$$,$(subst ",\",$(subst `,\`,$(subst \,\\,$(subst $(_NEWLINE), ,$($(VARIABLE)))))))" \
))
endef

#**
#* Returns the detected editor name by inspecting well-known terminal environment variables.
#*
#* returns: OneOf<vscode | phpstorm> | empty-string
#*
define _editor_from_env
$(if $(filter vscode,$(TERM_PROGRAM)),$(EDITOR_VSCODE),$(if \
	$(VSCODE_PID),$(EDITOR_VSCODE),$(if \
		$(filter JetBrains-JediTerm,$(TERMINAL_EMULATOR)),$(EDITOR_PHPSTORM),)))
endef

#**
#* Returns the detected editor name based on command availability in PATH.
#*
#* returns: OneOf<vscode | phpstorm> | empty-string
#*
define _editor_from_command
$(if $(shell command -v code >/dev/null 2>&1 && echo 1),$(EDITOR_VSCODE),$(if \
	$(shell { command -v pstorm || command -v phpstorm; } >/dev/null 2>&1 && echo 1),$(EDITOR_PHPSTORM),))
endef

#---vvv programs

define _DOTENV_AWK
function fail(message) { gsub(/\$$/, "$$$$", message); printf "$$(error [%s] %s)\n", _PREFIX, message; exit 1 }
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
function load(path,    line, key, value, quote, continued) {
	if ((getline line < path) < 0) return 0
	do {
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
				if ((getline continued < path) <= 0) fail("unterminated quote in `" path "`")
				sub(/\r$$/, "", continued)
				value = value "\n" continued
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
	} while ((getline line < path) > 0)
	close(path)
	return 1
}
function environment(fallback,    value) { value = _ENV_VALUE != "" ? _ENV_VALUE : (_ENV_KEY in values) ? values[_ENV_KEY] : ""; return value != "" ? value : fallback }
BEGIN {
	SQ = sprintf("%c", 39)
	if (!load(_BASE)) load(_BASE ".dist")
	env = environment(_DEFAULT_ENV)
	split(_TEST_ENVS, tests, /[ \t]+/)
	for (i in tests) if (tests[i] == env) is_test = 1
	if (!is_test) { load(_BASE ".local"); env = environment(env) }
	if (env != "local") { load(_BASE "." env); load(_BASE "." env ".local") }
	if (!(_ENV_KEY in values)) order[++count] = _ENV_KEY
	values[_ENV_KEY] = env
	for (i = 1; i <= count; i += 1) {
		key = order[i]
		value = values[key]
		gsub(/\$$/, "$$$$", value)
		gsub(/#/, "\\#", value)
		if (value ~ /\n/) {
			gsub(/\n/, _SEPARATOR, value)
			printf "define %s ?=%s%s%sendef%s", key, _SEPARATOR, value, _SEPARATOR, _SEPARATOR
		} else {
			printf "%s ?= %s%s", key, value, _SEPARATOR
		}
		printf "export %s%s", key, _SEPARATOR
		if (!(key in ENVIRON)) printf "_DOTENV_FILE_KEYS += %s%s", key, _SEPARATOR
	}
}
endef

_DOTENV_ASSIGNMENTS := $(if $(wildcard $(_DOTENV_BASE) $(_DOTENV_BASE).dist),$(shell \
	$(AWK) \
		-v _BASE='$(_DOTENV_BASE)' \
		-v _ENV_KEY='$(DOTENV_ENV_KEY)' \
		-v _ENV_VALUE='$($(DOTENV_ENV_KEY))' \
		-v _DEFAULT_ENV='$(DOTENV_DEFAULT_ENV)' \
		-v _TEST_ENVS='$(DOTENV_TEST_ENVS)' \
		-v _PREFIX='$(_LABEL)' \
		-v _SEPARATOR='$(_DOTENV_SEPARATOR)' \
		'$(_DOTENV_AWK)' \
))

$(eval $(subst $(_DOTENV_SEPARATOR),$(_NEWLINE),$(_DOTENV_ASSIGNMENTS)))

define _GITATTRIBUTES_PROGRAM_SOURCE
$$attributesPath = $$argv[1];
$$manifest = json_decode((string) file_get_contents($$argv[2]), true, flags: JSON_THROW_ON_ERROR);

$$shippedPaths = ['composer.json', 'LICENSE', 'README.md'];
$$declaredPaths = array_filter(explode(' ', $$argv[3] ?? ''));

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

$$projectDirectory = dirname($$argv[2]);
$$expand = static function (array $$paths, bool $$wholeTree) use ($$projectDirectory): array {
    $$expanded = [];

    foreach ($$paths as $$path) {
        $$path = (string) preg_replace('#^(?:\./)+#', '', $$path);
        $$isWholeTree = $$wholeTree
            ? is_dir($$projectDirectory . '/' . trim($$path, '/'))
            : str_ends_with($$path, '/');
        $$path = '/' . trim($$path, '/');
        $$expanded[] = $$path;

        if ($$isWholeTree) {
            $$expanded[] = $$path . '/**';
        }
    }

    return $$expanded;
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

$$pathWidth = max(array_map(strlen(...), [...$$shippedPaths, '*']));
$$renderedLines = [
    sprintf('%-' . $$pathWidth . 's  export-ignore', '*'),
    sprintf('%-' . $$pathWidth . 's  export-ignore', '.*'),
];

foreach ($$shippedPaths as $$path) {
    $$renderedLines[] = sprintf('%-' . $$pathWidth . 's -export-ignore', $$path)
        . (isset($$shippedLookup[$$path]) ? '' : ' -export-subst');
}

$$currentContents = (string) file_get_contents($$attributesPath);
$$generatedRegion = "###> brnshkr/config ###\n" . implode("\n", $$renderedLines) . "\n###< brnshkr/config ###";
$$updatedContents = preg_replace('/###> brnshkr\/config ###.*###< brnshkr\/config ###/s', $$generatedRegion, $$currentContents, 1);

if ($$updatedContents !== null && $$updatedContents !== $$currentContents) {
    file_put_contents($$attributesPath, $$updatedContents);
    echo $$attributesPath;
}
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
section=$$([ ! -f '$(_CHANGELOG_FILE)' ] || _CHANGELOG_VERSION='$(_CHANGELOG_VERSION)' \
	$(AWK) "$$_CHANGELOG_SECTION_AWK" '$(_CHANGELOG_FILE)'); \
if [ -n "$$section" ]; then \
	$(CAT) >/dev/null; \
	$(PRINTF) '%s\n' "$$section"; \
else \
	$(AWK) 'NR == 1 && /^## / { next } { body = body $$0 "\n" } \
		END { sub(/^\n+/, "", body); printf "%s", body }'; \
fi; \
[ -z '$(_CHANGELOG_COMPARE)' ] || $(PRINTF) '\n**Full Changelog**: %s\n' '$(_CHANGELOG_COMPARE)'
endef

#**
#* Merges the generated sections into `CHANGELOG_DIR`, keeping every note already written there.
#*
#* returns: void
#*
define _changelog_write
if [ '$(_CHANGELOG_MAJOR)' = '0' ] && [ -z '$(filter force f,$(_ARG_WORDS))' ]; then \
	$(CAT) >/dev/null; \
	$(call log,`%s` is below `1.0.0`$(_COMMA) pass `--force` to write it.,$(COLOR_NOTICE),'$(_CHANGELOG_VERSION)'); \
else \
	$(MKDIR) -p '$(CHANGELOG_DIR)'; \
	$(CAT) > '$(_CHANGELOG_FILE).added'; \
	{ \
		[ -f '$(_CHANGELOG_FILE)' ] || $(PRINTF) '%s\n\n' '$(CHANGELOG_TITLE)'; \
		_CHANGELOG_ADDED='$(_CHANGELOG_FILE).added' $(AWK) "$$_CHANGELOG_MERGE_AWK" \
			'$(_CHANGELOG_FILE).added' \
			$$([ -f '$(_CHANGELOG_FILE)' ] && $(PRINTF) '%s' '$(_CHANGELOG_FILE)'); \
	} > '$(_CHANGELOG_FILE).new'; \
	$(RM) -f '$(_CHANGELOG_FILE).added'; \
	if [ ! -f '$(_CHANGELOG_FILE)' ]; then \
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

define _CHANGELOG_SECTION_AWK_SOURCE
function version_of(line,    value) {
	value = line
	sub(/^## \[?/, "", value)
	sub(/[]( ].*$$/, "", value)
	return value
}
BEGIN { wanted = ENVIRON["_CHANGELOG_VERSION"] }
/^## / {
	inside = (version_of($$0) == wanted)
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
function pad(value,    padded) {
	padded = "0000000000" value
	return substr(padded, length(padded) - 9)
}
function version_of(line,    value) {
	value = line
	sub(/^## \[?/, "", value)
	sub(/[]( ].*$$/, "", value)
	return value
}
function semver_key(version,    value, prerelease, parts, count, position, key, tokens, token_count, token) {
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
function hash_of(line,    value) {
	if (!match(line, /\(\[?[0-9a-f]{7}\]?[)(]/)) return ""
	value = substr(line, RSTART, RLENGTH)
	gsub(/[^0-9a-f]/, "", value)
	return value
}
function is_entry(line) {
	return (line ~ /^- /) && hash_of(line) != ""
}
function is_heading(line) {
	return line ~ /^#{3,4} /
}

function section_text(index_,    position, out) {
	out = ""
	for (position = 1; position <= added_lines[index_]; position += 1) out = out added_line[index_, position] "\n"
	sub(/\n+$$/, "", out)
	return out "\n"
}

function merged_text(existing_index, added_index,    position, entry, group, pending, line, out, held, tail, seen_entry) {
	pending = 0
	for (position = 1; position <= added_entries[added_index]; position += 1) {
		if ((existing_index, added_entry_hash[added_index, position]) in existing_hash) continue
		pending += 1
		pending_group[pending] = added_entry_group[added_index, position]
		pending_entry[pending] = added_entry[added_index, position]
	}
	if (pending == 0) return ""

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
			held = held tail line "\n"
			tail = ""
		} else if (seen_entry) {
			tail = tail line "\n"
		} else {
			out = out line "\n"
		}
	}
	out = out flush_group(group, held, tail, seen_entry, pending)
	for (position = 1; position <= pending; position += 1) {
		if (pending_used[position]) continue
		out = out "\n" (pending_group[position] == "" ? "" : pending_group[position] "\n\n") pending_entry[position] "\n"
	}
	split("", pending_used)
	sub(/\n+$$/, "", out)
	return out "\n"
}

function flush_group(group, held, tail, seen_entry, pending,    position, out) {
	out = held
	for (position = 1; position <= pending; position += 1) {
		if (pending_used[position] || pending_group[position] != group) continue
		out = out pending_entry[position] "\n"
		pending_used[position] = 1
	}
	return out tail
}

# Every section the generator produced, with its entries and the group each sits under.
BEGIN { _added = ENVIRON["_CHANGELOG_ADDED"] }
FILENAME == _added {
	if ($$0 ~ /^## /) {
		added_count += 1
		added_version[added_count] = version_of($$0)
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
		added_entry_hash[added_count, added_entries[added_count]] = hash_of($$0)
	}
	next
}

# The file as it stands, kept verbatim.
{
	if ($$0 ~ /^## /) {
		existing_count += 1
		existing_version[existing_count] = version_of($$0)
	}
	if (existing_count == 0) {
		preamble = preamble $$0 "\n"
		next
	}
	existing_lines[existing_count] += 1
	existing_line[existing_count, existing_lines[existing_count]] = $$0
	if (is_entry($$0)) existing_hash[existing_count, hash_of($$0)] = 1
}

# An existing section, with any entry it is missing placed after the last entry of its own group.
END {
	document = preamble

	for (existing_index = 1; existing_index <= existing_count; existing_index += 1) {
		matched[existing_version[existing_index]] = existing_index
	}
	for (added_index = 1; added_index <= added_count; added_index += 1) {
		matched_added[added_version[added_index]] = added_index
	}

	# Sections the file does not carry yet, newest first, placed among the ones it does.
	for (added_index = 1; added_index <= added_count; added_index += 1) {
		if (added_version[added_index] in matched) continue
		insert_count += 1
		insert_key[insert_count] = semver_key(added_version[added_index])
		insert_index[insert_count] = added_index
	}
	for (position = 2; position <= insert_count; position += 1) {
		key_held = insert_key[position]
		value_held = insert_index[position]
		sorted = position - 1
		while (sorted >= 1 && insert_key[sorted] < key_held) {
			insert_key[sorted + 1] = insert_key[sorted]
			insert_index[sorted + 1] = insert_index[sorted]
			sorted -= 1
		}
		insert_key[sorted + 1] = key_held
		insert_index[sorted + 1] = value_held
	}

	pending_insert = 1
	for (existing_index = 1; existing_index <= existing_count; existing_index += 1) {
		while (pending_insert <= insert_count \
			&& insert_key[pending_insert] > semver_key(existing_version[existing_index])) {
			document = document section_text(insert_index[pending_insert]) "\n"
			pending_insert += 1
		}
		replacement = ((existing_version[existing_index] in matched_added) \
			? merged_text(existing_index, matched_added[existing_version[existing_index]]) : "")
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
		document = document section_text(insert_index[pending_insert]) "\n"
		pending_insert += 1
	}

	sub(/\n+$$/, "", document)
	print document
}
endef

define _CHANGELOG_AWK_SOURCE
function sort_pair(keys, values, count,    position, sorted, key_held, value_held) {
	for (position = 2; position <= count; position += 1) {
		key_held = keys[position]
		value_held = values[position]
		sorted = position - 1
		while (sorted >= 1 && keys[sorted] > key_held) {
			keys[sorted + 1] = keys[sorted]
			values[sorted + 1] = values[sorted]
			sorted -= 1
		}
		keys[sorted + 1] = key_held
		values[sorted + 1] = value_held
	}
}
function render(section, label,    position, inner, seconds, mirrored, keys, entries, second, out) {
	out = unscoped[section, label]
	for (position = 1; position <= second_count[section, label]; position += 1) {
		seconds[position] = second_name[section, label, position]
		mirrored[position] = second_name[section, label, position]
	}
	sort_pair(seconds, mirrored, second_count[section, label])
	for (position = 1; position <= second_count[section, label]; position += 1) {
		second = seconds[position]
		if (out != "") out = out "\n"
		out = out "#### " ((second in names) ? names[second] : second) "\n\n" sub_unscoped[section, label, second]
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
function major_of(version,    value) {
	value = version
	sub(/^v/, "", value)
	sub(/\..*$$/, "", value)
	return value
}
function flush(section, version, compare,    position, label, printed, out) {
	if (!(section in has_content)) return ""
	if (_major != "" && major_of(version) != _major) return ""
	out = (version == "") ? "" : "## " ((compare != "") ? "[" version "](" compare ")" : version) "\n"
	if ((section in breaking)) out = out "\n### " breaking_label "\n\n" breaking[section]
	for (position = 1; position <= rank; position += 1) {
		label = ordered[position]
		if (((section, label) in seen) && !(label in printed)) {
			out = out "\n### " label "\n\n" render(section, label)
			printed[label] = 1
		}
	}
	if (((section, other) in seen)) out = out "\n### " other "\n\n" render(section, other)
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
		if (!(label in ranked)) { ranked[label] = ++rank; ordered[rank] = label }
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
	nested = scope
	if (is_grouped) sub(/^[^\/]+\/?/, "", nested)
	second = nested
	sub(/\/.*$$/, "", second)
	rest = nested
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

	if (nested == "") {
		unscoped[section, label] = unscoped[section, label] entry "\n"
	} else {
		if (!((section, label, second) in second_seen)) {
			second_seen[section, label, second] = 1
			second_count[section, label] += 1
			second_name[section, label, second_count[section, label]] = second
		}
		if (rest == "") {
			sub_unscoped[section, label, second] = sub_unscoped[section, label, second] entry "\n"
		} else {
			sub_count[section, label, second] += 1
			sub_key[section, label, second, sub_count[section, label, second]] = rest
			sub_entry[section, label, second, sub_count[section, label, second]] = entry
		}
	}

	seen[section, label] = 1
	has_content[section] = 1
}
END {
	for (index_ = 0; index_ <= section; index_ += 1) {
		rendered = flush(index_, section_version[index_], (_all != "") ? section_compare[index_] : _compare)
		if (rendered == "") continue
		document = (document == "") ? rendered : document "\n" rendered
	}
	if (document == "") exit
	sub(/\n+$$/, "", document)
	print document
}
endef

define _GROUP_AWK_SOURCE
function repeat(text, times,    out) { out = ""; while (times-- > 0) out = out text; return out }
function value(record, key,    pattern) {
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
	if (!(identifier in seen)) { seen[identifier] = 1; order[++total] = identifier }
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
	if (!is_in_suppressed_messages) add(value(pending, _IDENTIFIER), value(pending, _MESSAGE))
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
function leaf(path) { sub(/.*\//, "", path); return path }
function repeat(text, count,    out) { out = ""; while (count-- > 0) out = out text; return out }
function character_bytes(leading_byte) {
	return (leading_byte < "\200") ? 1 : ((leading_byte < "\340") ? 2 : ((leading_byte < "\360") ? 3 : 4))
}
function character_width(character) {
	if (character < "\340") return 1
	if (character < "\360") return (character >= "\342\230\200" && character <= "\342\237\277") ? 2 : 1
	return 2
}
function display_width(text,    position, byte_count, total_width) {
	total_width = 0
	for (position = 1; position <= length(text); position += byte_count) {
		byte_count = character_bytes(substr(text, position, 1))
		total_width += character_width(substr(text, position, byte_count))
	}
	return total_width
}
function truncate(text, width_limit,    position, byte_count, character, taken_width, truncated_text) {
	if (display_width(text) <= width_limit) return text
	taken_width = 0
	truncated_text = ""
	for (position = 1; position <= length(text); position += byte_count) {
		byte_count = character_bytes(substr(text, position, 1))
		character = substr(text, position, byte_count)
		if (taken_width + character_width(character) > width_limit - 1) break
		truncated_text = truncated_text character
		taken_width += character_width(character)
	}
	return truncated_text "…"
}
function render_url(template, file, line) {
	if (template == "") return ""
	gsub(/\{cwd\}/, ENVIRON["_CURDIR"], template)
	gsub(/\{wslDistro\}/, ENVIRON["WSL_DISTRO_NAME"], template)
	gsub(/\{file\}/, file, template)
	gsub(/\{line\}/, line, template)
	return template
}
function strip_verbosity(text) { sub(/[ \t]*#v+([ \t]|$$)/, "", text); return text }
function line_verbosity(line,    verbosity_match) { if (line == "") line = $$0; if (!match(line, /#v+([[:space:]]|$$)/)) return 0; verbosity_match = substr(line, RSTART + 1, RLENGTH - 1); sub(/[[:space:]]$$/, "", verbosity_match); if (length(verbosity_match) > max_verbosity) max_verbosity = length(verbosity_match); return length(verbosity_match) }
function get_description() { if (!match($$0, /#~~[ ]*/)) return ""; return trim(strip_verbosity(substr($$0, RSTART + RLENGTH))) }
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
	is_parent_active = (condition_depth == 1 || condition_is_active[condition_depth - 1])
	condition_is_active[condition_depth]      = is_parent_active ? eval_condition(line) : 0
	condition_branch_matched[condition_depth] = condition_is_active[condition_depth]
}
function else_condition(line,    else_rest) {
	if (condition_depth == 0) return
	is_parent_active = (condition_depth == 1 || condition_is_active[condition_depth - 1])
	else_rest = line; sub(/^else[ \t]*/, "", else_rest)
	if (is_parent_active && else_rest ~ /^(ifeq|ifneq|ifdef|ifndef)[ \t]/) {
		if (condition_branch_matched[condition_depth]) condition_is_active[condition_depth] = 0
		else {
			condition_is_active[condition_depth] = eval_condition(else_rest)
			if (condition_is_active[condition_depth]) condition_branch_matched[condition_depth] = 1
		}
	} else if (is_parent_active) condition_is_active[condition_depth] = !condition_branch_matched[condition_depth]
}
function matches_scope(path,    i, f) {
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
function is_scope_visible(path) { return scope_verbosity[path] + 0 <= verbosity }
function scope_has_visible_entries(scope_path, section,    idx) {
	if (!matches_scope(scope_path)) return 0
	if (!is_scope_target(scope_path)) return 0
	if (!is_scope_visible(scope_path)) return 0
	for (idx = 1; idx <= section_count[scope_path, section] + 0; idx += 1)
		if (section_verbosity[scope_path, section, idx] + 0 <= verbosity) return 1
	return 0
}
function push_scope(level, name, verbosity,    current, parent_verbosity, parent) {
	scope_at[level] = (level > 0 && scope_at[level - 1] != "" ? scope_at[level - 1] "/" name : name)
	scope_depth = level
	current = scope_at[level]
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
function alias_of(name,    map, position, remainder) {
	map = " " ENVIRON["_TARGET_ALIAS_MAP"] " "
	position = index(map, " " name "=")
	if (!position) return ""
	remainder = substr(map, position + length(name) + 2)
	return ", " substr(remainder, 1, index(remainder, " ") - 1)
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
	section_name[current, section, idx] = name (section == "commands" ? alias_of(name) : "")
	section_text[current, section, idx] = text
	section_extra[current, section, idx] = extra
	section_description[current, section, idx] = description
	section_file[current, section, idx] = FILENAME
	section_line[current, section, idx] = line
	section_verbosity[current, section, idx] = (entry_verbosity != "" ? entry_verbosity : line_verbosity())
}
function handle_define(documentation,    description) {
	if ($$2 ~ /^_/) return
	if ($$2 ~ /^[A-Z][A-Z0-9_]*$$/) {
		add("variables", $$2, "(macro)", (is_in_ifndef ? "?=" : ":="), get_description(), FNR)
	} else {
		description = get_description()
		if (documentation != "") add("functions", $$2, documentation, (description != "" ? description : derive_description(documentation)), "", FNR)
		else add("functions", $$2, "", description, "", FNR)
	}
}
function has_content(scope_path, section,    idx) {
	if (!matches_scope(scope_path)) return 0
	if (scope_has_visible_entries(scope_path, section)) return 1
	for (idx = 1; idx <= child_count[scope_path]; idx += 1)
		if (has_content(children[scope_path, idx], section)) return 1
	return 0
}
function has_any_content(scope_path) {
	return has_content(scope_path, "description") || has_content(scope_path, "variables") \
		|| has_content(scope_path, "functions") || has_content(scope_path, "commands")
}
function lowest_verbosity(scope_path,    shown, level) {
	shown = verbosity
	for (level = 0; level <= max_verbosity; level += 1) { verbosity = level; if (has_any_content(scope_path)) break }
	verbosity = shown
	return (level <= max_verbosity ? level : -1)
}
function tree_max(scope_path, depth, section, mode,    best, idx, width, child_width, value) {
	if (!matches_scope(scope_path)) return 0
	if (is_scope_visible(scope_path) && is_scope_target(scope_path)) {
		for (idx = 1; idx <= section_count[scope_path, section] + 0; idx += 1) {
			if (section_verbosity[scope_path, section, idx] + 0 > verbosity) continue
			if (mode == "value" && section_description[scope_path, section, idx] == "") continue
			value = section_text[scope_path, section, idx]
			if (mode == "value" && ENVIRON["_HAS_RESOLVE_ARGUMENT"] != "" && section_name[scope_path, section, idx] in ENVIRON) value = trim(ENVIRON[section_name[scope_path, section, idx]])
			width = (mode == "value") ? length(value) : (depth + 2) * ENVIRON["INDENT"] + length(section_name[scope_path, section, idx])
			if (width > best) best = width
		}
	}
	for (idx = 1; idx <= child_count[scope_path]; idx += 1) {
		child_width = tree_max(children[scope_path, idx], depth + 1, section, mode)
		if (child_width > best) best = child_width
	}
	return best
}
function walk(scope_path, depth, section, column,    render_scope, padding, width, format, idx, entry, text, extra, description, count, lines, line_idx, has_pending_blank, was_function_printed) {
	if (!matches_scope(scope_path)) return
	if (!has_content(scope_path, section)) return
	if (scope_path != "main") {
		printf "%s$(call text,%s,$(COLOR_SCOPE))\n", repeat(" ", (depth + 1) * ENVIRON["INDENT"]), leaf(scope_path)
	}
	render_scope = scope_has_visible_entries(scope_path, section)
	if (render_scope) {
		padding = repeat(" ", (depth + 2) * ENVIRON["INDENT"])
		width = column - (depth + 2) * ENVIRON["INDENT"]
		for (idx = 1; idx <= section_count[scope_path, section] + 0; idx += 1) {
			if (section_verbosity[scope_path, section, idx] + 0 > verbosity) continue
			entry = section_name[scope_path, section, idx]
			text = section_text[scope_path, section, idx]
			extra = section_extra[scope_path, section, idx]
			description = section_description[scope_path, section, idx]
			if (section == "variables" && ENVIRON["_HAS_RESOLVE_ARGUMENT"] != "" && entry in ENVIRON) text = trim(ENVIRON[entry])
			if (section == "variables") description = render_inline_code(description)
			else if (section == "commands") text = render_inline_code(text)
			else if (section == "functions") { extra = render_inline_code(extra); text = render_inline_code(text) }
			else if (section == "description") text = render_inline_code(text)
			file = section_file[scope_path, section, idx]
			line = section_line[scope_path, section, idx]
			if (file != "" && file !~ /^\//) file = ENVIRON["_CURDIR"] "/" file
			if (ENVIRON["_WORKDIR"] != ENVIRON["_CURDIR"] && index(file, ENVIRON["_WORKDIR"] "/") == 1) \
				file = ENVIRON["_CURDIR"] substr(file, length(ENVIRON["_WORKDIR"]) + 1)
			url = render_url(ENVIRON["_EDITOR_URL"], file, line)
			hyperlink = ""
			hyperlink_end = ""
			if (url != "" && !ENVIRON["_IS_NO_ANSI"]) {
				hyperlink = "\033]8;;" url "\033\\"
				hyperlink_end = "\033]8;;\033\\"
			}
			entry_linked = hyperlink entry hyperlink_end
			entry_pad = width - length(entry)
			if (entry_pad < 0) entry_pad = 0
			entry_padded = entry_linked repeat(" ", entry_pad)
			if (section == "variables") {
				if (description != "") {
					text = truncate(text, description_column)
					format = sprintf("%2s  %s%s", extra, text, repeat(" ", description_column - display_width(text)))
					printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s (%s),$(COLOR_DESCRIPTION))\n", padding, entry_padded, format, description
				} else {
					printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%2s  %s,$(COLOR_DESCRIPTION))\n", padding, entry_padded, extra, text
				}
			} else if (section == "commands") {
				printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s,$(COLOR_DESCRIPTION))\n", padding, entry_padded, text
			} else if (section == "functions") {
				if (was_function_printed && verbosity >= 2 && text != "") printf "\n"
				was_function_printed = (text != "" && verbosity >= 2)
				if (extra != "" && (verbosity < 2 || text == "")) {
					printf "%s$(call text,%s,$(COLOR_ENTRY))  $(call text,%s,$(COLOR_DESCRIPTION))\n", padding, entry_padded, extra
				} else {
					printf "%s$(call text,%s,$(COLOR_ENTRY))\n", padding, entry_linked
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
current_file != FILENAME { current_file = FILENAME; for (i in scope_at) delete scope_at[i]; for (i in condition_is_active) delete condition_is_active[i]; for (i in condition_branch_matched) delete condition_branch_matched[i]; scope_depth = 0; is_in_define = 0; is_in_documentation = 0; is_in_ifndef = 0; condition_depth = 0 }
/^endef([[:space:]].*)?$$/ { is_in_define = 0; next }
is_in_define { next }
/^(ifeq|ifneq|ifdef|ifndef)[[:space:]]/ { begin_condition($$0); next }
/^else([[:space:]]|$$)/ { else_condition($$0); next }
/^endif([[:space:]]|$$)/ { if (condition_depth > 0) { condition_depth--; is_in_ifndef = 0 } next }
condition_depth > 0 && !condition_is_active[condition_depth] { next }
/^#-+/ { match($$0, /^#-+/); scope_dash_count = RLENGTH - 1; scope_suffix = substr($$0, RLENGTH + 1); match(scope_suffix, /^v*/); scope_verbosity_count = RLENGTH; push_scope(scope_dash_count - 2, trim(substr(scope_suffix, scope_verbosity_count + 1)), scope_verbosity_count); next }
/^#\*\*/ { is_in_documentation = 1; documentation_text = ""; next }
is_in_documentation {
	if (/^#\*/) { sub(/^#\*[ ]?/, ""); documentation_text = documentation_text $$0 "\n"; next }
	if (/^define[ \t]+/) { sub(/\n+$$/, "", documentation_text); handle_define(documentation_text); is_in_documentation = 0; is_in_define = 1; next }
	if (!/^#/) { is_in_documentation = 0; next }
}
/^define[[:space:]]+/ { handle_define(""); is_in_define = 1; next }
/^#!!/ { sub(/^#!![ ]*/, ""); add("description", "", strip_verbosity($$0), "", "", FNR); next }
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
	if (var_name !~ /^_/) {
		raw = substr(var_source, index(var_source, "=") + 1)
		while (raw ~ /\\[ \t]*$$/) {
			sub(/[ \t]*\\[ \t]*$$/, "", raw)
			if ((getline continuation) <= 0) break
			sub(/^[ \t]+/, "", continuation)
			if (var_verbosity == "") {
				continuation_verbosity = line_verbosity(continuation)
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
		add("variables", var_name, trim(strip_verbosity(raw)), (is_in_ifndef ? "?=" : var_operator), var_description, line_number, var_verbosity)
	}
	next
}
function target_name(name) { return (name in resolved_name) ? resolved_name[name] : ENVIRON["_TARGET_PREFIX"] name }
{ while (match($$0, /\$$\(call _target,[a-zA-Z0-9_.-]+\)/)) $$0 = substr($$0, 1, RSTART - 1) target_name(substr($$0, RSTART + 15, RLENGTH - 16)) substr($$0, RSTART + RLENGTH) }
{ gsub(/\$$\(_TARGET_PREFIX\)/, ENVIRON["_TARGET_PREFIX"]) }

/^[A-Za-z0-9_.-]+([ \t]+[A-Za-z0-9_.-]+)*:([^=]|$$)/ {
	split($$0, parts, ":")
	description = get_description()
	count = split(parts[1], names, /[ \t]+/)
	joined_commands = ""
	for (i = 1; i <= count; i += 1) {
		name = names[i]
		if (name == "" || name ~ /^[_\.]/) continue
		if (name in command_seen) continue
		command_seen[name] = 1
		joined_commands = (joined_commands == "" ? name : joined_commands "/" name)
	}
	if (joined_commands != "") add("commands", joined_commands, description, "", "", FNR)
	next
}
END {
	if (ENVIRON["_EDITOR_UNKNOWN"] != "") {
		editor_count = split(ENVIRON["_EDITORS"], editor_names, " ")
		editor_choices = ""
		for (editor_idx = 1; editor_idx <= editor_count; editor_idx += 1)
			editor_choices = editor_choices (editor_idx == 1 ? "" : "|") editor_names[editor_idx]
		printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) Unknown editor \"$(call text,%s,$(COLOR_WARNING))\".\n", ENVIRON["_EDITOR_UNKNOWN"]
		printf "$(call text,[$(_LABEL)],$(COLOR_NOTICE)) Run with \"$(call text,EDITOR=<%s>,$(MODIFIER_UNDERLINE))\" or \"$(call text,EDITOR=,$(MODIFIER_UNDERLINE))\" to disable.\n", editor_choices
		exit $(BRNSHKR_CONFIG_ERROR_CODE)
	}
	for (scope_path in seen_scope)
		if (!index(scope_path, "/") && (scope_path ~ /^v+$$/ || index(" " ENVIRON["_HELP_WORDS"] " ", " " scope_path " "))) {
			printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) Scope \"$(call text,%s,$(COLOR_WARNING))\" takes a name that help reserves.\n", scope_path
			printf "$(call text,[$(_LABEL)],$(COLOR_NOTICE)) Rename it; help reads \"$(call text,%s,$(MODIFIER_UNDERLINE))\" as a word of its own.\n", scope_path
			exit $(BRNSHKR_CONFIG_ERROR_CODE)
		}
	unknown_filter_count = 0
	for (filter_idx = 1; filter_idx <= scope_filter_count; filter_idx += 1) {
		filter = scope_filter[filter_idx]
		if (filter == "") continue
		filter_verbosity = lowest_verbosity(filter)
		if (filter_verbosity >= 0 && filter_verbosity <= verbosity) continue
		filter_display = filter; gsub(/\//, ".", filter_display)
		if (filter_verbosity < 0) unknown_filters[++unknown_filter_count] = filter_display
		else { printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) Scope \"$(call text,%s,$(COLOR_WARNING))\" shows nothing below %s.\n", filter_display, repeat("v", filter_verbosity); is_hidden = 1 }
	}
	if (is_hidden && !unknown_filter_count) exit $(BRNSHKR_CONFIG_ERROR_CODE)
	if (unknown_filter_count > 0) {
		unknown_message = ""
		for (filter_idx = 1; filter_idx <= unknown_filter_count; filter_idx += 1) {
			separator = (filter_idx == 1 ? "" : (filter_idx == unknown_filter_count ? " and " : ", "))
			unknown_message = unknown_message separator sprintf("\"$(call text,%s,$(COLOR_WARNING))\"", unknown_filters[filter_idx])
		}
		unknown_label = (unknown_filter_count == 1 ? "scope" : "scopes")
		printf "$(call text,[$(_LABEL)],$(COLOR_ERROR)) Unknown %s %s.\n", unknown_label, unknown_message
		printf "$(call text,[$(_LABEL)],$(COLOR_NOTICE)) Run \"$(call text,make help list-scopes,$(MODIFIER_UNDERLINE))\" or \"$(call text,make help ls,$(MODIFIER_UNDERLINE))\" to see available scopes.\n"
		exit $(BRNSHKR_CONFIG_ERROR_CODE)
	}
	if (ENVIRON["_HAS_LIST_SCOPES_ARGUMENT"]) {
		listed_count = 0
		for (scope_path in seen_scope) {
			if (scope_path == "main") continue
			if (!matches_scope(scope_path)) continue
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
			gsub(/\//, ".", listed_paths[listed_idx])
			print listed_paths[listed_idx]
		}
		exit 0
	}
	logo = ENVIRON["_HEADER_LOGO"]; sub(/__BRNSHKR_LOGO_END__$$/, "", logo); logo_reset = ""
	if (match(logo, /\033\[[0-9;]*m$$/)) { logo_reset = substr(logo, RSTART); logo = substr(logo, 1, RSTART - 1) }
	if (length(trim(logo)) > 0) printf "%s%s\n", logo, logo_reset
	if (length(trim(ENVIRON["_HEADER_TITLE"])) > 0) printf "%s\n\n", ENVIRON["_HEADER_TITLE"]
	printf "%s\n", ENVIRON["_HEADER_USAGE"]
	for (top_idx = 1; top_idx <= top_count; top_idx += 1) {
		top_path = top_order[top_idx]
		column = tree_max(top_path, 0, "variables", "name"); if (column > variable_column) variable_column = column
		column = tree_max(top_path, 0, "variables", "value"); if (column > description_column) description_column = column
		if (description_column > ENVIRON["VALUE_WIDTH"] + 0) description_column = ENVIRON["VALUE_WIDTH"] + 0
		column = tree_max(top_path, 0, "functions", "name"); if (column > function_column) function_column = column
		column = tree_max(top_path, 0, "commands", "name"); if (column > command_column) command_column = column
	}
	has_printed = 1
	has_printed += print_section(has_printed, "description", "Description:", 0)
	has_printed += print_section(has_printed, "variables", "Variables:", variable_column)
	has_printed += print_section(has_printed, "functions", "Functions:", function_column)
	has_printed += print_section(has_printed, "commands", "Available commands:", command_column)
}
endef

export EDITOR_URL

$(call _target,cc): _ARG_VALUES = $(_CACHES)
$(call _target,changelog): export _CHANGELOG_AWK = $(_CHANGELOG_AWK_SOURCE)
$(call _target,changelog): export _CHANGELOG_MERGE_AWK = $(_CHANGELOG_MERGE_AWK_SOURCE)
$(call _target,changelog): export _CHANGELOG_SECTION_AWK = $(_CHANGELOG_SECTION_AWK_SOURCE)
$(call _target,configs): export _GITATTRIBUTES_PROGRAM = $(_GITATTRIBUTES_PROGRAM_SOURCE)
$(call _target,configs): export _NAME_INTERNAL_TAG_PROGRAM = $(_NAME_INTERNAL_TAG_PROGRAM_SOURCE)
$(call _target,configs): _ARG_VALUES = $(_CONFIG_NAMES) local l force f
$(call _target,help): export _HELP_AWK = $(_HELP_AWK_SOURCE)
$(_GROUPS): export _GROUP_AWK = $(_GROUP_AWK_SOURCE)
