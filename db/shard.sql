--
-- Database: `aspshard`
--

-- --------------------------------------------------------

--
-- Table structure for table `importqueue`
--

CREATE TABLE `importqueue` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL,
  `localimportid` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `import` (`customerid`,`localimportid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `jobstatdata`
--

CREATE TABLE `jobstatdata` (
  `jobid` int(11) NOT NULL,
  `customerid` int(11) NOT NULL,
  `attempt` tinyint(4) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `priority_fraction` float NOT NULL,
  `customer_fraction` float NOT NULL,
  `job_fraction` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `leasetask`
--

CREATE TABLE `leasetask` (
  `taskuuid` bigint NOT NULL,
  `leasetime` bigint(20) NOT NULL,
  PRIMARY KEY  (`taskuuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `qjob`
--

CREATE TABLE `qjob` (
  `customerid` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL default '0',
  `scheduleid` int(11) default NULL,
  `listid` int(11) NOT NULL default '0',
  `phonemessageid` int(11) default NULL,
  `emailmessageid` int(11) default NULL,
  `printmessageid` int(11) default NULL,
  `questionnaireid` int(11) default NULL,
  `timezone` varchar(50) NOT NULL,
  `startdate` date NOT NULL default '0000-00-00',
  `enddate` date NOT NULL default '0000-00-00',
  `starttime` time NOT NULL default '00:00:00',
  `endtime` time NOT NULL default '00:00:00',
  `status` enum('scheduled','processing','procactive','active','cancelling','repeating') NOT NULL default 'scheduled',
  `phonetaskcount` int(11) NOT NULL default '0',
  `processedcount` int (11) NOT NULL default '0',
  `systempriority` tinyint(4) NOT NULL default '3',
  `timeslices` smallint(6) NOT NULL default '0',
  `thesql` text,
  PRIMARY KEY  (`customerid`,`id`),
  KEY `status` (`status`,`id`),
  KEY `startdate` (`startdate`),
  KEY `enddate` (`enddate`),
  KEY `starttime` (`starttime`),
  KEY `endtime` (`endtime`),
  KEY `startdate_2` (`startdate`,`enddate`,`starttime`,`endtime`,`id`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `qjobsetting`
--

CREATE TABLE `qjobsetting` (
  `customerid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`customerid`,`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `qjobtask`
--

CREATE TABLE `qjobtask` (
  `customerid` int(11) NOT NULL default '0',
  `jobid` int(11) NOT NULL default '0',
  `type` enum('phone','email','print','sms') NOT NULL,
  `personid` int(11) NOT NULL default '0',
  `sequence` tinyint(4) NOT NULL default '0',
  `contactsequence` tinyint(4) NOT NULL default '0',
  `status` enum('active','pending','assigned','progress','waiting','throttled') NOT NULL,
  `attempts` tinyint(4) NOT NULL default '0',
  `renderedmessage` text,
  `lastresult` enum('A','M','N','B','X','F','sent','unsent','printed','notprinted') default NULL,
  `lastresultdata` text,
  `lastduration` float default NULL,
  `lastattempttime` bigint(20) default NULL,
  `nextattempttime` bigint(20) default NULL,
  `leasetime` bigint(20) default NULL,
  `phone` varchar(20) default NULL,
  `uuid` bigint NOT NULL,
  PRIMARY KEY `uuid` (`uuid`),
  KEY `id` (`customerid`,`jobid`,`type`,`personid`,`sequence`),
  KEY `waiting` (`status`,`nextattempttime`),
  KEY `progresshandler` (`status`,`lastattempttime`),
  KEY `dispatch` (`status`,`customerid`,`jobid`,`type`,`attempts`,`sequence`),
  KEY `emailer` (`type`,`status`,`nextattempttime`),
  KEY `personid` (`personid`),
  KEY `expired` (`status`,`leasetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `qreportsubscription`
--

CREATE TABLE `qreportsubscription` (
  `customerid` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL default '0',
  `type` enum('notscheduled','once','weekly','monthly') NOT NULL default 'notscheduled',
  `daysofweek` varchar(20) default NULL,
  `dayofmonth` tinyint(4) default NULL,
  `timezone` varchar(50) NOT NULL,
  `nextrun` datetime default NULL,
  `time` time default NULL,
  `email` text NOT NULL,
  PRIMARY KEY  (`customerid`,`id`),
  KEY `nextrun` (`nextrun`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `qschedule`
--

CREATE TABLE `qschedule` (
  `customerid` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `daysofweek` varchar(20) NOT NULL,
  `time` time NOT NULL default '00:00:00',
  `nextrun` datetime default NULL,
  `timezone` varchar(50) NOT NULL,
  PRIMARY KEY  (`customerid`,`id`),
  KEY `nextrun` (`nextrun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `specialtaskqueue`
--

CREATE TABLE `specialtaskqueue` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL,
  `localspecialtaskid` int(11) NOT NULL,
  `uuid` varchar(255) default NULL,
  `status` enum('new','assigned') NOT NULL default 'new',
  `type` varchar(50) default NULL,
  `leasetime` bigint(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `specialtask` (`customerid`,`localspecialtaskid`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- -------------------------

CREATE TABLE `qjobperson` (
`customerid` INT( 11 ) NOT NULL DEFAULT '0',
`jobid` INT( 11 ) NOT NULL DEFAULT '0',
`personid` INT( 11 ) NOT NULL DEFAULT '0',
PRIMARY KEY (`customerid`,`jobid`,`personid`)
) ENGINE = innodb;

-- RELEASE ASP_2007-08_10 ----------------------------------------

ALTER TABLE `qjobtask` CHANGE `uuid` `uuid` BIGINT( 20 ) UNSIGNED NOT NULL;

ALTER TABLE `qjobtask` CHANGE `renderedmessage` `renderedmessage` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- RELEASE ASP_2007-08-24 ------------------------------------------

ALTER TABLE `qjobtask` CHANGE `lastresult` `lastresult` ENUM( 'A', 'M', 'N', 'B', 'X', 'F', 'sent', 'unsent', 'printed', 'notprinted', 'cancelling', 'endoflife' ) NULL DEFAULT NULL;

-- begin parent portal September 2007

ALTER TABLE `qjob` ADD `jobtypeid` INT NOT NULL AFTER `timeslices` ;

ALTER TABLE `qjob` ADD   `smsmessageid` int(11) default NULL AFTER `printmessageid` ;

-- ASP 5.2

ALTER TABLE `jobstatdata` ADD `type` ENUM( 'system', 'customer' ) NOT NULL DEFAULT 'system' FIRST ;
ALTER TABLE `jobstatdata` ADD INDEX `remotedm` ( `type` , `customerid` )  ;
ALTER TABLE `qjob` ADD `dispatchtype` ENUM( 'customer', 'system' ) NOT NULL DEFAULT 'system';
ALTER TABLE `specialtaskqueue` ADD `dispatchtype` ENUM( 'customer', 'system' ) NOT NULL DEFAULT 'system';

-- ASP 5.3 aka 6.0

CREATE TABLE `smsblock` (
`sms` VARCHAR( 20 ) NOT NULL ,
`status` ENUM( 'block', 'pendingoptin', 'optin' ) NOT NULL default 'pendingoptin',
`lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP  on update CURRENT_TIMESTAMP,
`notes` VARCHAR( 255 ) NOT NULL DEFAULT '',
PRIMARY KEY ( `sms` ),
INDEX ( `status` )
) ENGINE = innodb;

-- start here for ASP 6.2

ALTER TABLE `qjobtask` ADD `lastdispatchtype` ENUM( 'customer', 'system' ) NOT NULL DEFAULT 'system';

CREATE TABLE `qjoblist` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`customerid` int(11) NOT NULL,
`jobid` INT NOT NULL ,
`listid` INT NOT NULL ,
`thesql` TEXT,
KEY `jobid` (`customerid`,`jobid`,`listid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

-- missing indexes

ALTER TABLE `specialtaskqueue` ADD INDEX `dispatch` ( `status` , `dispatchtype` )  ;

ALTER TABLE `qreportsubscription` DROP INDEX `nextrun` , ADD INDEX `nextrun` ( `nextrun` , `timezone` ) ; -- does this need to be swapped order? Does this help? Jammer refuses to use any index on nextrun unless you trim off seconds ex: left(...,16)

ALTER TABLE `leasetask` ADD INDEX ( `leasetime` ) ;

ALTER TABLE `qjob` ADD INDEX `sched_timezones` ( `timezone` , `status` ) ;

ALTER TABLE `qreportsubscription` ADD INDEX ( `timezone` ) ;

ALTER TABLE `specialtaskqueue` ADD INDEX `leasecheck` ( `status` , `leasetime` ) ;

-- simplify the qjobtask table
ALTER TABLE `qjobtask` CHANGE `lastresultdata` `lastresultdata` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- table to move renderedmessage out of qjobtask
 CREATE TABLE `aspshard`.`renderedmessage` (
`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`renderedmessage` TEXT NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci ;

ALTER TABLE `qjobtask` DROP `renderedmessage`  ;
ALTER TABLE `qjobtask` ADD `renderedmessageid` BIGINT NULL AFTER `attempts` ;

-- view for dispatcher
create sql security invoker view qjobtask_dispatchview as
select `customerid`, `jobid`, `type`, `personid`, `sequence`, `contactsequence`, `status`, `attempts`, `renderedmessage`, `leasetime`, `phone`, `uuid`
from qjobtask left join renderedmessage on (qjobtask.renderedmessageid = renderedmessage.id);

CREATE TABLE `aspshard`.`messagelink` (
`customerid` INT NOT NULL ,
`jobid` INT NOT NULL ,
`personid` INT NOT NULL ,
`createtime` BIGINT NOT NULL ,
`code` VARCHAR( 22 ) CHARACTER SET ascii COLLATE ascii_bin NOT NULL ,
PRIMARY KEY ( `customerid` , `jobid` , `personid` ) ,
INDEX (`createtime`),
UNIQUE (`code` )
) ENGINE = InnoDB ;

-- ----------------------------
-- start here for release 7.1

ALTER TABLE `smsblock` CHANGE `status` `status` ENUM( 'block', 'pendingoptin', 'optin', 'new' ) NOT NULL DEFAULT 'new' ;

ALTER TABLE `smsblock` ADD `timezone` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'US/Pacific';

ALTER TABLE `smsblock` ADD `customerid` INT DEFAULT NULL FIRST ;

CREATE TABLE `aspshard`.`replicationcheck` (
`id` TINYINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`currenttime` BIGINT NOT NULL
) ENGINE = InnoDB ;

-- ---------------------------
-- start here for release 7.5

-- NOTE you must not run this until after all customers are upgraded, copies job.listid to joblist table
ALTER TABLE `qjob` DROP `listid`, DROP `thesql`;
ALTER TABLE `qjoblist` DROP `thesql`;

-- for looking up pending opt in messages based on timezone
ALTER TABLE `smsblock` ADD INDEX ( `timezone` ) ;

-- for sms dispatch to pull out older tasks for given status, timezone
ALTER TABLE `smsblock` ADD INDEX `dispatch` ( `status` , `lastupdate` , `timezone` );

-- dont need this anymore since dispatch key has status as prefix
ALTER TABLE `smsblock` DROP INDEX `status` ;

DROP TABLE `jobstatdata` ;

-- now use customer.joblist 
DROP TABLE `qjoblist`;

-- now use customer.jobsetting
DROP TABLE `qjobsetting`;

-- now use customer.job for these fields
ALTER TABLE `qjob`
  DROP `phonemessageid`,
  DROP `emailmessageid`,
  DROP `printmessageid`,
  DROP `smsmessageid`,
  DROP `jobtypeid`;
 
ALTER TABLE `qjob` ADD `messagegroupid` INT default NULL AFTER `scheduleid` ;

ALTER TABLE `qjob` DROP `messagegroupid`,
	DROP `questionnaireid`;
	
-- ---------------------------
-- start here for release 7.5.1

 ALTER TABLE `renderedmessage` CHANGE `renderedmessage` `renderedmessage` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
 
 
-- ---------------------------
-- start here for release 8.1

CREATE TABLE `importalert` (
	`customerid` INT NOT NULL,
	`importalertruleid` INT NOT NULL,
	`name` varchar(50) NOT NULL,
    `operation` enum('eq','ne','gt','lt') NOT NULL,
	`testvalue` INT NOT NULL,
    `actualvalue` INT NOT NULL,
	`alerttime` datetime default NULL,
	`notified` datetime default NULL,
	PRIMARY KEY ( `customerid` , `importalertruleid` ) 
) ENGINE = InnoDB;

CREATE TABLE `emailjobtask` (
  `customerid` int(11) NOT NULL default '0',
  `jobid` int(11) NOT NULL default '0',
  `personid` int(11) NOT NULL default '0',
  `sequence` tinyint(4) NOT NULL default '0',
  `contactsequence` tinyint(4) NOT NULL default '0',
  `status` enum('active','assigned','progress') NOT NULL,
  `renderedmessageid` BIGINT NULL,
  `lastresult` enum('sent','unsent') default 'unsent',
  `lastattempttime` bigint(20) default NULL,
  `nextattempttime` bigint(20) default NULL,
  `leasetime` bigint(20) default NULL,
  `uuid` BIGINT( 20 ) UNSIGNED NOT NULL,
  PRIMARY KEY `uuid` (`uuid`),
  KEY `id` (`customerid`,`jobid`,`personid`,`sequence`),
  KEY `progresshandler` (`status`,`lastattempttime`),
  KEY `dispatch` (`status`,`nextattempttime`),
  KEY `personid` (`personid`),
  KEY `expired` (`status`,`leasetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `smsjobtask` (
  `customerid` int(11) NOT NULL default '0',
  `jobid` int(11) NOT NULL default '0',
  `personid` int(11) NOT NULL default '0',
  `sequence` tinyint(4) NOT NULL default '0',
  `contactsequence` tinyint(4) NOT NULL default '0',
  `status` enum('active','assigned','progress') NOT NULL,
  `renderedmessageid` BIGINT NULL,
  `lastresult` enum('sent','unsent') default 'unsent',
  `lastattempttime` bigint(20) default NULL,
  `nextattempttime` bigint(20) default NULL,
  `leasetime` bigint(20) default NULL,
  `uuid` BIGINT( 20 ) UNSIGNED NOT NULL,
  `phone` varchar(20) default NULL,
  PRIMARY KEY `uuid` (`uuid`),
  KEY `id` (`customerid`,`jobid`,`personid`,`sequence`),
  KEY `progresshandler` (`status`,`lastattempttime`),
  KEY `dispatch` (`status`,`nextattempttime`),
  KEY `personid` (`personid`),
  KEY `expired` (`status`,`leasetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `emailrenderedmessage` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `renderedmessage` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci ;

CREATE TABLE `smsrenderedmessage` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `renderedmessage` varchar(160) NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci ;

ALTER TABLE `emailrenderedmessage` CHANGE `renderedmessage` `renderedmessage` MEDIUMBLOB NOT NULL ;

ALTER TABLE `smsjobtask` ADD `attempts` tinyint(4) NOT NULL default '0' after `status`,
    CHANGE `status` `status` enum('active','assigned','progress','waiting') NOT NULL ;

CREATE TABLE `emailleasetask` (
  `taskuuid` bigint NOT NULL,
  `leasetime` bigint(20) NOT NULL,
  PRIMARY KEY  (`taskuuid`),
  KEY `leasetime` (`leasetime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `smsleasetask` (
  `taskuuid` bigint NOT NULL,
  `leasetime` bigint(20) NOT NULL,
  PRIMARY KEY  (`taskuuid`),
  KEY `leasetime` (`leasetime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `smsjobtask` CHANGE `lastresult` `lastresult` enum('sent','unsent','fail','tempfail') default 'unsent' ;

