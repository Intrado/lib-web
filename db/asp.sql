-- phpMyAdmin SQL Dump
-- version 2.6.3-pl1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost:3307
-- Generation Time: Jul 20, 2006 at 03:08 PM
-- Server version: 5.0.22
-- PHP Version: 5.0.4
-- 
-- Database: `dialerasp`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `access`
-- 

CREATE TABLE `access` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `moduserid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `id` (`id`,`deleted`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `access`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `address`
-- 

CREATE TABLE `address` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `addressee` varchar(50) default NULL,
  `addr1` varchar(50) default NULL,
  `addr2` varchar(50) default NULL,
  `city` varchar(50) default NULL,
  `state` char(2) default NULL,
  `zip` varchar(10) default NULL,
  `editlock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `address`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `audiofile`
-- 

CREATE TABLE `audiofile` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `contentid` bigint(20) NOT NULL default '0',
  `recorddate` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `list` (`userid`,`deleted`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `audiofile`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `blockednumber`
-- 

CREATE TABLE `blockednumber` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `description` varchar(100) NOT NULL,
  `pattern` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `customerid` (`customerid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `blockednumber`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `calllog`
-- 

CREATE TABLE `calllog` (
  `id` bigint(20) NOT NULL auto_increment,
  `jobtaskid` bigint(20) NOT NULL default '0',
  `starttime` bigint(20) NOT NULL default '0',
  `duration` float NOT NULL default '0',
  `callprogress` enum('C','A','M','N','B','X','F') NOT NULL default 'C',
  `success` tinyint(4) NOT NULL default '0',
  `phonenumber` varchar(10) NOT NULL default '',
  `callattempt` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `jobtaskid` (`jobtaskid`),
  KEY `phonenumber` (`phonenumber`,`jobtaskid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `calllog`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `content`
-- 

CREATE TABLE `content` (
  `id` bigint(20) NOT NULL auto_increment,
  `contenttype` varchar(255) NOT NULL default '',
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `content`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `customer`
-- 

CREATE TABLE `customer` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `enabled` tinyint(4) NOT NULL default '0',
  `logocontentid` bigint(20) default NULL,
  `hostname` varchar(255) NOT NULL,
  `remotedm` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `customer`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `email`
-- 

CREATE TABLE `email` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `email` varchar(100) NOT NULL default '',
  `sequence` tinyint(4) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `email`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `fieldmap`
-- 

CREATE TABLE `fieldmap` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `fieldnum` varchar(4) NOT NULL default '0',
  `name` varchar(20) NOT NULL default '',
  `options` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `getfieldname` (`customerid`,`fieldnum`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `fieldmap`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `import`
-- 

CREATE TABLE `import` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `listid` int(11) default NULL,
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `status` enum('idle','running','error') NOT NULL default 'idle',
  `type` enum('manual','automatic','trigger','upload') NOT NULL default 'manual',
  `path` text,
  `scheduleid` int(11) default NULL,
  `ownertype` enum('system','user') NOT NULL default 'system',
  `updatemethod` enum('updateonly','update','full') NOT NULL default 'full',
  `lastrun` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `import`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `importfield`
-- 

CREATE TABLE `importfield` (
  `id` int(11) NOT NULL auto_increment,
  `importid` int(11) NOT NULL default '0',
  `mapto` varchar(4) NOT NULL default '',
  `mapfrom` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `importfield`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `job`
-- 

CREATE TABLE `job` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `scheduleid` int(11) default NULL,
  `jobtypeid` int(11) default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `listid` int(11) NOT NULL default '0',
  `phonemessageid` int(11) default NULL,
  `emailmessageid` int(11) default NULL,
  `printmessageid` int(11) default NULL,
  `type` set('phone','email','print') NOT NULL default 'phone',
  `createdate` datetime NOT NULL default '0000-00-00 00:00:00',
  `startdate` date NOT NULL default '0000-00-00',
  `enddate` date NOT NULL default '2006-07-04',
  `starttime` time NOT NULL default '00:00:00',
  `endtime` time NOT NULL default '00:00:00',
  `finishdate` datetime default NULL,
  `maxcallattempts` tinyint(4) NOT NULL default '2',
  `options` text NOT NULL,
  `status` enum('new','active','complete','cancelled','repeating') NOT NULL default 'new',
  `assigned` varchar(255) default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  `cancelleduserid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `status` (`status`,`id`),
  KEY `startdate` (`startdate`),
  KEY `enddate` (`enddate`),
  KEY `starttime` (`starttime`),
  KEY `endtime` (`endtime`),
  KEY `startdate_2` (`startdate`,`enddate`,`starttime`,`endtime`,`id`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `job`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `joblanguage`
-- 

CREATE TABLE `joblanguage` (
  `id` int(11) NOT NULL auto_increment,
  `jobid` int(11) NOT NULL default '0',
  `messageid` int(11) NOT NULL default '0',
  `type` enum('phone','email','print') NOT NULL default 'phone',
  `language` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `jobid` (`jobid`,`language`(50))
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `joblanguage`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `jobtask`
-- 

CREATE TABLE `jobtask` (
  `id` bigint(20) NOT NULL auto_increment,
  `jobworkitemid` bigint(20) NOT NULL default '0',
  `renderedmessageid` bigint(20) NOT NULL default '0',
  `phoneid` int(11) default NULL,
  `emailid` int(11) default NULL,
  `addressid` int(11) default NULL,
  `lastattempt` datetime default NULL,
  `numattempts` tinyint(4) NOT NULL default '0',
  `sequence` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `jobworkitemid` (`jobworkitemid`),
  KEY `lastattempt` (`lastattempt`,`jobworkitemid`,`sequence`),
  KEY `blockednumbers` (`phoneid`,`jobworkitemid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `jobtask`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `jobtype`
-- 

CREATE TABLE `jobtype` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `priority` int(11) NOT NULL default '10000',
  PRIMARY KEY  (`id`),
  KEY `customerid` (`customerid`,`priority`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `jobtype`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `jobworkitem`
-- 

CREATE TABLE `jobworkitem` (
  `id` bigint(20) NOT NULL auto_increment,
  `jobid` int(11) NOT NULL default '0',
  `type` enum('phone','email','print') NOT NULL default 'phone',
  `priority` int(11) NOT NULL default '0',
  `systempriority` tinyint(4) NOT NULL default '3',
  `personid` int(11) NOT NULL default '0',
  `messageid` int(11) NOT NULL default '0',
  `status` enum('checking','new','scheduled','waiting','queued','assigned','inprogress','success','fail','duplicate','blocked') NOT NULL default 'new',
  `duplicateid` bigint(20) default NULL,
  `resultdata` varchar(255) NOT NULL default '',
  `assignedto` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `job` (`jobid`,`personid`),
  KEY `personid` (`personid`,`jobid`),
  KEY `jobstatus` (`jobid`,`status`),
  KEY `assign` (`status`,`type`,`systempriority`,`priority`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `jobworkitem`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `language`
-- 

CREATE TABLE `language` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `code` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `customerid` (`customerid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `language`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `list`
-- 

CREATE TABLE `list` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastused` datetime default NULL,
  `lastsize` int(11) default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`name`,`deleted`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `list`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `listentry`
-- 

CREATE TABLE `listentry` (
  `id` int(11) NOT NULL auto_increment,
  `listid` int(11) NOT NULL default '0',
  `type` enum('R','A','N') NOT NULL default 'A',
  `ruleid` int(11) default NULL,
  `personid` int(11) default NULL,
  `sequence` tinyint(4) default '0',
  PRIMARY KEY  (`id`),
  KEY `type` (`personid`,`listid`,`type`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `listentry`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `message`
-- 

CREATE TABLE `message` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `type` enum('phone','email','print') NOT NULL default 'phone',
  `options` text NOT NULL,
  `data` text NOT NULL,
  `modified` datetime default NULL,
  `lastused` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`type`,`deleted`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `message`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `messagepart`
-- 

CREATE TABLE `messagepart` (
  `id` int(11) NOT NULL auto_increment,
  `messageid` int(11) NOT NULL default '0',
  `type` enum('A','T','V') NOT NULL default 'A',
  `audiofileid` int(11) default NULL,
  `txt` text,
  `fieldnum` varchar(4) default NULL,
  `defaultvalue` varchar(255) default NULL,
  `voiceid` int(11) default NULL,
  `sequence` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `messageid` (`messageid`,`sequence`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `messagepart`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `permission`
-- 

CREATE TABLE `permission` (
  `id` int(11) NOT NULL auto_increment,
  `accessid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `permission`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `person`
-- 

CREATE TABLE `person` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `userid` int(11) default NULL,
  `pkey` varchar(255) default NULL,
  `importid` int(11) default NULL,
  `lastimport` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `getbykey` (`customerid`,`pkey`(50)),
  KEY `pkeysort` (`id`,`pkey`(50)),
  KEY `pkeysortb` (`pkey`(50),`id`),
  KEY `lastimport` (`customerid`,`importid`,`lastimport`),
  KEY `general` (`id`,`customerid`,`deleted`),
  KEY `ownership` (`userid`,`customerid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `person`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `persondata`
-- 

CREATE TABLE `persondata` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `f01` varchar(255) default NULL,
  `f02` varchar(255) default NULL,
  `f03` varchar(255) default NULL,
  `f04` varchar(255) default NULL,
  `f05` varchar(255) default NULL,
  `f06` varchar(255) default NULL,
  `f07` varchar(255) default NULL,
  `f08` varchar(255) default NULL,
  `f09` varchar(255) default NULL,
  `f10` varchar(255) default NULL,
  `f11` varchar(255) default NULL,
  `f12` varchar(255) default NULL,
  `f13` varchar(255) default NULL,
  `f14` varchar(255) default NULL,
  `f15` varchar(255) default NULL,
  `f16` varchar(255) default NULL,
  `f17` varchar(255) default NULL,
  `f18` varchar(255) default NULL,
  `f19` varchar(255) default NULL,
  `f20` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `f01b` (`f01`(50),`personid`),
  KEY `f02b` (`f02`(50),`personid`),
  KEY `f03b` (`f03`(50),`personid`),
  KEY `f04b` (`f04`(50),`personid`),
  KEY `f05b` (`f05`(50),`personid`),
  KEY `f06b` (`f06`(50),`personid`),
  KEY `f07b` (`f07`(50),`personid`),
  KEY `f08b` (`f08`(50),`personid`),
  KEY `f09b` (`f09`(50),`personid`),
  KEY `f10b` (`f10`(50),`personid`),
  KEY `personid` (`personid`),
  KEY `f11b` (`f11`(25),`personid`),
  KEY `f12b` (`f12`(25),`personid`),
  KEY `f13b` (`f13`(25),`personid`),
  KEY `f14b` (`f14`(25),`personid`),
  KEY `f15b` (`f15`(25),`personid`),
  KEY `f16b` (`f16`(25),`personid`),
  KEY `f17b` (`f17`(25),`personid`),
  KEY `f18b` (`f18`(25),`personid`),
  KEY `f19b` (`f19`(25),`personid`),
  KEY `f20b` (`f20`(25),`personid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `persondata`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `persondatavalues`
-- 

CREATE TABLE `persondatavalues` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `fieldnum` varchar(4) NOT NULL default '',
  `value` varchar(255) default NULL,
  `refcount` int(11) NOT NULL default '0',
  `lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `valuelookup` (`customerid`,`value`(50)),
  KEY `name` (`customerid`,`fieldnum`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `persondatavalues`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `phone`
-- 

CREATE TABLE `phone` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `phone` varchar(20) NOT NULL default '',
  `sequence` tinyint(4) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`),
  KEY `dedupe` (`phone`,`sequence`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `phone`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `renderedmessage`
-- 

CREATE TABLE `renderedmessage` (
  `id` bigint(20) NOT NULL auto_increment,
  `content` text NOT NULL,
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `renderedmessage`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `reportcompleted`
-- 

CREATE TABLE `reportcompleted` (
  `jobid` int(11) NOT NULL default '0',
  `createdate` datetime default NULL,
  UNIQUE KEY `jobid` (`jobid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `reportcompleted`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `rule`
-- 

CREATE TABLE `rule` (
  `id` int(11) NOT NULL auto_increment,
  `logical` enum('and','or','and not','or not') NOT NULL default 'and',
  `fieldnum` varchar(4) NOT NULL default '0',
  `op` enum('eq','ne','gt','ge','lt','le','lk','sw','ew','cn','in','reldate') NOT NULL default 'eq',
  `val` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `rule`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `schedule`
-- 

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) default NULL,
  `trigger` enum('import','job') NOT NULL default 'import',
  `type` enum('R','O') NOT NULL default 'R',
  `time` time NOT NULL default '00:00:00',
  `nextrun` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `schedule`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `scheduleday`
-- 

CREATE TABLE `scheduleday` (
  `id` int(11) NOT NULL auto_increment,
  `scheduleid` int(11) NOT NULL default '0',
  `dow` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `scheduleday`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `setting`
-- 

CREATE TABLE `setting` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `moduserid` int(11) default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `setting`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `specialtask`
-- 

CREATE TABLE `specialtask` (
  `id` bigint(20) NOT NULL auto_increment,
  `status` varchar(20) NOT NULL default '',
  `type` varchar(50) NOT NULL default 'EasyCall',
  `assignedto` varchar(255) default NULL,
  `data` text NOT NULL,
  `lastcheckin` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `assignedto` (`assignedto`,`status`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `specialtask`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `ttsvoice`
-- 

CREATE TABLE `ttsvoice` (
  `id` int(11) NOT NULL auto_increment,
  `ttsname` varchar(20) NOT NULL default '',
  `name` varchar(20) NOT NULL default '',
  `language` varchar(20) NOT NULL default '',
  `gender` enum('male','female') NOT NULL default 'male',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `ttsvoice`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `user`
-- 

CREATE TABLE `user` (
  `id` int(11) NOT NULL auto_increment,
  `accessid` int(11) NOT NULL default '0',
  `login` varchar(20) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `accesscode` varchar(10) NOT NULL default '',
  `pincode` varchar(255) NOT NULL default '',
  `customerid` int(11) NOT NULL default '0',
  `personid` int(11) default NULL,
  `firstname` varchar(50) NOT NULL default '',
  `lastname` varchar(50) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `email` varchar(100) NOT NULL default '',
  `enabled` tinyint(4) NOT NULL default '0',
  `lastlogin` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`),
  KEY `login` (`login`,`password`,`enabled`,`deleted`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `user`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `userjobtypes`
-- 

CREATE TABLE `userjobtypes` (
  `userid` int(11) NOT NULL default '0',
  `jobtypeid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`jobtypeid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `userjobtypes`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `userrule`
-- 

CREATE TABLE `userrule` (
  `userid` int(11) NOT NULL default '0',
  `ruleid` int(11) NOT NULL default '0',
  `sequence` tinyint(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 
-- Dumping data for table `userrule`
-- 





ALTER TABLE `customer` CHANGE `remotedm` `remotedm` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;
ALTER TABLE `jobtype` ADD `systempriority` TINYINT DEFAULT '3' NOT NULL AFTER `priority` ;
ALTER TABLE `list` CHANGE `modified` `modified` DATETIME NULL ;
ALTER TABLE `calllog` CHANGE `phonenumber` `phonenumber` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `customer` ADD `timezone` VARCHAR( 255 ) DEFAULT 'UTC' NOT NULL AFTER `remotedm` ;


ALTER TABLE `jobtask` ADD `phone` VARCHAR( 20 ) AFTER `phoneid` ;

-- this will need work, need to add support in php and all the others
ALTER TABLE `specialtask` ADD `customerid` INT NOT NULL AFTER `id` ;
ALTER TABLE `jobtype` ADD `deleted` TINYINT NOT NULL DEFAULT '0';


ALTER TABLE `jobtask` DROP INDEX `lastattempt` ;
ALTER TABLE `jobtask` DROP `lastattempt` ;
ALTER TABLE `jobtask` ADD `lastattempt` BIGINT NULL AFTER `addressid` ,
ADD `nextattempt` BIGINT NULL AFTER `lastattempt` ;

-- aproximately recreate the lastattempt for reporting
update jobtask jt inner join calllog cl on (cl.jobtaskid = jt.id and jt.numattempts-1 = cl.callattempt)
set jt.lastattempt = cl.starttime;




------------ after on asp 7/25 --------------


ALTER TABLE `jobworkitem` ADD `currentjobtaskid` BIGINT NULL AFTER `status` ;

ALTER TABLE `schedule` CHANGE `trigger` `triggertype` ENUM( 'import', 'job' ) NOT NULL DEFAULT 'import';






------------ after on asp 7/26 --------------


ALTER TABLE `customer` ADD `inboundnumber` VARCHAR( 20 ) NOT NULL AFTER `hostname` ;


ALTER TABLE `jobtask` ADD INDEX `waiting` ( `id` , `nextattempt` ) 

---------------- after 8/16 ---------------

CREATE TABLE `sessiondata` (
`id` CHAR( 255 ) NOT NULL ,
`lastused` TIMESTAMP NOT NULL ,
`data` BLOB NOT NULL ,
PRIMARY KEY ( `id` ) ,
INDEX ( `lastused` )
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `import` ADD `uploadkey` VARCHAR( 255 ) NULL AFTER `id` ;

ALTER TABLE `import` ADD UNIQUE (
`uploadkey`
);

ALTER TABLE `sessiondata` ADD `customerid` INT NOT NULL AFTER `id` ;
ALTER TABLE `sessiondata` CHANGE `data` `data` TEXT NOT NULL 