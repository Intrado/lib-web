
-- $rev 1

INSERT into `joblist` (`jobid`, `listid`) SELECT `id`, `listid` from `job`
$$$

ALTER TABLE `job` DROP `listid`, DROP `thesql`
$$$

ALTER TABLE `joblist` DROP `thesql`
$$$

ALTER TABLE `custdm` CHANGE `poststatus` `poststatus` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `subscriber` CHANGE `preferences` `preferences` TEXT
$$$

ALTER TABLE `blockeddestination` ADD `failattempts` TINYINT( 4 ) NULL
$$$

ALTER TABLE `blockeddestination` ADD UNIQUE `typedestination` ( `type` , `destination` )
$$$

ALTER TABLE `blockeddestination` ADD `blockmethod` ENUM( 'manual', 'pending', 'autoblock' ) NOT NULL
$$$

ALTER TABLE `blockeddestination` ADD INDEX `methoddate` ( `blockmethod` , `createdate` )
$$$


-- $rev 2


ALTER TABLE `message` ADD `messagegroupid` INT( 11 ) NOT NULL AFTER `id` 
$$$

ALTER TABLE `audiofile` ADD `messagegroupid` INT( 11 ) DEFAULT NULL AFTER `id` 
$$$

ALTER TABLE `job` ADD `messagegroupid` INT( 11 ) NOT NULL AFTER `id` 
$$$

CREATE TABLE `event` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`userid` INT NOT NULL ,
	`organizationid` INT NOT NULL ,
	`sectionid` INT NULL ,
	`targetedmessageid` INT NULL ,
	`name` VARCHAR( 50 ) NOT NULL ,
	`notes` TEXT NOT NULL ,
	`occurence` DATETIME NOT NULL
) ENGINE = InnoDB
$$$

 CREATE TABLE `alert` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`eventid` INT NOT NULL ,
	`personid` INT NOT NULL ,
	`date` DATE NOT NULL ,
	`time` TIME NOT NULL
) ENGINE = InnoDB
$$$

CREATE TABLE `targetedmessage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messagekey` varchar(255) NOT NULL,
  `targetedmessagecategoryid` int(11) NOT NULL,
  `overridemessagegroupid` int(11) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB
$$$

CREATE TABLE `personassociation` (
  `personid` int(11) NOT NULL,
  `type` enum('organization','section','event') NOT NULL,
  `organizationid` int(11) DEFAULT NULL,
  `sectionid` int(11) DEFAULT NULL,
  `eventid` int(11) DEFAULT NULL,
  KEY `personid` (`personid`)
) ENGINE=InnoDB
$$$

CREATE TABLE `messagegroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL,
  `modified` datetime NOT NULL,
  `lastused` datetime DEFAULT NULL,
  `permanent` tinyint NOT NULL DEFAULT 1,
  `deleted` tinyint NOT NULL DEFAULT 0, 
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

