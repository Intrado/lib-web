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

INSERT INTO `ttsvoice` (`id`, `ttsname`, `name`, `language`, `gender`) VALUES (1, '', '', 'english', 'male');
INSERT INTO `ttsvoice` (`id`, `ttsname`, `name`, `language`, `gender`) VALUES (2, '', '', 'english', 'female');
INSERT INTO `ttsvoice` (`id`, `ttsname`, `name`, `language`, `gender`) VALUES (3, '', '', 'spanish', 'male');
INSERT INTO `ttsvoice` (`id`, `ttsname`, `name`, `language`, `gender`) VALUES (4, '', '', 'spanish', 'female');

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

-- everything past this point should be complete upgrade SQL (nickolas, use this)



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




--  after on asp 7/25 --------------


ALTER TABLE `jobworkitem` ADD `currentjobtaskid` BIGINT NULL AFTER `status` ;

ALTER TABLE `schedule` CHANGE `trigger` `triggertype` ENUM( 'import', 'job' ) NOT NULL DEFAULT 'import';






-- after on asp 7/26 --------------


ALTER TABLE `customer` ADD `inboundnumber` VARCHAR( 20 ) NOT NULL AFTER `hostname` ;


ALTER TABLE `jobtask` ADD INDEX `waiting` ( `id` , `nextattempt` ) ;

-- after 8/16 ---------------


ALTER TABLE `import` ADD `uploadkey` VARCHAR( 255 ) NULL AFTER `id` ;

ALTER TABLE `import` ADD UNIQUE (
`uploadkey`
);


