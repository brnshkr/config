include ../../../../../conf/Makefile

#-- spinner

#!! Makefile of a project whose recipe ends in an `exit` of its own

spin: #~~ runs a command that exits itself behind the spinner
	$(DEBUG_PREFIX)$(call _spinner,$(PRINTF) 'ran\n'; exit 0)
