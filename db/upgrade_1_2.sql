

CREATE TABLE `reportcompleted` (
  `jobid` int(11) NOT NULL default '0',
  `createdate` datetime default NULL,
  UNIQUE KEY `jobid` (`jobid`)
);



ALTER TABLE `access` ADD `deleted` TINYINT NOT NULL ;
ALTER TABLE `list` ADD `deleted` TINYINT NOT NULL ;
ALTER TABLE `message` ADD `deleted` TINYINT NOT NULL ;

ALTER TABLE `setting` ADD `moduserid` INT,
ADD `modified` DATETIME;
ALTER TABLE `user` ADD `deleted` TINYINT NOT NULL ;





ALTER TABLE `calllog` ADD INDEX ( `phonenumber` , `jobtaskid` ) ;

ALTER TABLE `blockednumber` ADD INDEX ( `customerid` ) ;


ALTER TABLE `message` DROP INDEX `userid` ,
ADD INDEX `userid` ( `userid` , `type` , `deleted` ) ;

ALTER TABLE `list` DROP INDEX `userid` ,
ADD INDEX `userid` ( `userid` , `name` , `deleted` ) ;

ALTER TABLE `access` ADD INDEX ( `id` , `deleted` ) ;

ALTER TABLE `user` DROP INDEX `login` ,
ADD INDEX `login` ( `login` , `password` , `enabled` , `deleted` ) ;