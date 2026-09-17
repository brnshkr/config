#-- app

#!! Application Makefile of the `brnshkr/config` package

STARTUP_TARGETS := build \
	install-hooks

include ./conf/Makefile

ARCHIVE_EXTRA_PATHS := ./conf/.gitignore.dist \
	./conf/Makefile \
	./conf/Makefile.dist \
	./conf/editorconfig.dist \
	./conf/gitattributes.dist \
	./conf/launch.dist.json \
	./conf/php-cs-fixer.dist.php \
	./conf/phpstan/ \
	./conf/phpstan.dist.php \
	./conf/phpunit.dist.xml \
	./conf/rector.dist.php \
	./conf/spelling/ \
	./conf/twig-cs-fixer.dist.php \
	./conf/vscode-css-custom-data.dist.json \
	./conf/vscode-extensions.dist.json \
	./conf/vscode-settings.dist.jsonc

PHP_UNIT_MIN_COVERAGE := 0
VITEST_MIN_COVERAGE   := 0

export VITE_CONFIG_NATIVE_IGNORE_WARNING := true

#---vv tools

COMPOSER := ./scripts/composer.php
MV       := mv#vvv #~~ path to `mv` binary

#--- build

typegen: #~~ regenerates the rule types the configs are built from
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) $(CURDIR)/scripts/typegen.ts

build: typegen #~~ builds the `./dist/` this package publishes
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) tsdown --config $(CURDIR)/conf/tsdown.ts $(ARGS)

watch: #~~ rebuilds `./dist/` as the sources change
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) tsdown --config $(CURDIR)/conf/tsdown.ts --watch $(ARGS)

#--- inspect

# NOTICE: Runs on Node since Bun truncates ESLint's piped output
inspect-eslint: #~~ runs `eslint-config-inspector`
	$(DEBUG_PREFIX)$(BUN) eslint-config-inspector --config $(CURDIR)/conf/eslint.ts $(ARGS)

inspect-eslint-stats: #~~ runs `eslint-config-inspector` and times every rule with a full lint on startup #v
	$(DEBUG_PREFIX)$(MAKE) $(_MAKE_FLAGS) inspect-eslint -- --stats $(ARGS)

inspect-modules: #~~ runs `node-modules-inspector`
	$(DEBUG_PREFIX)$(BUN) $(BUN_FLAGS) node-modules-inspector $(ARGS)

#--- mate

MATE             := $(CURDIR)/vendor/bin/mate
_MATE_EXTENSIONS := $(CURDIR)/mate/extensions.php

discover: #~~ runs mate discover
	$(DEBUG_PREFIX)$(MATE) discover $(ARGS)
	$(DEBUG_PREFIX)if [ ! -f $(_MATE_EXTENSIONS) ]; then \
		$(call log,No `%s` to post-process.,$(COLOR_NOTICE),'$(call _named_path,$(_MATE_EXTENSIONS))'); \
	else \
		$(MAKE) --no-print-directory php-cs-fixer $(_MATE_EXTENSIONS) \
			&& $(AWK) '\
				/This file is managed by/,/^$$/ { next } \
				/^\/\*\*$$/,/^ \*\/$$/ { next } \
				/^return/ { print "/**\n * @internal\n */" } \
				1 \
			' $(_MATE_EXTENSIONS) > $(_MATE_EXTENSIONS).tmp \
			&& $(MV) $(_MATE_EXTENSIONS).tmp $(_MATE_EXTENSIONS) \
			&& $(call log,Discovery finished.,$(COLOR_SUCCESS)); \
	fi

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

_MODIFIER_COLUMN_WIDTH = $(shell $(PRINTF) '$(_MODIFIER_COLUMNS)' | $(AWK) '{ \
	max = 0; \
	for (i = 1; i <= NF; i += 1) { \
		if (length($$i) > max) \
			max = length($$i) \
		} \
		print max \
	}' \
)

_TABLE_COLORS        := $(filter-out $(COLOR_NORMAL),$(_COLORS))
_COLOR_COLUMN_WIDTHS  = $(foreach COLOR,$(_TABLE_COLORS),$(shell $(PRINTF) '$(COLOR)' | $(AWK) '{ print length($$0) }'))

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

#-- helpers

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
