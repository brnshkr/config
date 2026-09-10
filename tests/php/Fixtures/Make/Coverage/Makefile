include ../../../../../conf/Makefile

#-- coverage

#!! Makefile of a project that checks a coverage report

coverage-reached: #~~ passes when the report reaches the floor
	@$(call _require_coverage,report.txt,90,COVERAGE_FILE)

coverage-missed: #~~ fails when the report falls short
	@$(call _require_coverage,report.txt,100,COVERAGE_FILE)

coverage-absent: #~~ fails when there is no report at all
	@$(call _require_coverage,none.txt,90,COVERAGE_FILE)
