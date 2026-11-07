include ../../../../../conf/Makefile

#-- parity

#!! Makefile printing what the environment files resolved to

values: #~~ prints each variable `NAMES` lists
	@$(foreach NAME,$(NAMES),printf '%s=[%s]\n' '$(NAME)' "$$$(NAME)";)
