include ../../../../../conf/Makefile

#-- container

#!! Makefile of a project whose tools run in a container

where: #~~ says where make runs and where a tool runs
	@echo "make=$$([ -f /.dockerenv ] && echo container || echo host)"
	@$(RUN) sh -c 'echo "tool=$$([ -f /.dockerenv ] && echo container || echo host)"'

where-alt: #~~ says where a tool named for another service runs
	@$(call run_in,alt) sh -c 'echo "alt=$$([ -f /.dockerenv ] && echo container || echo host)"'

show: #~~ prints what a tool sees of the environment
	@$(RUN) sh -c 'echo "stage=$$APP_ENV"; echo "stage-file=$$CONTAINER_FIXTURE_STAGE_FILE"; \
		echo "declared=$$CONTAINER_FIXTURE_DECLARED"; echo "undeclared=$$CONTAINER_FIXTURE_UNDECLARED"; \
		echo "editor=$$EDITOR_URL"; echo "where=$$(pwd)"'

detect: #~~ prints whether make believes it runs in a container
	@echo "in-container=$(IS_IN_CONTAINER)"
