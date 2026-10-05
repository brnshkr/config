#-- app

#!! Application Makefile of the `brnshkr/config` package

STARTUP_TARGETS := build \
	install-hooks

include ./conf/Makefile

#--- release

VERSION := 0.0.1-beta.5

ARCHIVE_EXTRA_PATHS := ./conf/Makefile \
	./conf/Makefile.dist \
	./conf/actionlint.dist.yaml \
	./conf/editorconfig.dist \
	./conf/gitattributes.dist \
	./conf/hadolint.dist.yaml \
	./conf/launch.dist.json \
	./conf/php-cs-fixer.dist.php \
	./conf/phpstan/ \
	./conf/phpstan.dist.php \
	./conf/phpunit.dist.xml \
	./conf/rector.dist.php \
	./conf/semgrep.dist.yaml \
	./conf/spelling/ \
	./conf/twig-cs-fixer.dist.php \
	./conf/vscode-extensions.dist.jsonc \
	./conf/vscode-extensions.js.dist.jsonc \
	./conf/vscode-extensions.php.dist.jsonc \
	./conf/vscode-settings.dist.jsonc \
	./conf/vscode-settings.js.dist.jsonc \
	./conf/vscode-settings.php.dist.jsonc \
	./conf/vscode-tailwind.css-data.dist.json#vvv

#--- forge

FORGE_IMAGE ?= ghcr.io/brnshkr/forge:dev#~~ image the container tests run in

export FORGE_IMAGE

#---vvv changelog

CHANGELOG_NAMES += editor-url=EditorUrl \
	file-finder=FileFinder

#---vv test

PHP_UNIT_MIN_COVERAGE_CLASSES  := 8.33
PHP_UNIT_MIN_COVERAGE_METHODS  := 50.06
PHP_UNIT_MIN_COVERAGE_LINES    := 63.90
VITEST_MIN_COVERAGE_BRANCHES   := 76.09
VITEST_MIN_COVERAGE_FUNCTIONS  := 94.46
VITEST_MIN_COVERAGE_LINES      := 91.30
VITEST_MIN_COVERAGE_STATEMENTS := 91.50

_HAS_FORGE_IMAGE          = $(eval _HAS_FORGE_IMAGE := $$(shell $$(DOCKER) image inspect $$(FORGE_IMAGE) >/dev/null 2>&1 && $$(PRINTF) 1))$(_HAS_FORGE_IMAGE)
_HAS_FORGE_SEMGREP_IMAGE  = $(eval _HAS_FORGE_SEMGREP_IMAGE := $$(shell $$(DOCKER) image inspect $$(FORGE_IMAGE)-semgrep >/dev/null 2>&1 && $$(PRINTF) 1))$(_HAS_FORGE_SEMGREP_IMAGE)
_HAS_SCRIPT              := $(call _is_on_path,$(SCRIPT))

PHP_UNIT_EXCLUDED_GROUPS = $(strip $(if $(wildcard $(CURDIR)/dist),,build) \
	$(if $(_HAS_FORGE_IMAGE),,container) \
	$(if $(filter 3.%,$(MAKE_VERSION)),make4) \
	$(if $(_HAS_FORGE_SEMGREP_IMAGE),,semgrep) \
	$(if $(_HAS_SCRIPT),,tty))#vv #~~ test groups this machine cannot run, left out of this project's own runs

PHP_UNIT_FLAGS += --parallel --processes=2

#---vv tools

COMPOSER := $(PHP) $(APP_DIR)/scripts/composer.php
MV       := mv#vvv #~~ path to `mv` binary
XARGS    := xargs#vvv #~~ path to `xargs` binary

#--- build

typegen: #~~ regenerates the rule types the configs are built from
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) $(APP_DIR)/scripts/typegen.ts

build: typegen #~~ builds the `./dist/` this package publishes
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) tsdown --config $(APP_DIR)/conf/tsdown.ts $(ARGS)

watch: #~~ rebuilds `./dist/` as the sources change
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) tsdown --config $(APP_DIR)/conf/tsdown.ts --watch $(ARGS)

#--- mate

_MATE_BINARY     := $(APP_DIR)/vendor/bin/mate
MATE             := $(RUN) $(_MATE_BINARY)#v
_MATE_EXTENSIONS := $(APP_DIR)/mate/extensions.php

mate: #~~ runs mate where the tools run
	$(DEBUG_PREFIX)$(MATE) $(ARGS)

discover: #~~ runs mate discover
	$(DEBUG_PREFIX)$(MATE) discover $(ARGS)
	$(DEBUG_PREFIX)if [ ! -r '$(call _host_path,$(_MATE_EXTENSIONS))' ]; then \
		$(call log,No `%s` to post-process.,$(COLOR_NOTICE),'$(call _named_path,$(_MATE_EXTENSIONS))'); \
	else \
		$(MAKE) --no-print-directory php-cs-fixer $(_MATE_EXTENSIONS) \
			&& $(AWK) '\
				/This file is managed by/,/^$$/ { next } \
				/^\/\*\*$$/,/^ \*\/$$/ { next } \
				/^return/ { print "/**\n * @internal\n */" } \
				1 \
			' '$(call _host_path,$(_MATE_EXTENSIONS))' > '$(call _host_path,$(_MATE_EXTENSIONS)).tmp' \
			&& $(MV) '$(call _host_path,$(_MATE_EXTENSIONS)).tmp' '$(call _host_path,$(_MATE_EXTENSIONS))' \
			&& $(call log,Discovery finished.,$(COLOR_SUCCESS)); \
	fi

