-- $rev 1

-- add dm notes
ALTER TABLE `custdm` ADD `notes` TEXT;
$$$

-- $rev 2

-- Add categories for alerts to seperate customer and manager alerts and any future category needs
CREATE TABLE `importalertcategory` (
    `id` int(11) NOT NULL auto_increment,
    `name` varchar(50) NOT NULL,
    `emails` TEXT,
 	 PRIMARY KEY  (`id`)
) ENGINE = InnoDB;
$$$

-- New table to keep track of import alert rules instead of a json encoding string
CREATE TABLE `importalertrule` (
	`id` int(11) NOT NULL auto_increment,
	`importid` INT NOT NULL,
	`categoryid` INT NOT NULL,
	`name` varchar(50) NOT NULL,
    `operation` enum('eq','ne','gt','lt') NOT NULL,
    `testvalue` INT NOT NULL,
    `daysofweek` varchar(20) NOT NULL,
 	 PRIMARY KEY  (`id`)
) ENGINE = InnoDB;
$$$

-- Add field for netsuite integration   
ALTER TABLE `import` ADD `nsticketid` VARCHAR( 50 ) NOT NULL default '';
$$$

-- Add notes field that is not visible to the customer 
ALTER TABLE `import` ADD `managernotes` TEXT;
$$$

-- $rev 3

-- Add notes data length field to avoid calling length(data) on import reports
ALTER TABLE `import` ADD `datalength` int(11) NOT NULL DEFAULT 0 AFTER `data` 
$$$

INSERT INTO `importalertcategory` (`name`) VALUES
	('manager'),
	('customer')
$$$

-- $rev 4

-- case insensitive user logins
ALTER TABLE `user` CHANGE `login` `login` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL 
$$$

-- fix myisam jobpost tables
ALTER TABLE `jobpost` ENGINE = InnoDB
$$$

-- $rev 5
-- empty rev - use php to set _customerenabled setting

-- $rev 6
-- remove deleted flags from message and messageattachment
ALTER TABLE `message` DROP `deleted`
$$$

ALTER TABLE `messageattachment` DROP `deleted`
$$$

-- $rev 7
-- remove originalmessagegroupid field from messagegroup table (No loner used anywhere)
ALTER TABLE `messagegroup` DROP `originalmessagegroupid`
$$$

-- $rev 8
ALTER TABLE `import` DROP `nsticketid`

-- $rev 9
ALTER TABLE `importalertcategory` CHANGE `name` `type` ENUM( 'manager', 'customer' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL

-- $rev 10
-- note, rev 10 may be skipped if 8.0/6 was applied
ALTER TABLE `job` ADD `activedate` DATETIME default NULL AFTER `modifydate` 
$$$

CREATE TABLE `jobstats` (
 `jobid` int(11) NOT NULL,
 `name` varchar(255) NOT NULL,
 `value` int(11) NOT NULL,
 PRIMARY KEY (`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 11
UPDATE import SET datalength=length(data)
$$$
