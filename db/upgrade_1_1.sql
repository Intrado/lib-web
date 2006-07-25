
ALTER TABLE `jobworkitem` CHANGE `status` `status` ENUM( 'checking', 'new', 'scheduled', 'waiting', 'queued', 'assigned', 'inprogress', 'success', 'fail', 'duplicate' ) DEFAULT 'new' NOT NULL;

ALTER TABLE `jobworkitem` ADD `duplicateid` BIGINT AFTER `status` ;

ALTER TABLE `phone` ADD INDEX `dedupe` ( `phone` , `sequence` ) ;


ALTER TABLE `job` ADD `finishdate` DATETIME AFTER `endtime` ;

ALTER TABLE `address` ADD `editlock` TINYINT DEFAULT '0' NOT NULL ;
ALTER TABLE `email` ADD `editlock` TINYINT DEFAULT '0' NOT NULL ;
ALTER TABLE `phone` ADD `editlock` TINYINT DEFAULT '0' NOT NULL ;

ALTER TABLE `list` ADD `lastused` DATETIME, ADD `lastsize` INT;

ALTER TABLE `message` ADD `modified` DATETIME, ADD `lastused` DATETIME;

ALTER TABLE `specialtask` ADD `status` VARCHAR( 20 ) NOT NULL AFTER `id` , ADD `lastcheckin` DATETIME;

ALTER TABLE `specialtask` DROP INDEX `assignedto` ,
ADD INDEX `assignedto` ( `assignedto` , `status` ) ;

ALTER TABLE `job` CHANGE `options` `options` TEXT NOT NULL ;

ALTER TABLE `persondata` CHANGE `f20` `f20` VARCHAR( 255 ) DEFAULT NULL ;

ALTER TABLE `persondata` 
	ADD INDEX `f11b` ( `f11` ( 25 ) , `personid` ) ,
	ADD INDEX `f12b` ( `f12` ( 25 ) , `personid` ) ,
	ADD INDEX `f13b` ( `f13` ( 25 ) , `personid` ) ,
	ADD INDEX `f14b` ( `f14` ( 25 ) , `personid` ) ,
	ADD INDEX `f15b` ( `f15` ( 25 ) , `personid` ) ,
	ADD INDEX `f16b` ( `f16` ( 25 ) , `personid` ) ,
	ADD INDEX `f17b` ( `f17` ( 25 ) , `personid` ) ,
	ADD INDEX `f18b` ( `f18` ( 25 ) , `personid` ) ,
	ADD INDEX `f19b` ( `f19` ( 25 ) , `personid` ) ,
	ADD INDEX `f20b` ( `f20` ( 25 ) , `personid` );


ALTER TABLE `persondatavalues` ADD `lock` TINYINT DEFAULT '0' NOT NULL ;

ALTER TABLE `fieldmap` CHANGE `options` `options` TEXT NOT NULL ;

DROP TABLE `billing` ;
DROP TABLE `student` ;

ALTER TABLE `message` CHANGE `options` `options` TEXT NOT NULL ;
ALTER TABLE `permission` CHANGE `value` `value` TEXT NOT NULL ;
ALTER TABLE `renderedmessage` ADD `created` DATETIME NOT NULL ;
ALTER TABLE `specialtask` CHANGE `type` `type` VARCHAR( 50 ) DEFAULT 'EasyCall' NOT NULL ;
ALTER TABLE `user` DROP `options` ;

ALTER TABLE `user` ADD `personid` INT AFTER `customerid` ;
ALTER TABLE `user` ADD INDEX ( `personid` ) ;

CREATE TABLE `blockednumber` (
`id` INT NOT NULL AUTO_INCREMENT ,
`customerid` INT NOT NULL ,
`userid` INT NOT NULL ,
`pattern` VARCHAR( 10 ) NOT NULL ,
PRIMARY KEY ( `id` )
);



-- Ysleta --


ALTER TABLE `user` ADD `lastlogin` DATETIME DEFAULT NULL ;

ALTER TABLE `jobworkitem` CHANGE `status` `status` ENUM( 'checking', 'new', 'scheduled', 'waiting', 'queued', 'assigned', 'inprogress', 'success', 'fail', 'duplicate', 'blocked' ) DEFAULT 'new' NOT NULL ;

ALTER TABLE `blockednumber` ADD `description` VARCHAR( 50 ) NOT NULL AFTER `userid` ;

ALTER TABLE `jobtask` ADD INDEX `blockednumbers` ( `phoneid` , `jobworkitemid` ) ;


