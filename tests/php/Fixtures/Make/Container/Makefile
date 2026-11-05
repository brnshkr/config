include ../../../../../conf/Makefile

#-- container

#!! Makefile of a project whose tools run in a container

where: #~~ says where make runs and where a tool runs
	@echo "make=$(if $(IS_IN_CONTAINER),container,host)"
	@$(RUN) sh -c 'echo "tool=$$([ -n "$$IS_IN_CONTAINER" ] && echo container || echo host)"'

terminal: #~~ says whether a tool writes to a terminal
	@$(RUN) sh -c '[ -t 1 ] && echo tool=terminal || echo tool=pipe'

where-alt: #~~ says where a tool named for another service runs
	@$(call run_in,alt) sh -c 'echo "alt=$$([ -n "$$IS_IN_CONTAINER" ] && echo container || echo host)"'

show: #~~ prints what a tool sees of the environment
	@echo "app-dir=$(APP_DIR)"
	@$(RUN) sh -c 'echo "stage=$$APP_ENV"; echo "stage-file=$$CONTAINER_FIXTURE_STAGE_FILE"; \
		echo "declared=$$CONTAINER_FIXTURE_DECLARED"; echo "undeclared=$$CONTAINER_FIXTURE_UNDECLARED"; \
		echo "editor=$$EDITOR_URL"'

detect: #~~ prints whether make believes it runs in a container
	@echo "in-container=$(IS_IN_CONTAINER)"
