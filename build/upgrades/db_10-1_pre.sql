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
