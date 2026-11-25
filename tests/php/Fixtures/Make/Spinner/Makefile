include ../../../../../conf/Makefile

#-- spinner

#!! Makefile of a project whose recipe ends in an `exit` of its own

spin: #~~ runs a command that exits itself behind the spinner
	$(TRACE_PREFIX)$(call _spinner,printf 'ran\n'; exit 0)

spin-until-interrupted: #~~ runs a command that waits for an interrupt behind the spinner
	$(TRACE_PREFIX)$(call _spinner,printf 'started\n'; sleep 10)
