include ../../../../../conf/Makefile

#-- group

#!! Makefile of a project grouping the findings a tool reported

group-pairs group-lists group-text group-eslint group-single group-wide group-braces group-clean group-failing group-crash: export _GROUP_AWK = $(_GROUP_AWK_SOURCE)

group-pairs: #~~ groups findings that carry a message
	@$(call _group,cat findings.json,identifier,message)

group-lists: #~~ groups findings that are named in a list
	@$(call _group,cat applied.json,appliedFixers)

group-text: #~~ groups findings a compiler reported as text
	@$(call _group,cat errors.txt)

group-eslint: #~~ groups findings from a report that also lists suppressed ones
	@$(call _group,cat eslint.json,ruleId,message)

group-single: #~~ groups a report with exactly one finding
	@$(call _group,cat single.json,identifier,message)

group-wide: #~~ groups findings whose total is wider than any count
	@$(call _group,cat wide.json,identifier,message)

group-braces: #~~ groups findings whose messages contain braces
	@$(call _group,cat braces.json,identifier,message)

group-clean: #~~ groups a report with no findings
	@$(call _group,cat clean.json,ruleId,message)

group-failing: #~~ groups a tool that exits nonzero because it has findings
	@$(call _group,sh -c 'cat findings.json; exit 1',identifier,message)

group-crash: #~~ groups a tool that failed without reporting anything
	@$(call _group,sh -c 'echo crashed; exit 3',ruleId,message)
