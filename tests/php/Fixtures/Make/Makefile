#-- app

#!! Application Makefile of the test fixture
#!! Covers all make help AWK features for snapshot testing
#!! shown at verbosity >= 2 #vv

#--- commands

root-makefile-command: #~~ command from root Makefile auto-include

hidden-command: #~~ shown at verbosity >= 1 #v

multi-a multi-b: #~~ multi-target command (appears joined with /)

no-desc-command:

.dot-command: #~~ dot-prefixed commands are skipped by the parser

_under-command: #~~ underscore-prefixed commands are skipped by the parser

#---v verbose-group

verbose-command: #~~ shown at verbosity >= 1

verbose-hidden-command: #~~ shown at verbosity >= 2 #vv

#---vv very-verbose-group

very-verbose-command: #~~ shown at verbosity >= 2
very-hidden-command: #~~ shown at verbosity >= 3 (scope v2 + entry v3 marker) #vvv

#---- very-verbose-sub

deep-command: #~~ depth-2 subsection (inherits vv verbosity from parent scope)

#----- depth-three

depth-three-command: #~~ depth-3 subsection

#------ depth-four

depth-four-command: #~~ depth-4 subsection (arbitrary nesting depth)

#---vvv ultra-verbose-group

ultra-verbose-command: #~~ shown at verbosity >= 3
ultra-hidden-command: #~~ shown at verbosity >= 4 (scope v3 + entry v4 marker) #vvvv

#---vvvv hyper-verbose-group

hyper-verbose-command: #~~ shown at verbosity >= 4 (arbitrary depth)

#--- variables

BASE_URL ?= https://example.com#~~ base URL for the application
TIMEOUT  := 30#~~ connection timeout in seconds
RETRIES  += 3#vv #~~ retry count

UNDESCRIBED ?= raw-value

CONTINUATION_VARIABLE ?= first \
	second \
	third#~~ value assembled from continuation lines

_PRIVATE_VARIABLE := should-not-appear

ifndef IFNDEF_VARIABLE
IFNDEF_VARIABLE ?= default-value#~~ variable in ifndef block (operator always shown as ?=)
endif

ifndef IFNDEF_MACRO_VARIABLE
define IFNDEF_MACRO_VARIABLE #~~ macro variable inside ifndef shows ?= operator
ifndef-macro-value
endef
endif

ifdef IS_DEFINED
IFDEF_VARIABLE := from-ifdef-block#~~ variable inside ifdef (shown only when IS_DEFINED is set)
endif

ifdef NEVER_DEFINED
NEVER_SHOWN := should-not-appear#~~ skipped — NEVER_DEFINED never set
endif

ifeq ($(ENV),prod)
IFEQ_VARIABLE     := prod-value#~~ variable inside ifeq block
DEDUP_VARIABLE    := branch-a#~~ appears once despite being in multiple branches
else
IFEQ_ELSE_VARIABLE := else-value#~~ variable from else branch (dedup: DEDUP_VARIABLE skipped here)
DEDUP_VARIABLE     := branch-b#~~ deduplicated — first branch wins, this is dropped
endif

ifeq ($(LANG),de)
LANG_GREETING := Hallo#~~ greeting selected by else-ifeq chain (de branch)
else ifeq ($(LANG),fr)
LANG_GREETING := Bonjour#~~ greeting selected by else-ifeq chain (fr branch)
else
LANG_GREETING := Hello#~~ greeting selected by else-ifeq chain (default branch)
endif

ifeq "a" "a"
QUOTED_DOUBLE_VARIABLE := matched#~~ quoted condition form (double-quoted operands)
endif

ifeq '${ENV}' 'prod'
QUOTED_BRACE_VARIABLE := prod#~~ quoted condition with brace expansion form
else
QUOTED_BRACE_VARIABLE := default#~~ quoted condition fallthrough (brace expansion form)
endif

ifeq ($(ENV),prod)
SEQUENTIAL_FIRST_VARIABLE := alpha#~~ first of two sequential ifeq blocks (prod branch)
else
SEQUENTIAL_FIRST_VARIABLE := beta#~~ first of two sequential ifeq blocks (default branch)
endif
ifeq ($(LANG),de)
SEQUENTIAL_SECOND_VARIABLE := gamma#~~ second of two sequential ifeq blocks (de branch)
else
SEQUENTIAL_SECOND_VARIABLE := delta#~~ second of two sequential ifeq blocks (default branch)
endif

ifdef IS_DEFINED
ELSE_IFDEF_VARIABLE := from-ifdef-branch#~~ value from else-ifdef chain (primary branch)
else ifdef LANG
ELSE_IFDEF_VARIABLE := from-else-ifdef-branch#~~ value from else-ifdef chain (LANG branch)
else
ELSE_IFDEF_VARIABLE := from-else-branch#~~ value from else-ifdef chain (default branch)
endif

ifndef NEVER_DEFINED
ELSE_IFNDEF_VARIABLE := from-ifndef-branch#~~ value from else-ifndef chain (primary branch)
else ifndef LANG
ELSE_IFNDEF_VARIABLE := from-else-ifndef-branch#~~ value from else-ifndef chain (else branch)
endif

ifeq ($(call resolve_env),prod)
NESTED_CALL_VARIABLE := nested-prod#~~ ifeq with $(call f) on lhs (exercises paren-depth tracking)
else
NESTED_CALL_VARIABLE := nested-default#~~ ifeq with $(call f) on lhs (default branch)
endif

#---v advanced

#**
#* Resolves the environment name from context.
#* Defaults to dev when ENV is not set.
#*
#* This paragraph only renders at vvv verbosity.
#*
#* usage:
#*   $(call resolve_env)
#*
#* returns: string
#*
define resolve_env
$(if $(ENV),$(ENV),dev)
endef

define no_docs_fn #~~ function without a documentation block
$(PRINTF) 'no docs'
endef

#**
#* Documentation block whose first sentence would be derived as a description
#* but is overridden by the inline #~~ tag below.
#*
define documented_and_described_fn #~~ inline description overrides the derived one
$(PRINTF) 'overridden'
endef

define undescribed_fn
$(PRINTF) 'no docs, no description'
endef

define verbose_described_fn #~~ described function shown at verbosity >= 1 (via scope)
$(PRINTF) 'verbose-described'
endef

define _private_fn
$(PRINTF) 'private - should not appear'
endef

define UPPER_MACRO #~~ define-based macro variable
upper-macro-value
endef

define VERBOSE_MACRO #~~ macro variable shown at verbosity >= 2 #vv
vv-macro-value
endef
