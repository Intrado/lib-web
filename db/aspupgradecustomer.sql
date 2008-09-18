-- Upgrade from release 6.0.1 to 6.1 Sept 19, 2008


ALTER TABLE `user` ADD `aremail` TEXT NOT NULL AFTER `email`
$$$
UPDATE `user` set `aremail` = `email`
$$$
UPDATE `user` set `email` = ''
$$$
ALTER TABLE `user` CHANGE `email` `email` VARCHAR( 255 ) NOT NULL
$$$

ALTER TABLE `import` ADD `datatype` ENUM( 'person', 'user', 'enrollment' ) NOT NULL DEFAULT 'person' AFTER `type`,
CHANGE `updatemethod` `updatemethod` ENUM( 'updateonly', 'update', 'full', 'createonly' ) NOT NULL DEFAULT 'full'
$$$

ALTER TABLE `user` ADD `staffpkey` VARCHAR( 255 ) NULL,
ADD `importid` TINYINT NULL ,
ADD `lastimport` DATETIME NULL
$$$

CREATE TABLE `groupdata` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`personid` INT NOT NULL ,
`fieldnum` TINYINT NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL,
`importid` TINYINT NOT NULL,
KEY `personfield`  (`personid`,`fieldnum`),
KEY `importid` (`importid`)
) ENGINE = innodb
$$$

CREATE TABLE `reportgroupdata` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`jobid` INT NOT NULL,
`personid` INT NOT NULL ,
`fieldnum` TINYINT NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL,
KEY `jobpersonfield`  (`jobid`,`personid`,`fieldnum`)
) ENGINE = innodb
$$$

CREATE TABLE `enrollment` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`personid` INT NOT NULL ,
`c01` VARCHAR( 255 ) NOT NULL ,
`c02` VARCHAR( 255 ) NOT NULL ,
`c03` VARCHAR( 255 ) NOT NULL ,
`c04` VARCHAR( 255 ) NOT NULL ,
`c05` VARCHAR( 255 ) NOT NULL ,
`c06` VARCHAR( 255 ) NOT NULL ,
`c07` VARCHAR( 255 ) NOT NULL ,
`c08` VARCHAR( 255 ) NOT NULL ,
`c09` VARCHAR( 255 ) NOT NULL ,
`c10` VARCHAR( 255 ) NOT NULL,
KEY `personid` (`personid`),
KEY `staffid` (`c01`)
) ENGINE = innodb
$$$

ALTER TABLE `userrule` ADD INDEX ( `userid` )
$$$

ALTER TABLE `user` ADD INDEX ( `staffpkey` )
$$$

-- ---------------------------------------------------------------------
-- data changes (not just schema) from here on...

INSERT INTO `fieldmap` (`fieldnum`, `name`, `options`) VALUES ('c01', 'Staff ID', 'searchable,multisearch,staff')
$$$
