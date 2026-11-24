#-- app

#!! This project's targets

STARTUP_TARGETS := build \
	install-hooks

ifeq ($(and $(wildcard $(CURDIR)/vendor),$(wildcard $(CURDIR)/node_modules)),)
bootstrap: startup
endif

include ./conf/Makefile

#--- release

ARCHIVE_EXTRA_PATHS := ./conf/Makefile \
	./conf/Makefile.dist \
	./conf/actionlint.dist.yaml \
	./conf/ai/conventions/ \
	./conf/ai/make.md \
	./conf/ai/mate/INSTRUCTIONS.md \
	./conf/ai/mate/config.php \
	./conf/ai/mate/skills/ \
	./conf/claude-settings.dist.json \
	./conf/editorconfig.dist \
	./conf/gitattributes.dist \
	./conf/hadolint.dist.yaml \
	./conf/launch.dist.jsonc \
	./conf/launch.php.dist.jsonc \
	./conf/php-cs-fixer.dist.php \
	./conf/phpstan/ \
	./conf/phpstan.dist.php \
	./conf/phpunit.dist.xml \
	./conf/rector.dist.php \
	./conf/semgrep.dist.yaml \
	./conf/shellcheckrc.dist \
	./conf/spelling/ \
	./conf/twig-cs-fixer.dist.php \
	./conf/vscode-extensions.dist.jsonc \
	./conf/vscode-extensions.php.dist.jsonc \
	./conf/vscode-settings.dist.jsonc \
	./conf/vscode-settings.php.dist.jsonc#vvv

#---vvv changelog

CHANGELOG_NAMES += editor-url=EditorUrl \
	file-finder=FileFinder

#---vv test

PHP_UNIT_MIN_COVERAGE_CLASSES  := 8.33
PHP_UNIT_MIN_COVERAGE_METHODS  := 50.06
PHP_UNIT_MIN_COVERAGE_LINES    := 63.90
VITEST_MIN_COVERAGE_BRANCHES   := 68.74
VITEST_MIN_COVERAGE_FUNCTIONS  := 88.01
VITEST_MIN_COVERAGE_LINES      := 86.88
VITEST_MIN_COVERAGE_STATEMENTS := 86.61

PHP_UNIT_EXCLUDED_GROUPS = $(strip $(if $(wildcard $(CURDIR)/dist),,build) \
	$(if $(filter 3.%,$(MAKE_VERSION)),make4) \
	$(patsubst python3,python,$(patsubst script,tty,$(filter-out $(notdir $(shell \
		export $(_HOST_ENVIRONMENT); $(RUN) sh -c 'for program in python3 script shellcheck; do command -v "$$program" || :; done' 2>/dev/null \
	)),python3 script shellcheck))))#vv #~~ test groups where the tools run cannot run, left out of this project's own runs

PHP_UNIT_FLAGS += --parallel --processes=2

#---vv tools

COMPOSER := $(if $(wildcard $(CURDIR)/vendor/autoload.php),$(PHP) $(APP_DIR)/scripts/composer.php,$(COMPOSER))
MV       := mv#vvv #~~ path to `mv` binary

#---v container

COMPOSE_UP_FLAGS += --quiet-build

#--- build

typegen: #~~ regenerates the rule types the configs are built from
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) $(APP_DIR)/scripts/typegen.ts

build: typegen #~~ builds the `./dist/` this package publishes
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) tsdown --config $(APP_DIR)/conf/tsdown.ts $(ARGS)

watch: #~~ rebuilds `./dist/` as the sources change
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) tsdown --config $(APP_DIR)/conf/tsdown.ts --watch $(ARGS)

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

_TABLE_COLORS := $(filter-out $(COLOR_NORMAL),$(_COLORS))

colors: _MODIFIER_COLUMN_WIDTH = $(shell printf '$(_MODIFIER_COLUMNS)' | $(AWK) '{ \
	max = 0; \
	for (i = 1; i <= NF; i += 1) { \
		if (length($$i) > max) \
			max = length($$i) \
		} \
		print max \
	}' \
)
colors: _COLOR_COLUMN_WIDTHS = $(foreach COLOR,$(_TABLE_COLORS),$(shell printf '$(COLOR)' | $(AWK) '{ print length($$0) }'))
colors: #~~ prints a table of all supported colors with combinations with all supported modifiers
	$(DEBUG_PREFIX)printf '%-$(_MODIFIER_COLUMN_WIDTH)s'
	$(DEBUG_PREFIX)$(foreach INDEX,$(call _get_indices,$(_TABLE_COLORS)), \
		$(eval _COLOR := $(word $(INDEX),$(_TABLE_COLORS))) \
		$(eval _WIDTH := $(word $(INDEX),$(_COLOR_COLUMN_WIDTHS))) \
		printf ' %-$(_WIDTH)s' '$(_COLOR)'; \
	)
	$(DEBUG_PREFIX)printf '\n'
	$(DEBUG_PREFIX)printf '%-$(_MODIFIER_COLUMN_WIDTH)s ' $(call _str_repeat,-,$(_MODIFIER_COLUMN_WIDTH))
	$(DEBUG_PREFIX)$(foreach INDEX,$(call _get_indices,$(_TABLE_COLORS)), \
		$(eval _WIDTH := $(word $(INDEX),$(_COLOR_COLUMN_WIDTHS))) \
		printf '%-*s ' $(_WIDTH) $(call _str_repeat,-,$(_WIDTH)); \
	)
	$(DEBUG_PREFIX)printf '\n'
	$(DEBUG_PREFIX)$(foreach MODIFIER_COLUMN,$(_MODIFIER_COLUMNS), \
		printf '%-$(_MODIFIER_COLUMN_WIDTH)s ' '$(subst +, ,$(MODIFIER_COLUMN))'; \
		$(foreach INDEX,$(call _get_indices,$(_TABLE_COLORS)), \
			$(eval _COLOR := $(word $(INDEX),$(_TABLE_COLORS))) \
			$(eval _WIDTH := $(word $(INDEX),$(_COLOR_COLUMN_WIDTHS))) \
			$(eval _TEXT := $(call text,$(_COLOR),$(_COLOR),$(subst +, ,$(MODIFIER_COLUMN)))) \
			printf '%-*b ' $(_WIDTH) '$(_TEXT)'; \
		) \
		printf '\n'; \
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
$(if $(filter 1,$2),$1,$1$(call _str_repeat,$1,$(shell printf $$(($2 - 1)))))
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