# NOTICE: `./scripts/mate.php` feeds its arguments here separated by NUL, so none of their quoting is lost on the way
_mate-from-stdin:
	$(DEBUG_PREFIX)$(RUN) $(XARGS) -0 $(_MATE_BINARY)

#---vvv debug

_MODIFIERS := $(MODIFIER_NORMAL) \
	$(MODIFIER_UNDERLINE) \
	$(MODIFIER_BRIGHT) \
	$(MODIFIER_BOLD) \
	$(MODIFIER_REVERSE)

_MODIFIER_COLUMNS :=

$(foreach MODIFIER,$(_MODIFIERS), \
  $(eval _MODIFIER_COLUMNS := $(_MODIFIER_COLUMNS) $(MODIFIER)) \
  $(foreach MODIFIER_COLUMN,$(_MODIFIER_COLUMNS), \
    $(if $(filter $(MODIFIER),$(subst +, ,$(MODIFIER_COLUMN))),, \
      $(if $(filter $(MODIFIER_NORMAL),$(MODIFIER_COLUMN)),, \
        $(eval _MODIFIER_COLUMNS := $(_MODIFIER_COLUMNS) $(MODIFIER_COLUMN)+$(MODIFIER)) \
      ) \
    ) \
  ) \
)

colors: _MODIFIER_COLUMN_WIDTH = $(shell $(PRINTF) '$(_MODIFIER_COLUMNS)' | $(AWK) '{ \
	max = 0; \
	for (i = 1; i <= NF; i += 1) { \
		if (length($$i) > max) \
			max = length($$i) \
		} \
		print max \
	}' \
)

_TABLE_COLORS := $(filter-out $(COLOR_NORMAL),$(_COLORS))

colors: _COLOR_COLUMN_WIDTHS = $(foreach COLOR,$(_TABLE_COLORS),$(shell $(PRINTF) '$(COLOR)' | $(AWK) '{ print length($$0) }'))

colors: #~~ prints a table of all supported colors with combinations with all supported modifiers
	$(DEBUG_PREFIX)$(PRINTF) '%-$(_MODIFIER_COLUMN_WIDTH)s'
	$(DEBUG_PREFIX)$(foreach INDEX,$(call _get_indices,$(_TABLE_COLORS)), \
		$(eval _COLOR := $(word $(INDEX),$(_TABLE_COLORS))) \
		$(eval _WIDTH := $(word $(INDEX),$(_COLOR_COLUMN_WIDTHS))) \
		$(PRINTF) ' %-$(_WIDTH)s' '$(_COLOR)'; \
	)
	$(DEBUG_PREFIX)$(PRINTF) '\n'
	$(DEBUG_PREFIX)$(PRINTF) '%-$(_MODIFIER_COLUMN_WIDTH)s ' $(call _str_repeat,-,$(_MODIFIER_COLUMN_WIDTH))
	$(DEBUG_PREFIX)$(foreach INDEX,$(call _get_indices,$(_TABLE_COLORS)), \
		$(eval _WIDTH := $(word $(INDEX),$(_COLOR_COLUMN_WIDTHS))) \
		$(PRINTF) '%-*s ' $(_WIDTH) $(call _str_repeat,-,$(_WIDTH)); \
	)
	$(DEBUG_PREFIX)$(PRINTF) '\n'
	$(DEBUG_PREFIX)$(foreach MODIFIER_COLUMN,$(_MODIFIER_COLUMNS), \
		$(PRINTF) '%-$(_MODIFIER_COLUMN_WIDTH)s ' '$(subst +, ,$(MODIFIER_COLUMN))'; \
		$(foreach INDEX,$(call _get_indices,$(_TABLE_COLORS)), \
			$(eval _COLOR := $(word $(INDEX),$(_TABLE_COLORS))) \
			$(eval _WIDTH := $(word $(INDEX),$(_COLOR_COLUMN_WIDTHS))) \
			$(eval _TEXT := $(call text,$(_COLOR),$(_COLOR),$(subst +, ,$(MODIFIER_COLUMN)))) \
			$(PRINTF) '%-*b ' $(_WIDTH) '$(_TEXT)'; \
		) \
		$(PRINTF) '\n'; \
	)

#--- helpers

#**
#* Repeats a string a given number of times.
#*
#* parameters:
#*   string: string
#*   count: positive-int
#*
#* returns: string
#*
define _str_repeat
$(if $(filter 1,$2),$1,$1$(call _str_repeat,$1,$(shell $(PRINTF) $$(($2 - 1)))))
endef

#**
#* Enumerates the indices of each word in a list, from the back. Only the length is read.
#*
#* parameters:
#*   list: list<string>
#*
#* returns: list<positive-int>
#*
define _get_indices_internal
$(if $1,$(words $1) $(call _get_indices_internal,$(wordlist 2,$(words $1),$1)))
endef

#**
#* The 1-based indices of every word in a list.
#*
#* parameters:
#*   list: list
#*
#* returns: list<positive-int>
#*
define _get_indices
$(sort $(call _get_indices_internal,$1))
endef
