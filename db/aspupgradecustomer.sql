-- Upgrade from release 7.0 to 7.1

-- Set a permanent flag for messages and audiofiles that should never be deleted
ALTER TABLE `message` ADD `permanent` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `deleted` 
$$$
ALTER TABLE `audiofile` ADD `permanent` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `deleted`
$$$

-- Make all messages that currently exist and are not deleted and are not easily identifyable as easycalls or inbound permanent
Update message set permanent = 1
where not deleted
	and name not like 'EasyCall - %'
	and name not like 'Call In %'
$$$

-- create an index on audiofileid for the next two queries
ALTER TABLE `messagepart` ADD INDEX ( `audiofileid` )
$$$

-- update all audiofiles for permanent messages and set them permanent
update message m
straight_join messagepart mp on (mp.messageid = m.id)
straight_join audiofile af on (af.id = mp.audiofileid)
set af.permanent = 1
where m.permanent
$$$

-- update all audiofiles not associated with any messageparts to permanent
update audiofile af
left join messagepart mp on af.id = mp.audiofileid
set af.permanent = 1
where not af.deleted
and mp.audiofileid is null
$$$

-- remove the index on audiofileid
ALTER TABLE `messagepart` DROP INDEX `audiofileid`
$$$
 
-- create table for archived report name to content id mappings
 CREATE TABLE `c_1`.`reportarchive` (
`name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`contentid` BIGINT( 20 ) NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
$$$
