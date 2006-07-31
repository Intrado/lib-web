



-- this adds the systempriority field
ALTER TABLE `jobworkitem` ADD `systempriority` TINYINT NOT NULL DEFAULT '3' AFTER `priority` ;


-- drop a now useless index, and add a nice one
ALTER TABLE `jobworkitem` DROP INDEX `priority` ;
ALTER TABLE `jobworkitem` DROP INDEX `assign` ,
ADD INDEX `assign` ( `status` , `type` , `systempriority` , `priority` ) 


-- this will get some work items that are the highest priority by system priority then customer priority (using A and C from the SRS). it won't evenly distribute between customers yet.
-- very optimized, and shouldn't take much time at all.

select jobworkitem.jobid,jobworkitem.id,jobtask.id,renderedmessage.content
from jobworkitem, jobtask, renderedmessage
where jobtask.jobworkitemid=jobworkitem.id and renderedmessage.id=jobtask.renderedmessageid
and jobtask.sequence=0

and jobworkitem.type='phone' 
and jobworkitem.status='queued'
and jobworkitem.systempriority=
	(select min(systempriority) from jobworkitem where status='queued' and type='phone')
and jobworkitem.priority=
	(select min(priority) from jobworkitem where status='queued' and type='phone' and systempriority=
		(select min(systempriority) from jobworkitem where status='queued' and type='phone'))
limit 1000


-- be sure to update the workitems to assigned before unlocking the tables with

update jobworkitem set status='assigned' where id in (...)

ALTER TABLE `jobtask` ADD INDEX `waiting` ( `id` , `nextattempt` ) 