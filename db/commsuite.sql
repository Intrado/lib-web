-- phpMyAdmin SQL Dump
-- version 2.9.1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 05, 2007 at 03:39 PM
-- Server version: 5.0.27
-- PHP Version: 5.2.0
--
-- Database: `c_X`
--

-- --------------------------------------------------------

--
-- Table structure for table `access`
--

CREATE TABLE `access` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `blockednumber`
--

CREATE TABLE `blockednumber` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `description` varchar(100) NOT NULL,
  `pattern` varchar(10) NOT NULL default '',
  `type` enum('call','sms','both') NOT NULL default 'both',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `contactpref`
--

CREATE TABLE `contactpref` (
  `personid` int(11) NOT NULL,
  `jobtypeid` int(11) NOT NULL,
  `type` enum('phone','email','print','sms') NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`personid`,`jobtypeid`,`type`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` bigint(20) NOT NULL auto_increment,
  `contenttype` varchar(255) NOT NULL default '',
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `fieldmap`
--

CREATE TABLE `fieldmap` (
  `id` int(11) NOT NULL auto_increment,
  `fieldnum` varchar(4) NOT NULL default '0',
  `name` varchar(20) NOT NULL default '',
  `options` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `getfieldname` (`fieldnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `import`
--

CREATE TABLE `import` (
  `id` int(11) NOT NULL auto_increment,
  `uploadkey` varchar(255) default NULL,
  `scheduleid` int(11) default NULL,
  `userid` int(11) NOT NULL default '0',
  `listid` int(11) default NULL,
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `status` enum('idle','queued','running','error') NOT NULL default 'idle',
  `type` enum('manual','automatic','list','addressbook') NOT NULL default 'manual',
  `ownertype` enum('system','user') NOT NULL default 'system',
  `updatemethod` enum('updateonly','update','full') NOT NULL default 'full',
  `lastrun` datetime default NULL,
  `data` longblob,
  `datamodifiedtime` datetime default NULL,
  `skipheaderlines` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uploadkey` (`uploadkey`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `importfield`
--

CREATE TABLE `importfield` (
  `id` int(11) NOT NULL auto_increment,
  `importid` int(11) NOT NULL default '0',
  `mapto` varchar(4) NOT NULL default '',
  `action` enum('copy','staticvalue','number','currency','date','lookup','curdate') NOT NULL default 'copy',
  `mapfrom` tinyint(4) default NULL,
  `val` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `importjob`
--

CREATE TABLE `importjob` (
  `id` int(11) NOT NULL auto_increment,
  `importid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
  `smsmessageid` int(11) default NULL,
  `questionnaireid` int(11) default NULL,
  `type` set('phone','email','print','sms','survey') NOT NULL default 'phone',
  `createdate` datetime NOT NULL default '0000-00-00 00:00:00',
  `startdate` date NOT NULL default '0000-00-00',
  `enddate` date NOT NULL default '2006-07-04',
  `starttime` time NOT NULL default '00:00:00',
  `endtime` time NOT NULL default '00:00:00',
  `finishdate` datetime default NULL,
  `status` enum('new','scheduled','processing','procactive','active','complete','cancelled','cancelling','repeating') NOT NULL default 'new',
  `percentprocessed` tinyint(4) NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  `ranautoreport` tinyint(4) NOT NULL default '0',
  `priorityadjust` int(11) NOT NULL default '0',
  `cancelleduserid` int(11) default NULL,
  `thesql` text,
  PRIMARY KEY  (`id`),
  KEY `status` (`status`,`id`),
  KEY `startdate` (`startdate`),
  KEY `enddate` (`enddate`),
  KEY `starttime` (`starttime`),
  KEY `endtime` (`endtime`),
  KEY `startdate_2` (`startdate`,`enddate`,`starttime`,`endtime`,`id`),
  KEY `scheduleid` (`scheduleid`),
  KEY `ranautoreport` (`ranautoreport`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `joblanguage`
--

CREATE TABLE `joblanguage` (
  `id` int(11) NOT NULL auto_increment,
  `jobid` int(11) NOT NULL default '0',
  `messageid` int(11) NOT NULL default '0',
  `type` enum('phone','email','print','sms') NOT NULL default 'phone',
  `language` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `jobid` (`jobid`,`language`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `jobsetting`
--

CREATE TABLE `jobsetting` (
  `jobid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `jobstats`
--

CREATE TABLE `jobstats` (
  `jobid` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY  (`jobid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `jobtype`
--

CREATE TABLE `jobtype` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `systempriority` tinyint(4) NOT NULL default '3',
  `info` varchar(255) NOT NULL default '',
  `issurvey` tinyint(4) NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `jobtypepref`
--

CREATE TABLE `jobtypepref` (
  `jobtypeid` int(11) NOT NULL,
  `type` enum('phone','email','print','sms') NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`jobtypeid`,`type`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `list`
--

CREATE TABLE `list` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `lastused` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`name`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
  PRIMARY KEY  (`id`),
  KEY `type` (`personid`,`listid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `type` enum('phone','email','print','sms') NOT NULL default 'phone',
  `data` text NOT NULL,
  `lastused` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`type`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
  `maxlen` smallint(6) default NULL,
  PRIMARY KEY  (`id`),
  KEY `messageid` (`messageid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

CREATE TABLE `person` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) default NULL,
  `pkey` varchar(255) default NULL,
  `importid` int(11) default NULL,
  `lastimport` datetime default NULL,
  `type` enum('system','addressbook','manualadd','upload') NOT NULL default 'system',
  `deleted` tinyint(4) NOT NULL default '0',
  `f01` varchar(50) NOT NULL,
  `f02` varchar(50) NOT NULL,
  `f03` varchar(50) NOT NULL,
  `f04` varchar(255) NOT NULL,
  `f05` varchar(255) NOT NULL,
  `f06` varchar(255) NOT NULL,
  `f07` varchar(255) NOT NULL,
  `f08` varchar(255) NOT NULL,
  `f09` varchar(255) NOT NULL,
  `f10` varchar(255) NOT NULL,
  `f11` varchar(255) NOT NULL,
  `f12` varchar(255) NOT NULL,
  `f13` varchar(255) NOT NULL,
  `f14` varchar(255) NOT NULL,
  `f15` varchar(255) NOT NULL,
  `f16` varchar(255) NOT NULL,
  `f17` varchar(255) NOT NULL,
  `f18` varchar(255) NOT NULL,
  `f19` varchar(255) NOT NULL,
  `f20` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `getbykey` (`pkey`(50)),
  KEY `pkeysort` (`id`,`pkey`(50)),
  KEY `pkeysortb` (`pkey`(50),`id`),
  KEY `lastimport` (`importid`,`lastimport`),
  KEY `general` (`id`,`deleted`),
  KEY `ownership` (`userid`),
  KEY `namesort` (`f02`,`f01`),
  KEY `lang` (`f03`),
  KEY `f04` (`f04`(20)),
  KEY `f05` (`f05`(20)),
  KEY `f06` (`f06`(20)),
  KEY `f07` (`f07`(20)),
  KEY `f08` (`f08`(20)),
  KEY `f09` (`f09`(20)),
  KEY `f10` (`f10`(20)),
  KEY `f11` (`f11`(20)),
  KEY `f12` (`f12`(20)),
  KEY `f13` (`f13`(20)),
  KEY `f14` (`f14`(20)),
  KEY `f15` (`f15`(20)),
  KEY `f16` (`f16`(20)),
  KEY `f17` (`f17`(20)),
  KEY `f18` (`f18`(20)),
  KEY `f19` (`f19`(20)),
  KEY `f20` (`f20`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `persondatavalues`
--

CREATE TABLE `persondatavalues` (
  `id` int(11) NOT NULL auto_increment,
  `fieldnum` varchar(4) NOT NULL default '',
  `value` varchar(255) default NULL,
  `refcount` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `valuelookup` (`value`(50)),
  KEY `name` (`fieldnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `portalperson`
--

CREATE TABLE `portalperson` (
  `portaluserid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  PRIMARY KEY  (`portaluserid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `portalpersontoken`
--

CREATE TABLE `portalpersontoken` (
  `token` varchar(255) NOT NULL,
  `expirationdate` date NOT NULL,
  `personid` int(11) NOT NULL,
  `creationuserid` int(11) NOT NULL,
  PRIMARY KEY  (`token`, `personid`),
  UNIQUE KEY `personid` (`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `reportcontact`
--

CREATE TABLE `reportcontact` (
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `type` enum('phone','email','print','sms') NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `numattempts` tinyint(4) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `starttime` bigint(20) default NULL,
  `result` enum('C','A','M','N','B','X','F','sent','unsent','printed','notprinted','notattempted','duplicate','blocked') NOT NULL default 'notattempted',
  `participated` tinyint(4) NOT NULL default '0',
  `duration` float default NULL,
  `resultdata` text,
  `attemptdata` varchar(255) default NULL,
  `phone` varchar(20) default NULL,
  `email` varchar(100) default NULL,
  `sms` varchar(20) default NULL,
  `addressee` varchar(50) default NULL,
  `addr1` varchar(50) default NULL,
  `addr2` varchar(50) default NULL,
  `city` varchar(50) default NULL,
  `state` char(2) default NULL,
  `zip` varchar(10) default NULL,
  PRIMARY KEY  (`jobid`,`type`,`personid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `reportinstance`
--

CREATE TABLE `reportinstance` (
  `id` int(11) NOT NULL auto_increment,
  `parameters` text NOT NULL,
  `fields` text,
  `activefields` text,
  `instancehash` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `reportperson`
--

CREATE TABLE `reportperson` (
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `type` enum('phone','email','print','sms') NOT NULL,
  `userid` int(11) NOT NULL,
  `messageid` int(11) default NULL,
  `status` enum('new','queued','assigned','fail','success','duplicate','blocked','nocontacts','declined') NOT NULL,
  `iscontacted` tinyint(4) NOT NULL default '0',
  `numcontacts` tinyint(4) NOT NULL,
  `numduperemoved` tinyint(4) NOT NULL,
  `numdeclined` tinyint(4) NOT NULL default '0',
  `numblocked` tinyint(4) NOT NULL,
  `duplicateid` int(11) default NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `reportsubscription`
--

CREATE TABLE `reportsubscription` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `reportinstanceid` int(11) NOT NULL default '0',
  `type` enum('notscheduled','once','weekly','monthly') NOT NULL default 'notscheduled',
  `daysofweek` varchar(20) default NULL,
  `dayofmonth` tinyint(4) default NULL,
  `lastrun` datetime default NULL,
  `nextrun` datetime default NULL,
  `time` time default NULL,
  `email` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `subscription` (`userid`,`reportinstanceid`),
  KEY `nextrun` (`nextrun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) default NULL,
  `daysofweek` varchar(20) NOT NULL,
  `time` time NOT NULL default '00:00:00',
  `nextrun` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `lookup` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `sms`
--

CREATE TABLE `sms` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `sms` varchar(20) NOT NULL default '',
  `sequence` tinyint(4) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`),
  KEY `dedupe` (`sms`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `specialtask`
--

CREATE TABLE `specialtask` (
  `id` bigint(20) NOT NULL auto_increment,
  `status` enum('new','queued','assigned','done') NOT NULL,
  `type` varchar(50) NOT NULL default 'EasyCall',
  `data` text NOT NULL,
  `lastcheckin` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `status` (`status`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `surveyquestion`
--

CREATE TABLE `surveyquestion` (
  `id` int(11) NOT NULL auto_increment,
  `questionnaireid` int(11) NOT NULL,
  `questionnumber` tinyint(4) NOT NULL,
  `webmessage` text character set utf8 collate utf8_bin,
  `phonemessageid` int(11) default NULL,
  `reportlabel` text,
  `validresponse` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `surveyquestionnaire`
--

CREATE TABLE `surveyquestionnaire` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `name` varchar(50) character set utf8 collate utf8_bin NOT NULL,
  `description` varchar(50) character set utf8 collate utf8_bin NOT NULL,
  `hasphone` tinyint(4) NOT NULL default '0',
  `hasweb` tinyint(4) NOT NULL default '0',
  `dorandomizeorder` tinyint(4) NOT NULL default '0',
  `machinemessageid` int(11) default NULL,
  `emailmessageid` int(11) default NULL,
  `intromessageid` int(11) default NULL,
  `exitmessageid` int(11) default NULL,
  `webpagetitle` varchar(50) default NULL,
  `webexitmessage` text,
  `usehtml` tinyint(4) NOT NULL default '0',
  `leavemessage` tinyint(4) NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `surveyresponse`
--

CREATE TABLE `surveyresponse` (
  `jobid` int(11) NOT NULL,
  `questionnumber` tinyint(4) NOT NULL,
  `answer` tinyint(4) NOT NULL,
  `tally` int(11) NOT NULL default '0',
  PRIMARY KEY  (`jobid`,`questionnumber`,`answer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `surveyweb`
--

CREATE TABLE `surveyweb` (
  `code` char(22) character set ascii collate ascii_bin NOT NULL,
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `status` enum('noresponse','web','phone') NOT NULL,
  `dateused` datetime default NULL,
  `loggedip` varchar(15) character set utf8 collate utf8_bin default NULL,
  `resultdata` text character set utf8 collate utf8_bin NOT NULL,
  PRIMARY KEY  (`jobid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `systemstats`
--

CREATE TABLE `systemstats` (
  `jobid` int(11) NOT NULL,
  `attempt` tinyint(4) NOT NULL,
  `date` date NOT NULL,
  `hour` int(11) NOT NULL,
  `answered` int(11) NOT NULL,
  `machine` int(11) NOT NULL,
  `busy` int(11) NOT NULL,
  `noanswer` int(11) NOT NULL,
  `failed` int(11) NOT NULL,
  `disconnect` int(11) NOT NULL,
  PRIMARY KEY  (`jobid`,`attempt`,`date`,`hour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `ttsvoice`
--

CREATE TABLE `ttsvoice` (
  `id` int(11) NOT NULL auto_increment,
  `language` varchar(20) NOT NULL default '',
  `gender` enum('male','female') NOT NULL default 'male',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL auto_increment,
  `accessid` int(11) NOT NULL default '0',
  `login` varchar(20) character set utf8 collate utf8_bin NOT NULL,
  `password` varchar(255) NOT NULL default '',
  `accesscode` varchar(10) NOT NULL default '',
  `pincode` varchar(255) NOT NULL default '',
  `firstname` varchar(50) NOT NULL default '',
  `lastname` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `email` text NOT NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  `lastlogin` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  `ldap` tinyint(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `login` (`login`,`password`,`enabled`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `userjobtypes`
--

CREATE TABLE `userjobtypes` (
  `userid` int(11) NOT NULL default '0',
  `jobtypeid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`jobtypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `userrule`
--

CREATE TABLE `userrule` (
  `userid` int(11) NOT NULL default '0',
  `ruleid` int(11) NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `usersetting`
--

CREATE TABLE `usersetting` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `usersetting` (`userid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `voicereply`
--

CREATE TABLE `voicereply` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL,
  `sequence` tinyint(4) NOT NULL default '0',
  `jobid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `contentid` bigint(20) NOT NULL,
  `replytime` bigint(20) NOT NULL,
  `listened` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `jobid` (`jobid`),
  KEY `userid` (`userid`),
  KEY `replytime` (`replytime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

--
-- Triggers and Procedures
--

CREATE TRIGGER insert_repeating_job
AFTER INSERT ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER DEFAULT 1;

IF NEW.status IN ('repeating') THEN
  SELECT value INTO tz FROM setting WHERE name='timezone';

  INSERT INTO qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid, thesql)
         VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.listid, NEW.phonemessageid, NEW.emailmessageid, NEW.printmessageid, NEW.smsmessageid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, 'repeating', NEW.jobtypeid, NEW.thesql);

  -- copy the jobsettings
  INSERT INTO qjobsetting (customerid, jobid, name, value) SELECT custid, NEW.id, name, value FROM jobsetting WHERE jobid=NEW.id;

  -- do not copy schedule because it was inserted via the insert_schedule trigger

END IF;
END
$$$

CREATE TRIGGER update_job
AFTER UPDATE ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER DEFAULT 1;

SELECT value INTO tz FROM setting WHERE name='timezone';

SELECT COUNT(*) INTO cc FROM qjob WHERE customerid=custid AND id=NEW.id;
IF cc = 0 THEN
-- we expect the status to be 'scheduled' when we insert the shard job
-- status 'new' is for jobs that are not yet submitted
  IF NEW.status='scheduled' THEN
    INSERT INTO qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid, thesql)
           VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.listid, NEW.phonemessageid, NEW.emailmessageid, NEW.printmessageid, NEW.smsmessageid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, NEW.status, NEW.jobtypeid, NEW.thesql);
    -- copy the jobsettings
    INSERT INTO qjobsetting (customerid, jobid, name, value) SELECT custid, NEW.id, name, value FROM jobsetting WHERE jobid=NEW.id;
  END IF;
ELSE
-- update job fields
  UPDATE qjob SET scheduleid=NEW.scheduleid, phonemessageid=NEW.phonemessageid, emailmessageid=NEW.emailmessageid, printmessageid=NEW.printmessageid, smsmessageid=NEW.smsmessageid, questionnaireid=NEW.questionnaireid, starttime=NEW.starttime, endtime=NEW.endtime, startdate=NEW.startdate, enddate=NEW.enddate, thesql=NEW.thesql WHERE customerid=custid AND id=NEW.id;
  IF NEW.status IN ('processing', 'procactive', 'active', 'cancelling') THEN
    UPDATE qjob SET status=NEW.status WHERE customerid=custid AND id=NEW.id;
  END IF;
END IF;
END
$$$

CREATE TRIGGER delete_job
AFTER DELETE ON job FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
-- only repeating jobs ever get deleted
DELETE FROM qjob WHERE customerid=custid AND id=OLD.id;
DELETE FROM qjobsetting WHERE customerid=custid AND jobid=OLD.id;
END
$$$

CREATE TRIGGER insert_jobsetting
AFTER INSERT ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
DECLARE cc INTEGER;

-- the job must be inserted before the settings
SELECT COUNT(*) INTO cc FROM qjob WHERE customerid=custid AND id=NEW.jobid;
IF cc = 1 THEN
    INSERT INTO qjobsetting (customerid, jobid, name, value) VALUES (custid, NEW.jobid, NEW.name, NEW.value);
END IF;
END
$$$

CREATE TRIGGER update_jobsetting
AFTER UPDATE ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
UPDATE qjobsetting SET value=NEW.value WHERE customerid=custid AND jobid=NEW.jobid AND name=NEW.name;
END
$$$

CREATE TRIGGER delete_jobsetting
AFTER DELETE ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
DELETE FROM qjobsetting WHERE customerid=custid AND jobid=OLD.jobid AND name=OLD.name;
END
$$$

CREATE TRIGGER insert_schedule
AFTER INSERT ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
DECLARE tz VARCHAR(50);

SELECT value INTO tz FROM setting WHERE name='timezone';

INSERT INTO qschedule (id, customerid, daysofweek, time, nextrun, timezone) VALUES (NEW.id, custid, NEW.daysofweek, NEW.time, NEW.nextrun, tz);
END
$$$

CREATE TRIGGER update_schedule
AFTER UPDATE ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
UPDATE qschedule SET daysofweek=NEW.daysofweek, time=NEW.time, nextrun=NEW.nextrun WHERE id=NEW.id AND customerid=custid;
END
$$$

CREATE TRIGGER delete_schedule
AFTER DELETE ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
DELETE FROM qschedule WHERE id=OLD.id AND customerid=custid;
END
$$$

CREATE TRIGGER insert_reportsubscription
AFTER INSERT ON reportsubscription FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
DECLARE tz VARCHAR(50);
SELECT value INTO tz FROM setting WHERE name='timezone';
INSERT INTO qreportsubscription (id, customerid, userid, type, daysofweek, dayofmonth, time, timezone, nextrun, email) VALUES (NEW.id, custid, NEW.userid, NEW.type, NEW.daysofweek, NEW.dayofmonth, NEW.time, tz, NEW.nextrun, NEW.email);
END
$$$

CREATE TRIGGER update_reportsubscription
AFTER UPDATE ON reportsubscription FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
UPDATE qreportsubscription SET type=NEW.type, daysofweek=NEW.daysofweek, dayofmonth=NEW.dayofmonth, time=NEW.time, nextrun=NEW.nextrun, email=NEW.email WHERE id=NEW.id AND customerid=custid;
END
$$$

CREATE TRIGGER delete_reportsubscription
AFTER DELETE ON reportsubscription FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT 1;
DELETE FROM qreportsubscription WHERE id=OLD.id AND customerid=custid;
END
$$$

create procedure start_import( in_importid int)
begin
declare l_custid int DEFAULT 1;
insert ignore into importqueue (customerid,localimportid) values (l_custid,in_importid);
end
$$$

create procedure start_specialtask( in_specialtaskid int)
begin
declare l_custid int DEFAULT 1;
declare l_type varchar(50);
select type from specialtask where id=in_specialtaskid into l_type;
insert ignore into specialtaskqueue (customerid,localspecialtaskid,type) values (l_custid,in_specialtaskid,l_type);
end
$$$


-- ------------------------------------------------------------------------------------------------------------------------
-- authserver
-- ------------------------------------------------------------------------------------------------------------------------

-- phpMyAdmin SQL Dump
-- version 2.9.1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 05, 2007 at 04:07 PM
-- Server version: 5.0.27
-- PHP Version: 5.2.0
--
-- Database: `authserver`
--

-- --------------------------------------------------------

--
-- Table structure for table `aspadminuser`
--

CREATE TABLE `aspadminuser` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(20) collate utf8_bin NOT NULL,
  `password` varchar(255) character set utf8 NOT NULL,
  `firstname` varchar(50) character set utf8 NOT NULL,
  `lastname` varchar(50) character set utf8 NOT NULL,
  `email` varchar(100) character set utf8 NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `login` (`login`,`password`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin   $$$

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL auto_increment,
  `shardid` tinyint(4) NOT NULL,
  `urlcomponent` varchar(255) NOT NULL default '',
  `inboundnumber` varchar(20) NOT NULL default '',
  `dbusername` varchar(50) NOT NULL default '',
  `dbpassword` varchar(50) NOT NULL default '',
  `logintoken` varchar(255) NOT NULL default '',
  `logintokenexpiretime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `enabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `urlcomponent` (`urlcomponent`),
  KEY `inboundnumber` (`inboundnumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `persontoken`
--

CREATE TABLE `persontoken` (
  `customerid` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `validationdata` varchar(50) NOT NULL,
  `expirationdate` date NOT NULL,
  `personid` int(11) NOT NULL,
  PRIMARY KEY  (`customerid`,`token`,`validationdata`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `portalactivation`
--

CREATE TABLE `portalactivation` (
  `activationtoken` varchar(255) NOT NULL,
  `creation` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `portaluserid` int(11) NOT NULL default '0',
  `newusername` varchar(255) default NULL,
  `forgotpassword` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`activationtoken`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `portalcustomer`
--

CREATE TABLE `portalcustomer` (
  `portaluserid` int(11) NOT NULL,
  `customerid` int(11) NOT NULL,
  PRIMARY KEY  (`portaluserid`,`customerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `portaluser`
--

CREATE TABLE `portaluser` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(50) NOT NULL default ' ',
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `zipcode` varchar(10) NOT NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  `lastlogin` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `sessiondata`
--

CREATE TABLE `sessiondata` (
  `id` char(255) NOT NULL,
  `customerid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `lastused` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `lastused` (`lastused`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `shard`
--

CREATE TABLE `shard` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `dbhost` varchar(255) NOT NULL default '',
  `dbusername` varchar(50) NOT NULL default '',
  `dbpassword` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$


-- ------------------------------------------------------------------------------------------------------------------------
-- shard
-- ------------------------------------------------------------------------------------------------------------------------

-- phpMyAdmin SQL Dump
-- version 2.9.1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 05, 2007 at 04:10 PM
-- Server version: 5.0.27
-- PHP Version: 5.2.0
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `leasetask`
--

CREATE TABLE `leasetask` (
  `taskuuid` bigint(20) NOT NULL,
  `leasetime` bigint(20) NOT NULL,
  PRIMARY KEY  (`taskuuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
  `smsmessageid` int(11) default NULL,
  `questionnaireid` int(11) default NULL,
  `timezone` varchar(50) NOT NULL,
  `startdate` date NOT NULL default '0000-00-00',
  `enddate` date NOT NULL default '0000-00-00',
  `starttime` time NOT NULL default '00:00:00',
  `endtime` time NOT NULL default '00:00:00',
  `status` enum('scheduled','processing','procactive','active','cancelling','repeating') NOT NULL default 'scheduled',
  `phonetaskcount` int(11) NOT NULL default '0',
  `processedcount` int(11) NOT NULL default '0',
  `systempriority` tinyint(4) NOT NULL default '3',
  `timeslices` smallint(6) NOT NULL default '0',
  `jobtypeid` int(11) NOT NULL,
  `thesql` text,
  PRIMARY KEY  (`customerid`,`id`),
  KEY `status` (`status`,`id`),
  KEY `startdate` (`startdate`),
  KEY `enddate` (`enddate`),
  KEY `starttime` (`starttime`),
  KEY `endtime` (`endtime`),
  KEY `startdate_2` (`startdate`,`enddate`,`starttime`,`endtime`,`id`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

-- --------------------------------------------------------

--
-- Table structure for table `qjobperson`
--

CREATE TABLE `qjobperson` (
  `customerid` int(11) NOT NULL default '0',
  `jobid` int(11) NOT NULL default '0',
  `personid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`customerid`,`jobid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
  `lastresult` enum('A','M','N','B','X','F','sent','unsent','printed','notprinted','cancelling','endoflife') default NULL,
  `lastresultdata` text,
  `lastduration` float default NULL,
  `lastattempttime` bigint(20) default NULL,
  `nextattempttime` bigint(20) default NULL,
  `leasetime` bigint(20) default NULL,
  `phone` varchar(20) default NULL,
  `uuid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`uuid`),
  KEY `id` (`customerid`,`jobid`,`type`,`personid`,`sequence`),
  KEY `waiting` (`status`,`nextattempttime`),
  KEY `progresshandler` (`status`,`lastattempttime`),
  KEY `dispatch` (`status`,`customerid`,`jobid`,`type`,`attempts`,`sequence`),
  KEY `emailer` (`type`,`status`,`nextattempttime`),
  KEY `personid` (`personid`),
  KEY `expired` (`status`,`leasetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$


-- ------------------------------------------------------------------------------------------------------------------------
-- dmapi
-- ------------------------------------------------------------------------------------------------------------------------

-- phpMyAdmin SQL Dump
-- version 2.9.1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 05, 2007 at 05:03 PM
-- Server version: 5.0.27
-- PHP Version: 5.2.0
--
-- Database: `dmapi`
--

-- --------------------------------------------------------

--
-- Table structure for table `jobtaskactive`
--

CREATE TABLE `jobtaskactive` (
  `id` bigint(20) NOT NULL,
  `customerid` int(11) NOT NULL,
  `shardid` tinyint(4) NOT NULL,
  `tasktime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `renderedmessage` text character set utf8 NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `status` (`tasktime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin   $$$

-- --------------------------------------------------------

--
-- Table structure for table `jobtaskcomplete`
--

CREATE TABLE `jobtaskcomplete` (
  `id` bigint(20) NOT NULL,
  `customerid` int(11) NOT NULL,
  `shardid` tinyint(4) NOT NULL,
  `starttime` bigint(20) NOT NULL default '0',
  `duration` float default NULL,
  `result` enum('A','M','N','B','X','F','sent','unsent','printed','notprinted') collate utf8_bin NOT NULL,
  `resultdata` text character set utf8 NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin   $$$

-- --------------------------------------------------------


--
-- Table structure for table `specialtaskactive`
--

CREATE TABLE `specialtaskactive` (
  `id` varchar(50) collate utf8_bin NOT NULL,
  `customerid` int(11) NOT NULL,
  `specialtaskid` int(11) NOT NULL,
  `shardid` tinyint(4) NOT NULL,
  `type` varchar(50) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin   $$$

-- --------------------------------------------------------

--
-- Table structure for table `tasksyncdata`
--

CREATE TABLE `tasksyncdata` (
  `name` varchar(50) NOT NULL,
  `value` varchar(50) NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8   $$$


-- New table for destination labels
CREATE TABLE `destlabel` (
`type` VARCHAR( 10 ) NOT NULL ,
`sequence` TINYINT NOT NULL ,
`label` VARCHAR( 20 ) NOT NULL ,
PRIMARY KEY ( `type` , `sequence` )
) ENGINE = innodb
$$$
