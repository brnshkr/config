TEST_TARGETS := matrix-test

include ../../../../../conf/Makefile

COMPOSE_PROJECT_NAME  := acme
ACME_IMAGE            := ghcr.io/acme/app:1-dev@sha256:0000
TEST_MATRIX_IMAGE_KEY := ACME_IMAGE

TEST_MATRIX := tag[php8.4-bun1.3];composer[acme/*:^2.0];bun[@acme/ui:^3.1] \
	tag[php8.5-bun1.4];composer[*:lowest]

#-- matrix

#!! Makefile of a project testing against more than its locked dependencies

matrix-test: #~~ prints where a row runs
	$(DEBUG_PREFIX)echo "matrix-test on $($(TEST_MATRIX_IMAGE_KEY)) in $(COMPOSE_PROJECT_NAME) as $(APP_SERVICE_MODE)"
