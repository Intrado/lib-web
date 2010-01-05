
-- $rev 3


-- after we create message group entries, we need to update permanent flag BEFORE deleting from the message
update messagegroup mg set mg.permanent = exists (select * from message m where m.messagegroupid = mg.id and m.permanent)
$$$

update messagegroup mg set mg.deleted = not exists (select * from message m where m.messagegroupid = mg.id and not m.deleted)
$$$

ALTER TABLE `message` DROP `permanent`
$$$

ALTER TABLE `message` DROP `lastused`
$$$

ALTER TABLE `listentry` DROP `typeold`
$$$

-- set language codes on messages
-- start by linking to any specifically mapped language via a job
update message m
	inner join messagegroup mg on (m.messagegroupid=mg.id)
	inner join job j on (mg.id=j.messagegroupid)
	inner join joblanguage jl on (jl.jobid=j.id)
	inner join language l on (l.name = jl.language and (m.id=jl.messageid or m.originalid=jl.messageid))
	set m.languagecode = l.code
	where m.languagecode is null
$$$

alter table message drop originalid
$$$

-- assume that if any defined language shows up in a message name then that message belongs to that language
update language l
inner join message m on (m.name like concat('%',l.name,'%'))
set m.languagecode = l.code
where m.languagecode is null
and messagegroupid is not null
$$$

-- any remaining messages default to english
update message set languagecode = 'en' where languagecode is null and messagegroupid is not null
$$$

