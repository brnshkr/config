include ../../../../../conf/Makefile

#-- coverage

#!! Makefile of a project that checks a coverage report

coverage-reached: #~~ passes when the report reaches the floor
	@$(call _require_coverage,report.txt,Lines,90,COVERAGE_FILE)

coverage-missed: #~~ fails when the report falls short
	@$(call _require_coverage,report.txt,Lines,100,COVERAGE_FILE)

coverage-absent: #~~ fails when there is no report at all
	@$(call _require_coverage,none.txt,Lines,90,COVERAGE_FILE)

coverage-metric: #~~ reads the metric it is given rather than the lines
	@$(call _require_coverage,report.txt,Methods,95,COVERAGE_FILE)
