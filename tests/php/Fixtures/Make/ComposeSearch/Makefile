APP_SERVICE :=

include ../../../../../conf/Makefile

#-- compose

#!! Makefile of a project whose Compose file sits outside its root

compose-files: #~~ prints the Compose files the container targets use
	@echo "compose-files=$(patsubst $(CURDIR)/%,%,$(COMPOSE_FILES))"
