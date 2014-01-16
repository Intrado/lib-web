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
CREATE TABLE `feedcat2cmacatmap` (
	fk_feedcategory INT NOT NULL,
	cmacategory INT NOT NULL,
	INDEX `feedcat` (`fk_feedcategory`)
);
$$$
