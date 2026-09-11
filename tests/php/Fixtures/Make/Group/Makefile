include ../../../../../conf/Makefile

#-- group

#!! Makefile of a project grouping the findings a tool reported

group-pairs group-lists group-text: export _GROUP_AWK = $(_GROUP_AWK_SOURCE)

group-pairs: #~~ groups findings that carry a message
	@$(call _group,cat findings.json,identifier,message)

group-lists: #~~ groups findings that are named in a list
	@$(call _group,cat applied.json,appliedFixers)

group-text: #~~ groups findings a compiler reported as text
	@$(call _group,cat errors.txt)
