-- $rev 1
ALTER TABLE `bursttemplate` CHANGE `created` `createdtimestampms` BIGINT NULL DEFAULT NULL
$$$

-- $rev 2
ALTER TABLE `bursttemplate` 
  CHANGE `name` `name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
  CHANGE `x` `x` DOUBLE( 12, 8 ) NOT NULL , 
  CHANGE `y` `y` DOUBLE( 12, 8 ) NOT NULL ,
  CHANGE `pagesskipstart` `pagesskipstart` INT( 3 ) NOT NULL DEFAULT '0' ,
  CHANGE `pagesskipend` `pagesskipend` INT( 3 ) NOT NULL DEFAULT '0' ,
  CHANGE `pagesperreport` `pagesperreport` INT( 3 ) NOT NULL DEFAULT '1' ,
  CHANGE `createdtimestampms` `createdtimestampms` BIGINT( 20 ) NOT NULL
$$$

ALTER TABLE `burst` 
  CHANGE `contentid` `contentid` BIGINT( 20 ) NOT NULL ,
  CHANGE `bytes` `bytes` BIGINT( 20 ) NOT NULL ,
  ADD INDEX `userid` ( `userid` , `name` , `deleted` )
$$$

-- $rev 3
ALTER TABLE `burst`
  CHANGE `bytes` `size` BIGINT( 20 ) NOT NULL ,
  DROP `totalpagesfound`,
  DROP `actualreportscount`
$$$

-- $rev 4
ALTER TABLE `job` ADD `burstid` INT NULL DEFAULT NULL AFTER `questionnaireid`
$$$

-- $rev 5
ALTER TABLE `job` DROP `burstid`
$$$


-- $rev 6
CREATE TABLE IF NOT EXISTS `contentattachment` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`contentid` bigint(20) NOT NULL,
	`filename` varchar(255) NOT NULL,
	`size` int(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE IF NOT EXISTS `burstattachment` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`burstid` int(11) NOT NULL,
	`filename` varchar(255) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `messageattachment` ADD `type` ENUM( 'content', 'burst' ) NOT NULL ,
	ADD `contentattachmentid` INT NULL DEFAULT NULL ,
	ADD `burstattachmentid` INT NULL DEFAULT NULL
$$$

-- $rev 7
ALTER TABLE `messageattachment`
	DROP `contentid`,
	DROP `filename`,
	DROP `size`
$$$

-- $rev 8
-- No schema change. subscriber limited user update is handled in db_10-1.php