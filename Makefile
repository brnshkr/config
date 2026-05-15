include ./conf/Makefile

#-- app

#!! Application Makefile of the brnshkr/config package

#---v general

GREP  := grep
MKDIR := mkdir
MV    := mv
RM    := rm
RSYNC := rsync
TAR   := tar

#---vv tools
BUN      := bun
COMPOSER := composer

#---vvv constants

SEMVER_REGEX := (0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?(\+[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?#vvv

#--- phpunit

PHP_UNIT        := $(PWD)/vendor/bin/pest
PHP_UNIT_CONFIG := $(PWD)/conf/phpunit.dist.xml
PHP_UNIT_FLAGS  := $(if $(DEBUG),--debug)

cc: #~~ removes the ./cache directory
	$(DEBUG_PREFIX)$(RM) -rf $(PWD)/.cache

#--- test

test: #~~ runs tests
	$(DEBUG_PREFIX)$(PHP_UNIT) --configuration $(PHP_UNIT_CONFIG) $(PHP_UNIT_FLAGS) $(ARGS)

test-update: #~~ runs tests with snapshot update
	$(DEBUG_PREFIX)$(PHP_UNIT) --configuration $(PHP_UNIT_CONFIG) $(PHP_UNIT_FLAGS) --update-snapshots $(ARGS)

check: rector php-cs-fixer twig-cs-fixer phpstan test #~~ runs rector, php-cs-fixer, twig-cs-fixer, phpstan and phpunit

#--- package

pack: bun-pack composer-pack #~~ runs composer-pack and bun-pack

#----vv bun

bun-list: #~~ lists included bun package files
	$(DEBUG_PREFIX)archive_file=$$($(BUN) pm pack 2>&1 | grep -oE -m1 '$(VENDOR)-$(PACKAGE)-$(SEMVER_REGEX)\.tgz') \
		&& $(TAR) -tf "$$archive_file" | sed 's/^package\///' \
		&& $(RM) -f "$$archive_file"

bun-pack: #~~ publishes the bun package to ./.local/@<VENDOR>/<PACKAGE>
	$(DEBUG_PREFIX)archive_file=$$($(BUN) pm pack 2>&1 | grep -oE -m1 '$(VENDOR)-$(PACKAGE)-$(SEMVER_REGEX)\.tgz') \
		&& $(RM) -rf $(PWD)/.local/@$(VENDOR)/$(PACKAGE) \
		&& $(MKDIR) -p $(PWD)/.local/@$(VENDOR)/$(PACKAGE) \
		&& $(TAR) -xzf "$$archive_file" \
		&& $(RM) -f "$$archive_file" \
		&& $(RSYNC) -a ./package/ $(PWD)/.local/@$(VENDOR)/$(PACKAGE) \
		&& $(RM) -rf ./package

#----vv composer

composer-list: #~~ lists included composer package files
	$(DEBUG_PREFIX)archive_file=$$($(COMPOSER) archive 2>&1 | $(GREP) -oE -m1 '$(VENDOR)-$(PACKAGE)-$(SEMVER_REGEX)\.tar') \
		&& $(TAR) -tf "$$archive_file" \
		&& $(RM) -f "$$archive_file"

composer-pack: #~~ publishes the composer package to ./.local/<VENDOR>/<PACKAGE>
	$(DEBUG_PREFIX)archive_file=$$($(COMPOSER) archive 2>&1 | $(GREP) -oE -m1 '$(VENDOR)-$(PACKAGE)-$(SEMVER_REGEX)\.tar') \
		&& $(RM) -rf $(PWD)/.local/$(VENDOR)/$(PACKAGE) \
		&& $(MKDIR) -p $(PWD)/.local/$(VENDOR)/$(PACKAGE) \
		&& $(MV) "$$archive_file" $(PWD)/.local/$(VENDOR)/$(PACKAGE)/ \
		&& $(TAR) -C $(PWD)/.local/$(VENDOR)/$(PACKAGE) -xf $(PWD)/.local/$(VENDOR)/$(PACKAGE)/"$$archive_file" \
		&& $(RM) -f $(PWD)/.local/$(VENDOR)/$(PACKAGE)/"$$archive_file"

#---vvv debug

_MODIFIER_COLUMNS := $(EMPTY)

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

_MODIFIER_COLUMN_WIDTH := $(shell $(_PRINTF) '$(_MODIFIER_COLUMNS)' | $(_AWK) '{ \
	max = 0; \
	for (i = 1; i <= NF; i += 1) { \
		if (length($$i) > max) \
			max = length($$i); \
		} \
		print max \
	}' \
)

_TABLE_COLORS        := $(filter-out $(COLOR_NORMAL),$(_COLORS))
_COLOR_COLUMN_WIDTHS := $(foreach COLOR,$(_TABLE_COLORS),$(shell $(_PRINTF) '$(COLOR)' | $(_AWK) '{ print length($$0) }'))

colors: #~~ prints a table of all supported colors with combinations with all supported modifiers
	$(DEBUG_PREFIX)$(PRINTF) '%-$(_MODIFIER_COLUMN_WIDTH)s';
	$(DEBUG_PREFIX)$(foreach INDEX,$(call _get_indices,$(_TABLE_COLORS)), \
		$(eval _COLOR := $(word $(INDEX),$(_TABLE_COLORS))) \
		$(eval _WIDTH := $(word $(INDEX),$(_COLOR_COLUMN_WIDTHS))) \
		$(PRINTF) ' %-$(_WIDTH)s' '$(_COLOR)'; \
	)
	$(DEBUG_PREFIX)$(PRINTF) '\n';
	$(DEBUG_PREFIX)$(PRINTF) '%-$(_MODIFIER_COLUMN_WIDTH)s ' $(call _str_repeat,-,$(_MODIFIER_COLUMN_WIDTH))
	$(DEBUG_PREFIX)$(foreach INDEX,$(call _get_indices,$(_TABLE_COLORS)), \
		$(eval _WIDTH := $(word $(INDEX),$(_COLOR_COLUMN_WIDTHS))) \
		$(PRINTF) '%-*s ' $(_WIDTH) $(call _str_repeat,-,$(_WIDTH)); \
	)
	$(DEBUG_PREFIX)$(PRINTF) '\n';
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
# Recursively repeats a string a specified number of times.
#
# usage:
#  $(call _str_repeat,string,count)
# parameters:
#  string: string
#  count: int<1, max>
# returns: string
#**
define _str_repeat
$(if $(filter 1,$2),$1,$1$(call _str_repeat,$1,$(shell $(PRINTF) $$(($2 - 1)))))
endef

#**
# Recursively enumerates the indices of each word in a list.
#
# usage:
#  $(call _get_indices_internal,list)
# parameters:
#  list: List<1>
# returns: List<int<1,max>>
#**
define _get_indices_internal
$(if $1,$(words $1) $(call _get_indices_internal,$(wordlist 2,$(words $1),$1)))
endef

#**
# Returns a list of the 1-based indices of all words in a list.
#
# usage:
#  $(call _get_indices,list)
# parameters:
#  list: List<string>
# returns: List<int<1,max>>
#**
define _get_indices
$(sort $(call _get_indices_internal,$1))
endef