CREATE TABLE `sessiondata` (
  `id` char(255) NOT NULL,
  `customerid` int(11) NOT NULL,
  `lastused` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `lastused` (`lastused`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- after 9/21

CREATE TABLE `usersetting` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `usersetting` (`userid`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8  ;


-- make login case sensitive
ALTER TABLE `user` CHANGE `login` `login` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ;


-- after 11/15


ALTER TABLE `jobworkitem` ADD INDEX `digest` ( `status` , `jobid` ) ;


-- Pre march 07 release (queries after this were applied to the ASP during march 07 release)


ALTER TABLE `job` CHANGE `status` `status` ENUM( 'new', 'active', 'complete', 'cancelled', 'cancelling', 'repeating' ) NOT NULL DEFAULT 'new';

-- for customer load balancing
ALTER TABLE `jobworkitem` ADD `customerid` INT NOT NULL AFTER `jobid` ;

-- set all existing workitems customerid
update jobworkitem wi, job j, user u
set wi.customerid=u.customerid
where
j.id = wi.jobid and u.id = j.userid;



ALTER TABLE `jobworkitem` ADD INDEX `assign2` ( `status` , `customerid` , `type` , `systempriority` , `priority` ) ;





ALTER TABLE `specialtask` DROP `assignedto` ;
ALTER TABLE `specialtask` CHANGE `status` `status` ENUM( 'new', 'queued', 'assigned', 'done' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `specialtask` DROP INDEX `assignedto` ,
ADD INDEX `status` ( `status` , `type` ) ;

ALTER TABLE `specialtask`  ENGINE = innodb;


-- support multiple emails for autoreports
ALTER TABLE `user` CHANGE `email` `email` TEXT CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ;


-- dedupe emails, and accurate reporting

ALTER TABLE `jobtask` ADD `email` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_bin NULL AFTER `emailid` ;


ALTER TABLE `job` ADD `ranautoreport` TINYINT NOT NULL DEFAULT '0' AFTER `deleted` ;

-- update all jobs ranautoreport field from teh reportcompleted table

update job j inner join reportcompleted rc on (rc.jobid = j.id) set j.ranautoreport=1;

ALTER TABLE `job` ADD INDEX ( `ranautoreport` , `status` ) ;


-- moved from ldap.sql
ALTER TABLE `user` ADD `ldap` TINYINT( 10 ) NOT NULL DEFAULT 0;


-- moved from survey.sql

CREATE TABLE `surveyquestionnaire` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `name` varchar(50) collate utf8_bin NOT NULL,
  `description` varchar(50) collate utf8_bin NOT NULL,
  `hasphone` tinyint(4) NOT NULL default '0',
  `hasweb` tinyint(4) NOT NULL default '0',
  `dorandomizeorder` tinyint(4) NOT NULL default '0',
  `machinemessageid` int(11) default NULL,
  `emailmessageid` int(11) default NULL,
  `intromessageid` int(11) default NULL,
  `exitmessageid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM ;

ALTER TABLE `surveyquestionnaire` ADD `deleted` TINYINT NOT NULL DEFAULT '0';



CREATE TABLE `surveyquestion` (
  `id` int(11) NOT NULL auto_increment,
  `questionnaireid` int(11) NOT NULL,
  `questionnumber` tinyint(4) NOT NULL,
  `webmessage` text collate utf8_bin,
  `phonemessageid` int(11) default NULL,
  `validresponse` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM ;


-- add survey types to job

ALTER TABLE `job` CHANGE `type` `type` SET( 'phone', 'email', 'print', 'survey' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'phone';

ALTER TABLE `job` ADD `questionnaireid` INT NULL AFTER `printmessageid` ;

-- now jobworkitem doesn't always need a specific message, for survey the message info is pulled from the questionnaire.
ALTER TABLE `jobworkitem` CHANGE `messageid` `messageid` INT( 11 ) NULL ;


ALTER TABLE `calllog` ADD `resultdata` TEXT NULL AFTER `callattempt` ;

CREATE TABLE `surveyresponse` (
`jobid` INT NOT NULL ,
`questionnumber` TINYINT NOT NULL ,
`answer` TINYINT NOT NULL ,
`tally` INT NOT NULL DEFAULT '0'
) ENGINE = MYISAM ;

ALTER TABLE `surveyresponse` ADD PRIMARY KEY ( `jobid` , `questionnumber` , `answer` ) ;


ALTER TABLE `calllog` ADD `participated` TINYINT NOT NULL DEFAULT '0' AFTER `resultdata` ;

ALTER TABLE `surveyquestion` ADD `reportlabel` VARCHAR( 30 ) AFTER `phonemessageid` ;


CREATE TABLE `surveyemailcode` (
  `code` char(22) character set ascii collate ascii_bin NOT NULL,
  `jobworkitemid` bigint(20) NOT NULL,
  `customerid` int(11) NOT NULL,
  `isused` tinyint(4) NOT NULL default '0',
  `dateused` datetime default NULL,
  `loggedip` varchar(15) collate utf8_bin default NULL,
  PRIMARY KEY  (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `surveyemailcode` ADD INDEX ( `jobworkitemid` ) ;

ALTER TABLE `surveyquestionnaire` ADD `webpagetitle` VARCHAR( 50 ) NULL AFTER `exitmessageid` ;
ALTER TABLE `surveyquestionnaire` ADD `webexitmessage` TEXT NULL AFTER `webpagetitle` ;

ALTER TABLE `surveyquestionnaire` ADD `usehtml` TINYINT NOT NULL DEFAULT '0' AFTER `webexitmessage` ;


ALTER TABLE `surveyemailcode` ADD `resultdata` TEXT NOT NULL AFTER `loggedip` ;



ALTER TABLE `surveyemailcode` DROP INDEX `jobworkitemid` ,
ADD UNIQUE `jobworkitemid` ( `jobworkitemid` ) ;


-- update all jobtasks phone entries for old records
update jobtask jt join calllog cl on (cl.jobtaskid = jt.id) set jt.phone = cl.phonenumber where cl.phonenumber is not null;


-- update jobtask emails
update jobtask jt join email e on (jt.emailid = e.id) set jt.email = e.email where e.email is not null;




-- WARNING! run this only on commsuite systems, it will disable all auto importing upload imports if run on the ASP
-- update import set type='manual' where listid is null;

-- get rid of import schedules
update scheduleday sd inner join schedule s on (s.id = sd.scheduleid) set sd.scheduleid = -1 where s.triggertype='import';
update scheduleday sd inner join import i on (i.scheduleid = sd.scheduleid) set sd.scheduleid = -1 where i.scheduleid is not null;
delete from scheduleday where scheduleid=-1;
delete from schedule where triggertype='import';
update import set scheduleid= null;

ALTER TABLE `import` CHANGE `type` `type` ENUM( 'manual', 'automatic', 'list', 'addressbook' ) NOT NULL DEFAULT 'manual';

-- set all list uploads to list type
update import set type = 'list' where listid is not null;

-- generate upload keys for all imports that don't have them

update import set uploadkey=mid(md5(rand()),3,12) where type != 'list' and (uploadkey is null or uploadkey = '');


-- add customerid to calllog

ALTER TABLE `calllog` ADD `customerid` INT NOT NULL AFTER `jobtaskid` ;

-- update exiting records w/ customerid

update calllog cl
join jobtask jt on (cl.jobtaskid = jt.id)
join jobworkitem wi on (jt.jobworkitemid = wi.id)
join job j on (wi.jobid = j.id)
join user u on (j.userid = u.id)
set cl.customerid = u.customerid;


ALTER TABLE `calllog` DROP INDEX `phonenumber` ;
ALTER TABLE `calllog` ADD INDEX `callreport` ( `customerid` , `starttime` ) ;

-- fix hibernate incompatability w/ utf8_bin
ALTER TABLE `user` CHANGE `email` `email` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

-- new table for trigger based jobs from imports

CREATE TABLE `importjob` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `importid` INT NOT NULL ,
  `jobid` INT NOT NULL
) ENGINE = MYISAM ;

-- asp has 1k settings, this should avoid table scans

ALTER TABLE `setting` ADD INDEX `lookup` ( `customerid` , `name` );

-- add type column to specify how person was added to system, update userid to reflect who entered it (do not set 0 anymore)

alter table `person` add `type` enum ('system', 'addressbook', 'manualadd', 'upload') not null default 'system' after `lastimport`;

UPDATE person SET TYPE = 'addressbook' WHERE userid is not null;

UPDATE person SET TYPE = 'manualadd' WHERE userid = 0 and importid is null;

UPDATE person SET TYPE = 'upload' WHERE userid = 0 and importid is not null;

update person p
  join import i on (p.importid = i.id)
  set p.userid = i.userid
  where p.userid = 0;

update person p
  join listentry le on (p.id = le.personid)
  join list l on (le.listid = l.id)
  set p.userid = l.userid
  where p.userid = 0;


-- speeds up date range reports slightly because mysql query optimizer can take statistics into account (that user->customer is a many to one)
ALTER TABLE `user` ADD INDEX ( `customerid` ) ;


-- ASP March 07 Release (queries after this need to be applied to the ASP --


-- field to keep track of how much a jobs workitems priority has been adjusted
ALTER TABLE `job` ADD `priorityadjust` INT NOT NULL DEFAULT '0' AFTER `ranautoreport` ;


-- configurable bucket/timeslice size
ALTER TABLE `jobtype` ADD `timeslices` SMALLINT NOT NULL DEFAULT '0' AFTER `systempriority` ;

update jobtype set timeslices = 50 where systempriority=1;
update jobtype set timeslices = 0 where systempriority=2;
update jobtype set timeslices = 100 where systempriority=3;



-- voice reply stuff

CREATE TABLE voicereply (
  id int(11) NOT NULL auto_increment,
  jobtaskid bigint(20) NOT NULL,
  jobworkitemid bigint(20) NOT NULL,
  personid int(11) NOT NULL,
  jobid int(11) NOT NULL,
  userid int(11) NOT NULL,
  customerid int(11) NOT NULL,
  contentid bigint(20) NOT NULL,
  replytime bigint(20) NOT NULL,
  listened tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM ;


-- added field for survey templates
ALTER TABLE `surveyquestionnaire` ADD `leavemessage` TINYINT(4) NOT NULL DEFAULT '0' AFTER `usehtml` ;


ALTER TABLE `voicereply` ADD INDEX ( `jobid` ) ;
ALTER TABLE `voicereply` ADD INDEX ( `userid` ) ;
ALTER TABLE `voicereply` ADD INDEX ( `replytime` ) ;

------------------------------------------------------------

drop table reportcompleted;


ALTER TABLE `person`
ADD `f01` VARCHAR( 50 ) NOT NULL ,
ADD `f02` VARCHAR( 50 ) NOT NULL ,
ADD `f03` VARCHAR( 50 ) NOT NULL ,
ADD `f04` VARCHAR( 255 ) NOT NULL ,
ADD `f05` VARCHAR( 255 ) NOT NULL ,
ADD `f06` VARCHAR( 255 ) NOT NULL ,
ADD `f07` VARCHAR( 255 ) NOT NULL ,
ADD `f08` VARCHAR( 255 ) NOT NULL ,
ADD `f09` VARCHAR( 255 ) NOT NULL ,
ADD `f10` VARCHAR( 255 ) NOT NULL ,
ADD `f11` VARCHAR( 255 ) NOT NULL ,
ADD `f12` VARCHAR( 255 ) NOT NULL ,
ADD `f13` VARCHAR( 255 ) NOT NULL ,
ADD `f14` VARCHAR( 255 ) NOT NULL ,
ADD `f15` VARCHAR( 255 ) NOT NULL ,
ADD `f16` VARCHAR( 255 ) NOT NULL ,
ADD `f17` VARCHAR( 255 ) NOT NULL ,
ADD `f18` VARCHAR( 255 ) NOT NULL ,
ADD `f19` VARCHAR( 255 ) NOT NULL ,
ADD `f20` VARCHAR( 255 ) NOT NULL ;


update person p inner join persondata pd on (pd.personid=p.id) set
p.f01 = pd.f01,
p.f02 = pd.f02,
p.f03 = pd.f03,
p.f04 = pd.f04,
p.f05 = pd.f05,
p.f06 = pd.f06,
p.f07 = pd.f07,
p.f08 = pd.f08,
p.f09 = pd.f09,
p.f10 = pd.f10,
p.f11 = pd.f11,
p.f12 = pd.f12,
p.f13 = pd.f13,
p.f14 = pd.f14,
p.f15 = pd.f15,
p.f16 = pd.f16,
p.f17 = pd.f17,
p.f18 = pd.f18,
p.f19 = pd.f19,
p.f20 = pd.f20;

CREATE TABLE `reportperson` (
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  `userid` int(11) NOT NULL,
  `customerid` int(11) NOT NULL,
  `messageid` int(11) NOT NULL,
  `status` enum('new','queued','assigned','fail','success','duplicate','blocked') NOT NULL,
  `numcontacts` tinyint(4) NOT NULL,
  `numduperemoved` tinyint(4) NOT NULL,
  `numblocked` tinyint(4) NOT NULL,
  `pkey` varchar(255) default NULL,
  `f01` varchar(50) NOT NULL default '',
  `f02` varchar(50) NOT NULL default '',
  `f03` varchar(50) NOT NULL default '',
  `f04` varchar(255) NOT NULL default '',
  `f05` varchar(255) NOT NULL default '',
  `f06` varchar(255) NOT NULL default '',
  `f07` varchar(255) NOT NULL default '',
  `f08` varchar(255) NOT NULL default '',
  `f09` varchar(255) NOT NULL default '',
  `f10` varchar(255) NOT NULL default '',
  `f11` varchar(255) NOT NULL default '',
  `f12` varchar(255) NOT NULL default '',
  `f13` varchar(255) NOT NULL default '',
  `f14` varchar(255) NOT NULL default '',
  `f15` varchar(255) NOT NULL default '',
  `f16` varchar(255) NOT NULL default '',
  `f17` varchar(255) NOT NULL default '',
  `f18` varchar(255) NOT NULL default '',
  `f19` varchar(255) NOT NULL default '',
  `f20` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`jobid`,`type`,`personid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `reportcontact` (
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `numattempts` tinyint(4) NOT NULL,
  `userid` int(11) NOT NULL,
  `customerid` int(11) NOT NULL,
  `starttime` bigint(20) NOT NULL default '0',
  `result` enum('C','A','M','N','B','X','F','sent','unsent','printed','notprinted') NOT NULL,
  `participated` tinyint(4) NOT NULL default '0',
  `duration` float default NULL,
  `resultdata` text,
  attemptdata varchar(200),
  `phone` varchar(20) default NULL,
  `email` varchar(100) default NULL,
  `addressee` varchar(50) default NULL,
  `addr1` varchar(50) default NULL,
  `addr2` varchar(50) default NULL,
  `city` varchar(50) default NULL,
  `state` char(2) default NULL,
  `zip` varchar(10) default NULL,
  PRIMARY KEY  (`jobid`,`type`,`personid`,`sequence`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- clean up records with duplicate junk in workitems

delete wi2 from
jobworkitem wi1 left join jobworkitem wi2 on (wi2.id > wi1.id and wi2.jobid = wi1.jobid and wi2.type = wi1.type and wi1.personid=wi2.personid)
where wi2.id is not null;

-- report person

insert into reportperson
select

j.id as jobid,
p.id as personid,
wi.type as type,
j.userid as userid,
u.customerid as customerid,
wi.messageid as messageid,
wi.status as status,
(select count(*) from jobtask jt where jt.jobworkitemid=wi.id) as numcontacts,
0 as numduperemoved,
0 as numblocked,

p.pkey as pkey,
p.f01 as f01,
p.f02 as f02,
p.f03 as f03,
p.f04 as f04,
p.f05 as f05,
p.f06 as f06,
p.f07 as f07,
p.f08 as f08,
p.f09 as f09,
p.f10 as f10,
p.f11 as f11,
p.f12 as f12,
p.f13 as f13,
p.f14 as f14,
p.f15 as f15,
p.f16 as f16,
p.f17 as f17,
p.f18 as f18,
p.f19 as f19,
p.f20 as f20

from person p
inner join jobworkitem wi
	on (p.id = wi.personid)
inner join job j
	on (wi.jobid = j.id)
inner join user u
	on (u.id = j.userid)
left join message m on
	(m.id = wi.messageid);



-- reportattempt

-- clean up records with bad sequences

delete jt2 from
jobtask jt1 left join jobtask jt2 on (jt2.id > jt1.id and jt2.jobworkitemid = jt1.jobworkitemid and jt2.sequence = jt1.sequence)
where jt2.id is not null;

-- insert phones
insert into reportcontact

select

j.id as jobid,
wi.personid as personid,
wi.type as type,
jt.sequence as sequence,
jt.numattempts as numattempts,
j.userid as userid,
u.customerid as customerid,

ifnull(cl.starttime,jt.lastattempt) as starttime,

case wi.type
 when 'phone' then cl.callprogress
 when 'email' then if (wi.status='success','sent','unsent')
 when 'print' then if (wi.status='success','printed','notprinted')
 end as result,
ifnull(cl.participated,0) as participated,
cl.duration as duration,
coalesce(cl.resultdata, sec.resultdata) as resultdata,

(select group_concat(cl2.starttime,':',cl2.callprogress order by cl2.callattempt)
 from calllog cl2 where cl2.jobtaskid = jt.id) as attemptdata,

jt.phone as phone,
jt.email as email,
a.addressee as addressee,
a.addr1 as addr1,
a.addr2 as addr2,
a.city as city,
a.state as state,
a.zip as zip


from jobworkitem wi
inner join job j on (wi.jobid = j.id)
inner join user u on (u.id = j.userid)
inner join jobtask jt on
(jt.jobworkitemid=wi.id)
inner join calllog cl on
(cl.jobtaskid=jt.id and cl.callattempt = jt.numattempts-1)
left join address a on
(a.id=jt.addressid and wi.type='print')
left join surveyemailcode sec on
(sec.jobworkitemid = wi.id and j.type='survey' and wi.type='email')
where wi.type = 'phone';

-- insert emails
insert into reportcontact

select

j.id as jobid,
wi.personid as personid,
wi.type as type,
jt.sequence as sequence,
jt.numattempts as numattempts,
j.userid as userid,
u.customerid as customerid,

ifnull(cl.starttime,jt.lastattempt) as starttime,

case wi.type
 when 'phone' then cl.callprogress
 when 'email' then if (wi.status='success','sent','unsent')
 when 'print' then if (wi.status='success','printed','notprinted')
 end as result,
ifnull(cl.participated,0) as participated,
cl.duration as duration,
coalesce(cl.resultdata, sec.resultdata) as resultdata,

(select group_concat(cl2.starttime,':',cl2.callprogress order by cl2.callattempt)
 from calllog cl2 where cl2.jobtaskid = jt.id) as attemptdata,

jt.phone as phone,
jt.email as email,
a.addressee as addressee,
a.addr1 as addr1,
a.addr2 as addr2,
a.city as city,
a.state as state,
a.zip as zip


from jobworkitem wi
inner join job j on (wi.jobid = j.id)
inner join user u on (u.id = j.userid)
inner join jobtask jt on
(jt.jobworkitemid=wi.id)
left join calllog cl on
(cl.jobtaskid=jt.id and cl.callattempt = jt.numattempts-1)
left join address a on
(a.id=jt.addressid and wi.type='print')
left join surveyemailcode sec on
(sec.jobworkitemid = wi.id and j.type='survey' and wi.type='email')
where wi.type = 'email';

-- insert prints
insert into reportcontact

select

j.id as jobid,
wi.personid as personid,
wi.type as type,
jt.sequence as sequence,
jt.numattempts as numattempts,
j.userid as userid,
u.customerid as customerid,

ifnull(cl.starttime,jt.lastattempt) as starttime,

case wi.type
 when 'phone' then cl.callprogress
 when 'email' then if (wi.status='success','sent','unsent')
 when 'print' then if (wi.status='success','printed','notprinted')
 end as result,
ifnull(cl.participated,0) as participated,
cl.duration as duration,
coalesce(cl.resultdata, sec.resultdata) as resultdata,

(select group_concat(cl2.starttime,':',cl2.callprogress order by cl2.callattempt)
 from calllog cl2 where cl2.jobtaskid = jt.id) as attemptdata,

jt.phone as phone,
jt.email as email,
a.addressee as addressee,
a.addr1 as addr1,
a.addr2 as addr2,
a.city as city,
a.state as state,
a.zip as zip


from jobworkitem wi
inner join job j on (wi.jobid = j.id)
inner join user u on (u.id = j.userid)
inner join jobtask jt on
(jt.jobworkitemid=wi.id)
left join calllog cl on
(cl.jobtaskid=jt.id and cl.callattempt = jt.numattempts-1)
left join address a on
(a.id=jt.addressid and wi.type='print')
left join surveyemailcode sec on
(sec.jobworkitemid = wi.id and j.type='survey' and wi.type='email')
where wi.type = 'print';


drop table persondata;

ALTER TABLE `import` ADD `data` LONGBLOB default NULL ;







