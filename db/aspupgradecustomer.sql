-- Upgrade from release 6.1 to 6.2 


create table if not exists customercallstats (
  jobid int(11) NOT NULL,
  userid int(11) NOT NULL,
  finishdate datetime default NULL,
  attempted int(11),
  primary key (jobid)
) engine=innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

CREATE TABLE if not exists `dmschedule` (
`id` INT NOT NULL auto_increment ,
`dmid` INT NOT NULL ,
`daysofweek` VARCHAR( 20 ) NOT NULL ,
`starttime` TIME NOT NULL ,
`endtime` TIME NOT NULL ,
`resourcepercentage` float NOT NULL DEFAULT '1',
PRIMARY KEY ( `id` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- fix charsets

ALTER TABLE `access`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `address`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `audiofile`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `blockednumber`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `contactpref`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `content`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `custdm`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `destlabel`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `dmcalleridroute`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `dmroute`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `email`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `enrollment`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `fieldmap`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `groupdata`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `import`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `importfield`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `importjob`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `job`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `joblanguage`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `jobsetting`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `jobstats`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `jobtype`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `jobtypepref`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `language`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `list`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `listentry`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `message`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `messageattachment`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `messagepart`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `permission`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `person`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `persondatavalues`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `phone`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `portalperson`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `portalpersontoken`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `reportcontact`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `reportgroupdata`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `reportinstance`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `reportperson`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `reportsubscription`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `rule`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `schedule`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `setting`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `sms`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `specialtask`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `surveyquestion`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `surveyquestionnaire`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `surveyresponse`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `surveyweb`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `systemstats`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `ttsvoice`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `user`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `userjobtypes`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `userrule`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `usersetting`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$
ALTER TABLE `voicereply`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci $$$


 ALTER TABLE `custdm` CHANGE `name` `name` VARCHAR( 255 ) NOT NULL  
$$$

 ALTER TABLE `enrollment` CHANGE `c01` `c01` VARCHAR( 255 ) NOT NULL ,
CHANGE `c02` `c02` VARCHAR( 255 ) NOT NULL ,
CHANGE `c03` `c03` VARCHAR( 255 ) NOT NULL ,
CHANGE `c04` `c04` VARCHAR( 255 ) NOT NULL ,
CHANGE `c05` `c05` VARCHAR( 255 ) NOT NULL ,
CHANGE `c06` `c06` VARCHAR( 255 ) NOT NULL ,
CHANGE `c07` `c07` VARCHAR( 255 ) NOT NULL ,
CHANGE `c08` `c08` VARCHAR( 255 ) NOT NULL ,
CHANGE `c09` `c09` VARCHAR( 255 ) NOT NULL ,
CHANGE `c10` `c10` VARCHAR( 255 ) NOT NULL 
$$$

 ALTER TABLE `groupdata` CHANGE `value` `value` VARCHAR( 255 ) NOT NULL  
$$$

 ALTER TABLE `reportgroupdata` CHANGE `value` `value` VARCHAR( 255 ) NOT NULL  
$$$

 ALTER TABLE `reportsubscription` CHANGE `name` `name` VARCHAR( 50 ) NOT NULL ,
CHANGE `description` `description` VARCHAR( 50 ) NOT NULL 
$$$


-- rule changes
-- depricate like operator
update rule set op='eq' where op='lk'
$$$

ALTER TABLE `rule` CHANGE `op` `op` ENUM( 'eq', 'ne', 'sw', 'ew', 'cn', 'in', 'reldate', 'date_range', 
	'num_eq', 'num_ne', 'num_gt', 'num_ge', 'num_lt', 'num_le', 'num_range', 'date_offset' ) NOT NULL DEFAULT 'eq' 
$$$


CREATE TABLE IF NOT EXISTS `importlogentry` (
  `id` bigint(20) NOT NULL auto_increment,
  `importid` int(11) NOT NULL,
  `severity` enum('info','error','warn') NOT NULL,
  `txt` varchar(255) NOT NULL,
  `linenum` int(11) NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- this will take forever
ALTER TABLE `reportcontact` ADD `dispatchtype` ENUM( 'customer', 'system' ) NOT NULL DEFAULT 'system';
$$$




