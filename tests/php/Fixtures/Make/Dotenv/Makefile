include ../../../../../conf/Makefile

#-- dotenv

#!! Makefile of a project whose environment files the shared one loads

dotenv-show: #~~ prints every value the environment files resolved to
	@env \
		| $(AWK) '/^[A-Za-z_][A-Za-z0-9_]*=/ { if (record != "") print record; record = $$0; next } { record = record "\\n" $$0 } END { if (record != "") print record }' \
		| { grep -E '^(DOTENV_FIXTURE_|APP_ENV=|FIXTURE_ENV=)' || true; } \
		| LC_ALL=C sort
