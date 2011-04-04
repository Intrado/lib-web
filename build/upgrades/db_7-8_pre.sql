-- $rev 1

ALTER TABLE `user` ADD `salt` VARCHAR( 29 ) NOT NULL AFTER `password` ,
ADD `passwordversion` TINYINT NOT NULL AFTER `salt` 
$$$

ALTER TABLE `subscriber` ADD `salt` VARCHAR( 29 ) NOT NULL AFTER `password` ,
ADD `passwordversion` TINYINT NOT NULL AFTER `salt` 
$$$

-- $rev 2

CREATE TABLE `template` (
 `type` varchar(20) NOT NULL,
 `messagegroupid` int(11) NOT NULL,
 PRIMARY KEY  (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `messagegroup` CHANGE `type` `type` ENUM( 'notification', 'targetedmessage', 'classroomtemplate', 'systemtemplate' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'notification'
$$$

-- $rev 3

-- $rev 4

-- $rev 5

-- $rev 6

ALTER TABLE `messagegroup` CHANGE `userid` `userid` INT( 11 ) NULL DEFAULT NULL
$$$

ALTER TABLE `message` CHANGE `userid` `userid` INT( 11 ) NULL DEFAULT NULL
$$$

-- $rev 7

ALTER TABLE `reportperson` DROP `messageid`
$$$

