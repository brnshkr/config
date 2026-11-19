include ../../../../../conf/Makefile

#-- gates

#!! Makefile whose conditionals the target scrape has to evaluate for itself

ifneq ($(wildcard $(CURDIR)/present-one $(CURDIR)/absent),)
gate-any-present:
	@echo gate-any-present
endif

ifneq ($(wildcard $(CURDIR)/absent),)
gate-none-present:
	@echo gate-none-present
endif

ifneq ($(wildcard $(CURDIR)/present-one),)
ifneq ($(wildcard $(CURDIR)/present-two),)
gate-nested-present:
	@echo gate-nested-present
endif

ifneq ($(wildcard $(CURDIR)/absent),)
gate-nested-absent:
	@echo gate-nested-absent
endif
endif

ifneq ($(wildcard $(CURDIR)/odd@name+here),)
gate-odd-characters:
	@echo gate-odd-characters
endif

ifeq ($(GATE),on)
gate-ifeq-taken:
	@echo gate-ifeq-taken
else
gate-ifeq-other:
	@echo gate-ifeq-other
endif
