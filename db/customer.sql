-- schema for some customer c_X

--
-- Table structure for table `access`
--

CREATE TABLE `access` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `alert`
--

CREATE TABLE `alert` (
  `id` int(11) NOT NULL auto_increment,
  `eventid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `sent` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `alertjob` (`personid`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `audiofile`
--

CREATE TABLE `audiofile` (
  `id` int(11) NOT NULL auto_increment,
  `messagegroupid` int(11) default NULL,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `contentid` bigint(20) NOT NULL default '0',
  `recorddate` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  `permanent` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `list` (`userid`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `blockeddestination`
--

CREATE TABLE `blockeddestination` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) default NULL,
  `description` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `type` enum('phone','sms','email') NOT NULL,
  `createdate` datetime default NULL,
  `failattempts` tinyint(4) default NULL,
  `blockmethod` enum('manual','pending','autoblock') NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `typedestination` (`type`,`destination`),
  KEY `userid` (`userid`,`type`),
  KEY `methoddate` (`blockmethod`,`createdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` bigint(20) NOT NULL auto_increment,
  `contenttype` varchar(255) NOT NULL default '',
  `data` LONGTEXT CHARACTER SET ascii COLLATE ascii_bin NOT NULL , -- 8.2/4  data is always base64, only need ascii. update to longtext for files > 16m
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `custdm`
--

CREATE TABLE `custdm` (
  `dmid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `enablestate` enum('new','active','disabled') NOT NULL,
  `telco_type` enum('Test','Asterisk','Jtapi') NOT NULL default 'Test',
  `routechange` tinyint(4) default NULL,
  `poststatus` mediumtext,
  PRIMARY KEY  (`dmid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `customercallstats`
--

CREATE TABLE `customercallstats` (
  `jobid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `finishdate` datetime default NULL,
  `attempted` int(11) default NULL,
  PRIMARY KEY  (`jobid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `destlabel`
--

CREATE TABLE `destlabel` (
  `type` enum('phone','email','sms') NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `label` varchar(20) NOT NULL,
  `notes` varchar(255) default NULL,
  PRIMARY KEY  (`type`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `dmcalleridroute`
--

CREATE TABLE `dmcalleridroute` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `callerid` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dmid` (`dmid`,`callerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `dmroute`
--

CREATE TABLE `dmroute` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `match` varchar(20) NOT NULL,
  `strip` int(11) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `suffix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dmid` (`dmid`,`match`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `dmschedule`
--

CREATE TABLE `dmschedule` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `daysofweek` varchar(20) NOT NULL,
  `starttime` time NOT NULL,
  `endtime` time NOT NULL,
  `resourcepercentage` float NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `email`
--

CREATE TABLE `email` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `email` varchar(100) NOT NULL default '',
  `sequence` tinyint(4) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  `editlockdate` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`),
  KEY `editlockdate` (`editlockdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `organizationid` int(11) NOT NULL,
  `sectionid` int(11) default NULL,
  `targetedmessageid` int(11) default NULL,
  `name` varchar(50) NOT NULL,
  `notes` text NOT NULL,
  `occurence` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `groupdata`
--

CREATE TABLE `groupdata` (
  `id` bigint(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL,
  `fieldnum` tinyint(4) NOT NULL,
  `value` varchar(255) NOT NULL,
  `importid` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `personfield` (`personid`,`fieldnum`),
  KEY `importid` (`importid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
  `notes` text,
  `status` enum('idle','queued','running','error') NOT NULL default 'idle',
  `type` enum('manual','automatic','list','addressbook') NOT NULL default 'manual',
  `datatype` enum('person','user','enrollment','section') NOT NULL default 'person',
  `ownertype` enum('system','user') NOT NULL default 'system',
  `updatemethod` enum('updateonly','update','full','createonly') NOT NULL default 'full',
  `lastrun` datetime default NULL,
  `data` longblob,
  `datamodifiedtime` datetime default NULL,
  `skipheaderlines` tinyint(4) NOT NULL default '0',
  `alertoptions` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uploadkey` (`uploadkey`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `importfield`
--

CREATE TABLE `importfield` (
  `id` int(11) NOT NULL auto_increment,
  `importid` int(11) NOT NULL default '0',
  `mapto` varchar(4) NOT NULL default '',
  `action` enum('copy','staticvalue','number','currency','date','lookup','curdate','numeric','currencyleadingzero') NOT NULL default 'copy',
  `mapfrom` tinyint(4) default NULL,
  `val` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `importjob`
--

CREATE TABLE `importjob` (
  `id` int(11) NOT NULL auto_increment,
  `importid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `importlogentry`
--

CREATE TABLE `importlogentry` (
  `id` bigint(20) NOT NULL auto_increment,
  `importid` int(11) NOT NULL,
  `severity` enum('info','error','warn') NOT NULL,
  `txt` varchar(255) NOT NULL,
  `linenum` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `job`
--

CREATE TABLE `job` (
  `id` int(11) NOT NULL auto_increment,
  `messagegroupid` int(11) default NULL,
  `userid` int(11) NOT NULL default '0',
  `scheduleid` int(11) default NULL,
  `jobtypeid` int(11) default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `questionnaireid` int(11) default NULL,
  `type` enum('notification','survey','alert') NOT NULL default 'notification',
  `createdate` datetime NOT NULL default '0000-00-00 00:00:00',
  `modifydate` datetime default NULL,
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
  PRIMARY KEY  (`id`),
  KEY `status` (`status`,`id`),
  KEY `startdate` (`startdate`),
  KEY `enddate` (`enddate`),
  KEY `starttime` (`starttime`),
  KEY `endtime` (`endtime`),
  KEY `startdate_2` (`startdate`,`enddate`,`starttime`,`endtime`,`id`),
  KEY `scheduleid` (`scheduleid`),
  KEY `ranautoreport` (`ranautoreport`,`status`),
  KEY `useraccess` (`userid`,`status`,`deleted`),
  KEY `modifydate` (`modifydate`),
  KEY `messagegroupid` (`messagegroupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `joblanguage`
--

CREATE TABLE `joblanguage` (
  `id` int(11) NOT NULL auto_increment,
  `jobid` int(11) NOT NULL default '0',
  `messageid` int(11) NOT NULL default '0',
  `type` enum('phone','email','print','sms') NOT NULL default 'phone',
  `language` varchar(255) NOT NULL default '',
  `translationeditlock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `jobid` (`jobid`,`language`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `joblist`
--

CREATE TABLE `joblist` (
  `id` int(11) NOT NULL auto_increment,
  `jobid` int(11) NOT NULL,
  `listid` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `jobid` (`jobid`,`listid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `jobsetting`
--

CREATE TABLE `jobsetting` (
  `jobid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `jobstats`
--

CREATE TABLE `jobstats` (
  `jobid` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY  (`jobid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `jobtype`
--

CREATE TABLE `jobtype` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `systempriority` tinyint(4) NOT NULL default '3',
  `info` varchar(255) NOT NULL,
  `issurvey` tinyint(4) NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `jobtypepref`
--

CREATE TABLE `jobtypepref` (
  `jobtypeid` int(11) NOT NULL,
  `type` enum('phone','email','print','sms') NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`jobtypeid`,`type`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `code` varchar(3) character set ascii NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `list`
--

CREATE TABLE `list` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `type` enum('person','section','alert') NOT NULL default 'person',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `modifydate` datetime default NULL,
  `lastused` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`name`,`deleted`),
  KEY `modifydate` (`modifydate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `listentry`
--

CREATE TABLE `listentry` (
  `id` int(11) NOT NULL auto_increment,
  `listid` int(11) NOT NULL default '0',
  `type` enum('rule','add','negate','organization','section') NOT NULL default 'add',
  `ruleid` int(11) default NULL,
  `personid` int(11) default NULL,
  `organizationid` int(11) default NULL,
  `sectionid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `type` (`personid`,`listid`),
  KEY `listrule` (`listid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `id` int(11) NOT NULL auto_increment,
  `messagegroupid` int(11) default NULL,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `type` enum('phone','email','print','sms') NOT NULL default 'phone',
  `subtype` varchar(20) NOT NULL,
  `data` text NOT NULL,
  `modifydate` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  `autotranslate` enum('none','translated','source','overridden') NOT NULL default 'none',
  `languagecode` varchar(3) character set ascii default NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`type`,`deleted`),
  KEY `modifydate` (`modifydate`),
  KEY `messagegroupid` (`messagegroupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `messageattachment`
--

CREATE TABLE `messageattachment` (
  `id` int(11) NOT NULL auto_increment,
  `messageid` int(11) NOT NULL,
  `contentid` bigint(20) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `size` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `messageid` (`messageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `messagegroup`
--

CREATE TABLE `messagegroup` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `type` enum('notification','targetedmessage','classroomtemplate') NOT NULL default 'notification',
  `name` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL,
  `modified` datetime NOT NULL,
  `lastused` datetime default NULL,
  `permanent` tinyint(4) NOT NULL default '1',
  `deleted` tinyint(4) NOT NULL default '0',
  `defaultlanguagecode` varchar(3) character set ascii NOT NULL default 'en',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `messagepart`
--

CREATE TABLE `messagepart` (
  `id` int(11) NOT NULL auto_increment,
  `messageid` int(11) NOT NULL default '0',
  `type` enum('A','T','V','I') NOT NULL default 'A',
  `audiofileid` int(11) default NULL,
  `imagecontentid` bigint(20) default NULL,
  `txt` text,
  `fieldnum` varchar(4) default NULL,
  `defaultvalue` varchar(255) default NULL,
  `voiceid` int(11) default NULL,
  `sequence` tinyint(4) NOT NULL default '0',
  `maxlen` smallint(6) default NULL,
  PRIMARY KEY  (`id`),
  KEY `messageid` (`messageid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `organization`
--

CREATE TABLE `organization` (
  `id` int(11) NOT NULL auto_increment,
  `orgkey` varchar(255) NOT NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `orgkey` (`orgkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `permission`
--

CREATE TABLE `permission` (
  `id` int(11) NOT NULL auto_increment,
  `accessid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `accessid` (`accessid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
  KEY `lastimport` (`importid`,`lastimport`),
  KEY `ownership` (`userid`),
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
  KEY `f20` (`f20`(20)),
  KEY `pkeysort` (`pkey`,`type`,`deleted`),
  KEY `f01` (`f01`),
  KEY `f02` (`f02`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `personassociation`
--

CREATE TABLE `personassociation` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL,
  `importid` int(11) default NULL,
  `importstatus` enum('none','checking','new') character set latin1 collate latin1_general_ci NOT NULL default 'none',
  `type` enum('organization','section','event') NOT NULL,
  `organizationid` int(11) default NULL,
  `sectionid` int(11) default NULL,
  `eventid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`),
  KEY `importid` (`importid`,`importstatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `persondatavalues`
--

CREATE TABLE `persondatavalues` (
  `id` int(11) NOT NULL auto_increment,
  `fieldnum` varchar(4) NOT NULL default '',
  `value` varchar(255) default NULL,
  `refcount` int(11) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `valuelookup` (`value`(50)),
  KEY `name` (`fieldnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `personsetting`
--

CREATE TABLE `personsetting` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `phone`
--

CREATE TABLE `phone` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `phone` varchar(20) NOT NULL default '',
  `sequence` tinyint(4) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  `editlockdate` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`),
  KEY `dedupe` (`phone`,`sequence`),
  KEY `editlockdate` (`editlockdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `portalperson`
--

CREATE TABLE `portalperson` (
  `portaluserid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `notifyemail` varchar(100) default NULL,
  `notifysms` varchar(20) default NULL,
  PRIMARY KEY  (`portaluserid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `portalpersontoken`
--

CREATE TABLE `portalpersontoken` (
  `token` varchar(255) NOT NULL,
  `expirationdate` date NOT NULL,
  `personid` int(11) NOT NULL,
  `creationuserid` int(11) NOT NULL,
  PRIMARY KEY  (`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `prompt`
--

CREATE TABLE `prompt` (
  `id` int(11) NOT NULL auto_increment,
  `type` enum('intro','emergencyintro','langmenu') NOT NULL,
  `messageid` int(11) NOT NULL,
  `dtmf` tinyint(4) default NULL,
  `languagecode` varchar(3) character set ascii NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `publish`
--

CREATE TABLE `publish` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `action` enum('publish','subscribe') NOT NULL,
  `type` enum('messagegroup') NOT NULL,
  `messagegroupid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`),
  KEY `messagegroupid` (`messagegroupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `reportarchive`
--

CREATE TABLE `reportarchive` (
  `name` varchar(50) NOT NULL,
  `contentid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
  `resultdata` varchar(255) default NULL,
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
  `voicereplyid` int(11) default NULL,
  `response` tinyint(4) default NULL,
  `dispatchtype` enum('customer','system') NOT NULL default 'system',
  PRIMARY KEY  (`jobid`,`type`,`personid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `reportgroupdata`
--

CREATE TABLE `reportgroupdata` (
  `id` bigint(11) NOT NULL auto_increment,
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `fieldnum` tinyint(4) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `jobpersonfield` (`jobid`,`personid`,`fieldnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
  `modifydate` datetime default NULL,
  `email` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `subscription` (`userid`,`reportinstanceid`),
  KEY `nextrun` (`nextrun`),
  KEY `modifydate` (`modifydate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `rule`
--

CREATE TABLE `rule` (
  `id` int(11) NOT NULL auto_increment,
  `logical` enum('and','or','and not','or not') NOT NULL default 'and',
  `fieldnum` varchar(4) NOT NULL default '0',
  `op` enum('eq','ne','sw','ew','cn','in','reldate','date_range','num_eq','num_ne','num_gt','num_ge','num_lt','num_le','num_range','date_offset','reldate_range') NOT NULL default 'eq',
  `val` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `id` int(11) NOT NULL auto_increment,
  `skey` varchar(255) NOT NULL,
  `organizationid` int(11) NOT NULL,
  `c01` varchar(255) NOT NULL,
  `c02` varchar(255) NOT NULL,
  `c03` varchar(255) NOT NULL,
  `c04` varchar(255) NOT NULL,
  `c05` varchar(255) NOT NULL,
  `c06` varchar(255) NOT NULL,
  `c07` varchar(255) NOT NULL,
  `c08` varchar(255) NOT NULL,
  `c09` varchar(255) NOT NULL,
  `c10` varchar(255) NOT NULL,
  `importid` int(11) default NULL,
  `importstatus` enum('none','checking','new') character set latin1 collate latin1_general_ci NOT NULL default 'none',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `skey` (`organizationid`,`skey`),
  KEY `importid` (`importid`,`importstatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$


--
-- Table structure for table `sms`
--

CREATE TABLE `sms` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `sms` varchar(20) NOT NULL default '',
  `sequence` tinyint(4) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  `editlockdate` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`),
  KEY `dedupe` (`sms`,`sequence`),
  KEY `editlockdate` (`editlockdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `specialtask`
--

CREATE TABLE `specialtask` (
  `id` bigint(20) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `status` enum('new','queued','assigned','done') NOT NULL,
  `type` varchar(50) NOT NULL default 'EasyCall',
  `data` text NOT NULL,
  `lastcheckin` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `status` (`status`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `subscriber`
--

CREATE TABLE `subscriber` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) collate utf8_general_ci NOT NULL,
  `password` varchar(50) collate utf8_bin NOT NULL,
  `personid` int(11) default NULL,
  `lastlogin` datetime default NULL,
  `lastreminder` datetime default NULL,
  `enabled` tinyint(4) NOT NULL,
  `preferences` text collate utf8_bin,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin 
$$$

--
-- Table structure for table `subscriberpending`
--

CREATE TABLE `subscriberpending` (
  `id` int(11) NOT NULL auto_increment,
  `subscriberid` int(11) NOT NULL,
  `type` enum('phone','email','sms') collate utf8_bin NOT NULL,
  `value` varchar(255) collate utf8_bin NOT NULL,
  `token` varchar(255) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin 
$$$

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
  PRIMARY KEY  (`id`),
  KEY `questionnaireid` (`questionnaireid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `surveyresponse`
--

CREATE TABLE `surveyresponse` (
  `jobid` int(11) NOT NULL,
  `questionnumber` tinyint(4) NOT NULL,
  `answer` tinyint(4) NOT NULL,
  `tally` int(11) NOT NULL default '0',
  PRIMARY KEY  (`jobid`,`questionnumber`,`answer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `systemmessages`
--

CREATE TABLE `systemmessages` (
  `id` int(11) NOT NULL auto_increment,
  `message` varchar(1000) NOT NULL,
  `icon` varchar(50) default NULL,
  `modifydate` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `modifydate` (`modifydate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `systemstats`
--

CREATE TABLE `systemstats` (
  `jobid` int(11) NOT NULL,
  `attempt` tinyint(4) NOT NULL default '0',
  `date` date NOT NULL,
  `hour` int(11) NOT NULL,
  `answered` int(11) NOT NULL,
  `machine` int(11) NOT NULL,
  `busy` int(11) NOT NULL,
  `noanswer` int(11) NOT NULL,
  `failed` int(11) NOT NULL,
  `disconnect` int(11) NOT NULL,
  PRIMARY KEY  (`jobid`,`attempt`,`date`,`hour`),
  KEY `graphs` (`date`,`attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `targetedmessage`
--

CREATE TABLE `targetedmessage` (
  `id` int(11) NOT NULL auto_increment,
  `messagekey` varchar(255) NOT NULL,
  `targetedmessagecategoryid` int(11) NOT NULL,
  `overridemessagegroupid` int(11) default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  `enabled` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `targetedmessagecategory`
--

CREATE TABLE `targetedmessagecategory` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  `image` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `ttsvoice`
--

CREATE TABLE `ttsvoice` (
  `id` int(11) NOT NULL auto_increment,
  `language` varchar(20) NOT NULL default '',
  `languagecode` varchar(3) character set ascii NOT NULL,
  `gender` enum('male','female') NOT NULL default 'male',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

INSERT INTO `ttsvoice` (`languagecode`, `language`, `gender`) VALUES
	('en', 'english', 'male'),
	('en', 'english', 'female'),
	('es', 'spanish', 'male'),
	('es', 'spanish', 'female'),
	('ca', 'catalan', 'female'),
	('ca', 'catalan', 'male'),
	('zh', 'chinese', 'female'),
	('nl', 'dutch', 'female'),
	('nl', 'dutch', 'male'),
	('fi', 'finnish', 'female'),
	('fr', 'french', 'female'),
	('fr', 'french', 'male'),
	('de', 'german', 'female'),
	('de', 'german', 'male'),
	('el', 'greek', 'female'),
	('it', 'italian', 'female'),
	('it', 'italian', 'male'),
	('pl', 'polish', 'female'),
	('pl', 'polish', 'male'),
	('pt', 'portuguese', 'female'),
	('pt', 'portuguese', 'male'),
	('ru', 'russian', 'female'),
	('sv', 'swedish', 'female'),
	('sv', 'swedish', 'male')
$$$


--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL auto_increment,
  `accessid` int(11) NOT NULL default '0',
  `login` varchar(20) character set utf8 collate utf8_bin NOT NULL,
  `password` varchar(50) NOT NULL,
  `accesscode` varchar(10) NOT NULL default '',
  `pincode` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL default '',
  `lastname` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `email` varchar(255) NOT NULL,
  `aremail` text NOT NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  `lastlogin` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  `ldap` tinyint(10) NOT NULL default '0',
  `staffpkey` varchar(255) default NULL,
  `importid` int(11) default NULL,
  `lastimport` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `login` (`login`,`password`,`enabled`,`deleted`),
  KEY `staffpkey` (`staffpkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `userassociation`
--

CREATE TABLE `userassociation` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `importid` int(11) default NULL,
  `importstatus` enum('none','checking','new') character set latin1 collate latin1_general_ci NOT NULL default 'none',
  `type` enum('rule','organization','section') NOT NULL default 'rule',
  `organizationid` int(11) default NULL,
  `sectionid` int(11) default NULL,
  `ruleid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`),
  KEY `importid` (`importid`,`importstatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

--
-- Table structure for table `userjobtypes`
--

CREATE TABLE `userjobtypes` (
  `userid` int(11) NOT NULL default '0',
  `jobtypeid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`jobtypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 
$$$

INSERT INTO `setting` (`name`, `value`) values ('_dbversion', '7.5/8')
$$$
-- END REV 7.5/8

-- START REV 7.5/9
ALTER TABLE `section` DROP INDEX `skey` ,
ADD UNIQUE `skey` ( `organizationid` , `skey` )
$$$

ALTER TABLE `publish` ADD INDEX ( `action` , `userid` )
$$$

drop table joblanguage
$$$

drop table jobstats
$$$

ALTER TABLE `personassociation` ADD INDEX ( `sectionid` )
$$$

ALTER TABLE `personassociation` ADD INDEX ( `organizationid` )
$$$

ALTER TABLE `personassociation` ADD INDEX ( `eventid` )
$$$

update setting set value='7.5/9' where name='_dbversion';
$$$
-- END REV 7.5/9

-- START REV 7.5/10
CREATE TABLE `reportorganization` (
 `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY , 
 `jobid` INT NOT NULL , 
 `personid` INT NOT NULL , 
 `organizationid` INT NOT NULL , 
 INDEX ( `jobid` , `personid` ) 
) ENGINE = InnoDB 
$$$ 

ALTER TABLE `audiofile` ADD INDEX ( `messagegroupid` )
$$$

ALTER TABLE `publish` ADD `listid` INT NULL AFTER `messagegroupid`
$$$
ALTER TABLE `publish` CHANGE `type` `type` ENUM( 'messagegroup', 'list' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL 
$$$
ALTER TABLE `publish` ADD `organizationid` INT NULL AFTER `listid`
$$$

ALTER TABLE `publish` ADD INDEX ( `organizationid` )
$$$
ALTER TABLE `publish` ADD INDEX ( `listid` )
$$$

update setting set value='7.5/10' where name='_dbversion'
$$$
-- END REV 7.5/10

-- START REV 7.5/11

update setting set value='7.5/11' where name='_dbversion'
$$$
-- END REV 7.5/11

-- START REV 7.5/12

update setting set value='7.5/12' where name='_dbversion'
$$$
-- END REV 7.5/12

-- START REV 7.5/13 and /14

update setting set value='7.5/14' where name='_dbversion'
$$$
-- END REV 7.5/14

-- START REV 7.6/0

update setting set value='7.6/0' where name='_dbversion'
$$$
-- END REV 7.6/0

-- START REV 7.7/1

--
-- Table structure for table `reportarchive`
--
ALTER TABLE `reportarchive` CHANGE `name` `reportdate` DATE NOT NULL 
$$$

ALTER TABLE `reportarchive` ADD INDEX ( `reportdate` ) 
$$$

ALTER TABLE `reportarchive` CHANGE `contentid` `contentid` BIGINT NULL
$$$

update setting set value='7.7/1' where name='_dbversion'
$$$
-- END REV 7.7/1

-- $rev 2

ALTER TABLE `email` ADD INDEX `dedupe` ( `email` , `sequence` )
$$$

-- $rev 3
ALTER TABLE `messagegroup` ADD `originalmessagegroupid` INT NULL AFTER `id` 
$$$

-- $rev 4
ALTER TABLE `subscriber` ADD INDEX `lastlogin` ( `lastlogin` , `enabled` , `personid` )
$$$

-- $rev 5
ALTER TABLE `language` CHANGE `code` `code` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL 
$$$

-- $rev 6
-- empty rev to revert incorrect insert into customer settings for enabling the hassmapi setting
$$$

update setting set value='7.7/6' where name='_dbversion'
$$$
-- END REV 7.7/6


-- START REV 7.8/1

ALTER TABLE `user` ADD `salt` VARCHAR( 29 ) NOT NULL AFTER `password` ,
ADD `passwordversion` TINYINT NOT NULL AFTER `salt` 
$$$

ALTER TABLE `subscriber` ADD `salt` VARCHAR( 29 ) NOT NULL AFTER `password` ,
ADD `passwordversion` TINYINT NOT NULL AFTER `salt` 
$$$


-- $rev 2

CREATE TABLE `template` (
 `type` varchar(20) NOT NULL,
 `messagegroupid` int(11) NOT NULL,
 PRIMARY KEY  (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `messagegroup` CHANGE `type` `type` ENUM( 'notification', 'targetedmessage', 'classroomtemplate', 'systemtemplate' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'notification'
$$$

-- $rev 3

-- $rev 4

-- $rev 5

-- $rev 6

ALTER TABLE `messagegroup` CHANGE `userid` `userid` INT( 11 ) NULL DEFAULT NULL
$$$

ALTER TABLE `message` CHANGE `userid` `userid` INT( 11 ) NULL DEFAULT NULL
$$$

update setting set value='7.8/6' where name='_dbversion'
$$$
-- END REV 7.8/6

-- $rev 7

ALTER TABLE `reportperson` DROP `messageid`
$$$

update setting set value='7.8/7' where name='_dbversion'
$$$
-- END REV 7.8/7


-- START REV 8.0/1

-- add post type
ALTER TABLE `message` CHANGE `type` `type` ENUM( 'phone', 'email', 'print', 'sms', 'post' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'phone'
$$$

-- new table for post type messages sent by a job
CREATE TABLE jobpost (
jobid int NOT NULL,
 `type` enum ('facebook','twitter','page') NOT NULL,
destination varchar(255) NOT NULL,
posted tinyint(1) NOT NULL DEFAULT 0,
PRIMARY KEY(jobid, type, destination),
INDEX pagecode(destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

update setting set value='8.0/1' where name='_dbversion'
$$$
-- END REV 8.0/1

-- woops forgot 8.0/2
-- start 8.0/3

INSERT INTO systemmessages (message, icon, modifydate)
VALUES (
'<div style="color:#3e693f;font-size: 20px;font-weight: bold;">Welcome New User</div>
  <ul>
  <li>See the Getting Started Guide: <a href="#" onclick="window.open(\'help/index.php\', \'_blank\', \'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes\');"><img src="img/icons/page_white_acrobat.gif" /> Getting Started</a> 
  </ul>', 'largeicons/news.jpg', '2000-01-01 01:02:34'
)
$$$

update setting set value='8.0/3' where name='_dbversion'
$$$

-- END REV 8.0/3

-- start 8.0/4
INSERT ignore INTO `setting` (
`id` ,
`name` ,
`value`
)
VALUES (
NULL , 'fbauthorizewall', '1'
)
$$$

update setting set value='8.0/4' where name='_dbversion'
$$$
-- END REV 8.0/4

-- $rev 5

-- fix password to allow NULL
ALTER TABLE `subscriber` CHANGE `password` `password` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
CHANGE `salt` `salt` VARCHAR( 29 ) CHARACTER SET utf8 COLLATE utf8_bin NULL,
CHANGE `passwordversion` `passwordversion` TINYINT( 4 ) NOT NULL DEFAULT '0'
$$$

-- fix password to allow NULL
ALTER TABLE `user` CHANGE `password` `password` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
CHANGE `salt` `salt` VARCHAR( 29 ) CHARACTER SET utf8 COLLATE utf8_bin NULL,
CHANGE `passwordversion` `passwordversion` TINYINT( 4 ) NOT NULL DEFAULT '0',
CHANGE `pincode` `pincode` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
$$$

update setting set value='8.0/5' where name='_dbversion'
$$$
-- END REV 8.0/5

-- start 8.1/1
-- add dm notes
ALTER TABLE `custdm` ADD `notes` TEXT;
$$$

update setting set value='8.1/1' where name='_dbversion'
$$$
-- END REV 8.1/1

-- start 8.1/2

-- Add categories for alerts to seperate customer and manager alerts and any future category needs
CREATE TABLE `importalertcategory` (
    `id` int(11) NOT NULL auto_increment,
    `name` varchar(50) NOT NULL,
    `emails` TEXT,
 	 PRIMARY KEY  (`id`)
) ENGINE = InnoDB;
$$$

-- New table to keep track of import alert rules instead of a json encoding string
CREATE TABLE `importalertrule` (
	`id` int(11) NOT NULL auto_increment,
	`importid` INT NOT NULL,
	`categoryid` INT NOT NULL,
	`name` varchar(50) NOT NULL,
    `operation` enum('eq','ne','gt','lt') NOT NULL,
    `testvalue` INT NOT NULL,
    `daysofweek` varchar(20) NOT NULL,
 	 PRIMARY KEY  (`id`)
) ENGINE = InnoDB;
$$$

-- Add field for netsuite integration   
ALTER TABLE `import` ADD `nsticketid` VARCHAR( 50 ) NOT NULL default '';
$$$

-- Add notes field that is not visible to the customer 
ALTER TABLE `import` ADD `managernotes` TEXT;
$$$

update setting set value='8.1/2' where name='_dbversion'
$$$
-- END REV 8.1/2
-- start 8.1/3
-- Add notes data length field to avoid calling length(data) on import reports
ALTER TABLE `import` ADD `datalength` int(11) NOT NULL DEFAULT 0 AFTER `data` 
$$$

INSERT INTO `importalertcategory` (`name`) VALUES
	('manager'),
	('customer')
$$$

update setting set value='8.1/3' where name='_dbversion'
$$$
-- END REV 8.1/3


-- START 8.1/4

-- case insensitive user logins
ALTER TABLE `user` CHANGE `login` `login` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL 
$$$

update setting set value='8.1/4' where name='_dbversion'
$$$

-- engine for jobpost corrected above

-- END REV 8.1/4

-- 8.1/5 inserted customer.setting _customerenabled which is set in customeredit.php for new customers
update setting set value='8.1/5' where name='_dbversion'
$$$

-- END REV 8.1/5

-- START 8.1/6
ALTER TABLE `message` DROP `deleted`
$$$

ALTER TABLE `messageattachment` DROP `deleted`
$$$

update setting set value='8.1/6' where name='_dbversion'
$$$
-- END REV 8.1/6

-- START 8.1/7
ALTER TABLE `messagegroup` DROP `originalmessagegroupid`
$$$

update setting set value='8.1/7' where name='_dbversion'
$$$
-- END REV 8.1/7

-- START 8.1/8
ALTER TABLE `import` DROP `nsticketid`
$$$

update setting set value='8.1/8' where name='_dbversion'
$$$
-- END REV 8.1/8

-- START 8.1/9
ALTER TABLE `importalertcategory` CHANGE `name` `type` ENUM( 'customer', 'manager' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

update setting set value='8.1/9' where name='_dbversion'
$$$
-- END REV 8.1/9

-- START 8.1/10
ALTER TABLE `job` ADD `activedate` DATETIME default NULL AFTER `modifydate` 
$$$

CREATE TABLE `jobstats` (
 `jobid` int(11) NOT NULL,
 `name` varchar(255) NOT NULL,
 `value` int(11) NOT NULL,
 PRIMARY KEY (`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

update setting set value='8.1/10' where name='_dbversion'
$$$
-- END REV 8.1/10



-- START 8.2/1
INSERT INTO fieldmap (id, fieldnum, name, options) VALUES 
	(NULL , '$d01', '%Date%', 'text,systemvar'),
	(NULL , '$d02', '%Tomorrow\'s Date%', 'text,systemvar'),
	(NULL , '$d03', '%Yesterday\'s Date%', 'text,systemvar')
$$$
update setting set value='8.2/1' where name='_dbversion'
$$$
-- END 8.2/1

-- START 8.2/2
CREATE TABLE `monitor` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`userid` int(11) NOT NULL,
	`type` enum('job-active','job-firstpass','job-complete') NOT NULL,
	`action` enum('email') NOT NULL DEFAULT 'email',
	PRIMARY KEY (`id`),
	KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8
$$$


CREATE TABLE `monitorfilter` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`monitorid` int(11) NOT NULL,
	`type` enum('userid','jobtypeid') NOT NULL,
	`val` text,
	PRIMARY KEY (`id`),
	KEY `monitorid` (`monitorid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
$$$

update setting set value='8.2/2' where name='_dbversion'
$$$
-- END 8.2/2

-- START 8.2/3
ALTER TABLE `surveyquestion` CHANGE `questionnumber` `questionnumber` INT NOT NULL 
$$$
ALTER TABLE `surveyresponse` CHANGE `questionnumber` `questionnumber` INT NOT NULL 
$$$
update setting set value='8.2/3' where name='_dbversion'
$$$
-- END 8.2/3


-- START 8.2/4
-- change made inline, see content table
update setting set value='8.2/4' where name='_dbversion'
$$$
-- END 8.2/4

-- START 8.2/5

CREATE TABLE `feedcategory` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(50) NOT NULL,
 `description` TEXT NOT NULL,
 `deleted` tinyint(4) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `userfeedcategory` (
 `userid` int(11) NOT NULL,
 `feedcategoryid` int(11) NOT NULL,
 PRIMARY KEY (`userid`,`feedcategoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `jobpost` CHANGE `type` `type` ENUM( 'facebook', 'twitter', 'page', 'feed' ) NOT NULL 
$$$

update setting set value='8.2/5' where name='_dbversion'
$$$

-- END 8.2/5

-- START 8.2/6

update setting set value='8.2/6' where name='_dbversion'
$$$
-- END 8.2/6

-- START 8.2/7

CREATE TABLE `authorizedcallerid` (
	`callerid` varchar(20),
	PRIMARY KEY (`callerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
$$$

CREATE TABLE `authorizedusercallerid` (
	`userid` int(11) NOT NULL,
	`callerid` varchar(20) NOT NULL,
	PRIMARY KEY (`userid`,`callerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
$$$

update setting set value='8.2/7' where name='_dbversion'
$$$
-- END 8.2/7

-- START 8.2/8
-- smadmin monitor
update setting set value='8.2/8' where name='_dbversion'
$$$
-- END 8.2/8

-- START 8.2/9
-- monitor template
update setting set value='8.2/9' where name='_dbversion'
$$$
-- END 8.2/9

-- START 8.2/10

-- woops, remove job index startdate transform to startdate,starttime instead we use activedate in rev11

update setting set value='8.2/10' where name='_dbversion'
$$$
-- END 8.2/10

-- START 8.2/11

-- index for feed generator
ALTER TABLE `job` ADD INDEX `activedate` ( `activedate` ) 
$$$

update setting set value='8.2/11' where name='_dbversion'
$$$
-- END 8.2/11


-- START 8.3/1

ALTER TABLE `person` CHANGE `type` `type` ENUM( 'system', 'addressbook', 'manualadd', 'upload', 'subscriber',  'guardianauto',  'guardiancm') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'system'
$$$

update setting set value='8.3/1' where name='_dbversion'
$$$
-- END 8.3/1

-- stuff moved out
update setting set value='8.3/2' where name='_dbversion'
$$$
-- END 8.3/2

-- need more chars for descriptive names
ALTER TABLE `template` CHANGE `type` `type` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

update setting set value='8.3/3' where name='_dbversion'
$$$
-- END 8.3/3

-- allow mapping guardian fields on person import
ALTER TABLE `importfield`
  ADD `guardiansequence` TINYINT( 4 ) NULL DEFAULT NULL AFTER `importid`
$$$

CREATE TABLE `guardiancategory` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(50) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `personguardian` (
  `personid` int(11) NOT NULL,
  `guardianpersonid` int(11) NOT NULL,
  `guardiancategoryid` int(11) NOT NULL,
  `importid` int(11) NULL,
  `importstatus` enum('none','checking','new') DEFAULT NULL,
  PRIMARY KEY (`personid`,`guardianpersonid`,`guardiancategoryid`),
  INDEX guardian ( `guardianpersonid`,`guardiancategoryid` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

update setting set value='8.3/4' where name='_dbversion'
$$$
-- END 8.3/4

CREATE TABLE `importmicroupdate` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `importid` int(11) NOT NULL,
 `updatemethod` ENUM( 'update', 'delete' ) NOT NULL,
 `data` blob NOT NULL,
 `datalength` int(11) NOT NULL,
 `datamodifiedtime` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `importid` (`importid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

update setting set value='8.3/5' where name='_dbversion'
$$$
-- END 8.3/5

ALTER table `guardiancategory`
ADD `sequence` tinyint(4) NOT NULL
$$$

update setting set value='8.3/6' where name='_dbversion'
$$$
-- END 8.3/6

ALTER TABLE user 
    ADD globaluserid int DEFAULT NULL,
    ADD personid int DEFAULT NULL
$$$
 
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `profileid` int(11) NOT NULL,
  `organizationid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
ALTER TABLE `organization` 
    ADD `parentorganizationid` INT NULL AFTER `id`,
    ADD `createdtimestamp` INT DEFAULT NULL ,
    ADD `modifiedtimestamp` INT DEFAULT NULL
$$$
 
ALTER TABLE `setting` ADD `organizationid` INT NULL AFTER `id`
$$$

update setting set value='8.3/7' where name='_dbversion'
$$$
-- END 8.3/7

ALTER TABLE `job` CHANGE `status` `status` ENUM( 'new', 'scheduled', 'processing', 'procactive', 'active', 'complete', 'cancelled', 'cancelling', 'repeating', 'template' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'new'
$$$

update setting set value='8.3/8' where name='_dbversion'
$$$
-- END 8.3/8

CREATE TABLE `userlink` (
  `userid` int(11) NOT NULL,
  `subordinateuserid` int(11) NOT NULL,
  PRIMARY KEY (`userid`,`subordinateuserid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

update setting set value='8.3/9' where name='_dbversion'
$$$
-- END 8.3/9

-- START 8.3/10

--
-- Table structure for table `reportemaildelivery`
--
CREATE TABLE `reportemaildelivery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` bigint(20) NOT NULL,
  `jobid` int(11) DEFAULT NULL,
  `personid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `sequence` int(11) DEFAULT NULL,
  `replytoname` varchar(100) NOT NULL,
  `replytodomain` varchar(100) NOT NULL,
  `recipientname` varchar(100) NOT NULL,
  `recipientdomain` varchar(100) NOT NULL,
  `statuscode` smallint(4) unsigned NOT NULL,
  `responsetext` text NOT NULL,
  `recordsource` enum('customer_job','contact_manager','password_reset','report_subscription','subscriber_expiration','cm_password_reset','job_monitor','internal_monitoring') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`timestamp`),
  KEY `recipient` (`timestamp`,`recipientname`,`recipientdomain`),
  KEY `status` (`timestamp`,`statuscode`),
  KEY `source` (`timestamp`,`recordsource`),
  KEY `user` (`timestamp`,`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=17838 DEFAULT CHARSET=utf8
$$$

update setting set value='8.3/10' where name='_dbversion'
$$$

-- END 8.3/10

-- rename globaluser to portaluser
ALTER TABLE  `user` CHANGE  `globaluserid`  `portaluserid` INT( 11 ) NULL DEFAULT NULL,
  add portaluserassociationtimestamp int default null after portaluserid
$$$

-- fix unique index
ALTER TABLE  `setting` CHANGE  `organizationid`  `organizationid` INT( 11 ) NOT NULL DEFAULT  '0',
  DROP INDEX  `name` ,
  ADD UNIQUE  `name` (  `name` ,  `organizationid` )
$$$

-- rename field to match access table
ALTER TABLE  `role` CHANGE  `profileid`  `accessid` INT( 11 ) NOT NULL,
  ADD  `importid` INT NULL ,
  ADD  `importstatus` ENUM(  'none',  'checking',  'new' ) NOT NULL DEFAULT  'none'
$$$

update setting set value='8.3/11' where name='_dbversion'
$$$

-- END 8.3/11

-- START 8.3/12
ALTER TABLE `custdm` DROP `poststatus`
$$$
ALTER TABLE `custdm` ADD `dmuuid` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `dmid`
$$$
update setting set value='8.3/12' where name='_dbversion'
$$$
-- END 8.3/12

-- ------------------------------
-- START 9.1/1

-- need more chars
ALTER TABLE  `importfield` CHANGE  `mapto`  `mapto` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''
$$$

update setting set value='9.1/1' where name='_dbversion'
$$$
-- END 9.1/1

-- START 9.1/2
CREATE TABLE IF NOT EXISTS `authenticationprovider` (
  `type` enum('powerschool') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  PRIMARY KEY (`type`,`endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
update setting set value='9.1/2' where name='_dbversion'
$$$
-- END 9.1/2
-- START 9.1/3
update setting set value='9.1/3' where name='_dbversion'
$$$
-- END 9.1/3
-- START 9.1/4
ALTER TABLE role ADD INDEX `userorganization` ( `userid` , `organizationid` ) 
$$$
update setting set value='9.1/4' where name='_dbversion'
$$$
-- END 9.1/4

-- START 9.2/1
DROP TABLE `authenticationprovider`
$$$
update setting set value='9.2/1' where name='_dbversion'
$$$
-- END 9.2/1

-- START 9.3/1

-- jobtype rename to notificationtype using 'messaging' for tai
ALTER TABLE jobtype ADD type enum ('job','survey','messaging') DEFAULT 'job'
$$$
UPDATE jobtype SET type='survey' WHERE issurvey
$$$
ALTER TABLE jobtype DROP issurvey
$$$
RENAME TABLE jobtype TO notificationtype
$$$
CREATE VIEW jobtype AS SELECT id, name, systempriority, info, deleted, type='survey' AS issurvey FROM notificationtype WHERE type IN ('job','survey')
$$$

-- tai user display preference
ALTER TABLE  `role` ADD  `userdisplayname` VARCHAR( 255 ) NULL
$$$

update setting set value='9.3/1' where name='_dbversion'
$$$
-- END 9.3/1

-- START 9.4/1
update setting set value='9.4/1' where name='_dbversion'
$$$
-- END 9.4/1

-- change default from 0 to null
ALTER TABLE  `setting` CHANGE  `organizationid`  `organizationid` INT( 11 ) NULL DEFAULT NULL
$$$

update setting set value='9.4/2' where name='_dbversion'
$$$
-- END 9.4/2

-- START 9.5/1
ALTER TABLE `messagegroup` CHANGE `type` `type` ENUM( 'notification', 'targetedmessage', 'classroomtemplate', 'systemtemplate', 'stationery' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'notification'
$$$

update setting set value='9.5/1' where name='_dbversion'
$$$
-- END 9.5/1

-- START 9.5/2
ALTER TABLE `setting` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '', CHANGE `value` `value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

update setting set value='9.5/2' where name='_dbversion'
$$$
-- END 9.5/2

-- START 9.5/3

-- bug CS-4311
insert into setting (name, value) values ('_dbupgrade_inprogress', 'none')
$$$

update setting set value='9.5/3' where name='_dbversion'
$$$
-- END 9.5/3

-- START 9.6/1
-- no schema only insert into authserver.customerproduct

update setting set value='9.6/1' where name='_dbversion'
$$$
-- END 9.6/1

-- Fix any settings which were inserted earlier.
-- NOTE: This does not belong in an upgrade script! Only effects new customers where _dbversion and fbauthorizewall have been inserted above
update setting set organizationid = null
$$$

-- START 9.7/1
CREATE TABLE IF NOT EXISTS `reportemailtracking` (
	`jobid` int(11) NOT NULL,
	`personid` int(11) NOT NULL,
	`sequence` tinyint(4) NOT NULL,
	`timestampms` bigint(20) NOT NULL,
	`numrequests` int(11) NOT NULL,
	`requestduration` text NOT NULL,
	UNIQUE KEY `jobid` (`jobid`,`personid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
$$$

update setting set value='9.7/1' where name='_dbversion'
$$$
-- END 9.7/1

-- START 9.7/2
ALTER TABLE `reportemailtracking` CHANGE `requestduration` `requestduration` BIGINT NOT NULL
$$$

update setting set value='9.7/2' where name='_dbversion'
$$$
-- END 9.7/2

-- START 9.7/3
update setting set value='9.7/3' where name='_dbversion'
$$$
-- END 9.7/3

-- START 9.7/4
ALTER TABLE  `reportemaildelivery` ADD INDEX  `jobperson` (  `jobid` ,  `personid`, `sequence`)
$$$

update setting set value='9.7/4' where name='_dbversion'
$$$
-- END 9.7/4

-- START 9.7/5
update setting set value='9.7/5' where name='_dbversion'
$$$
-- END 9.7/5

-- START 10.0/1
update setting set value='10.0/1' where name='_dbversion'
$$$
ALTER TABLE `alert` ADD INDEX ( `date` )
$$$
-- END 10.0/1

-- START 10.0/2
update setting set value='10.0/2' where name='_dbversion'
$$$
ALTER TABLE `event` ADD INDEX ( `userid` )
$$$
-- END 10.0/2

-- START 10.0/3
update setting set value='10.0/3' where name='_dbversion'
$$$
CREATE TABLE IF NOT EXISTS `burst` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `contentid` bigint(20),
  `name` varchar(50) NOT NULL default '',
  `status` enum('new','mapped','sent') NOT NULL default 'new',
  `filename` varchar(255) NOT NULL default '',
  `bytes` bigint(20),
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
-- END 10.0/3

-- START 10.0/4
update setting set value='10.0/4' where name='_dbversion'
$$$
CREATE TABLE IF NOT EXISTS `burst_template` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `x` double(12,8),
  `y` double(12,8),
  `created` datetime,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
-- END 10.0/4


-- START 10.0/5
update setting set value='10.0/5' where name='_dbversion'
$$$
RENAME TABLE burst_template TO bursttemplate
$$$

ALTER TABLE burst
ADD COLUMN `bursttemplateid`    INT(11),
ADD COLUMN `uploaddatems`       BIGINT(20) NOT NULL,
ADD COLUMN `pagesskipstart`     INT(3)     NOT NULL DEFAULT 0,
ADD COLUMN `pagesskipend`       INT(3)     NOT NULL DEFAULT 0,
ADD COLUMN `pagesperreport`     INT(3)     NOT NULL DEFAULT 1,
ADD COLUMN `totalpagesfound`    INT(6),
ADD COLUMN `actualreportscount` INT(6)
$$$

drop INDEX `id` ON burst
$$$

drop INDEX `id` ON bursttemplate
$$$

-- END 10.0/5


-- START 10.0/6
update setting set value='10.0/6' where name='_dbversion'
$$$
alter table `burst` drop column `pagesskipstart`
$$$
alter table `burst` drop column `pagesskipend`
$$$
alter table `burst` drop column `pagesperreport`
$$$
alter table `bursttemplate` add column `pagesskipstart` int(3) default 0 after `y`
$$$
alter table `bursttemplate` add column `pagesskipend` int(3) default 0 after `pagesskipstart`
$$$
alter table `bursttemplate` add column `pagesperreport` int(3) default 1 after `pagesskipend`
$$$
-- END 10.0/6

-- START 10.1/1
ALTER TABLE `bursttemplate` CHANGE `created` `createdtimestampms` BIGINT NULL DEFAULT NULL
$$$

update setting set value='10.1/1' where name='_dbversion'
$$$
-- END 10.1/1

-- START 10.1/2

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

update setting set value='10.1/2' where name='_dbversion'
$$$
-- END 10.1/2

-- START 10.1/3

ALTER TABLE `burst`
  CHANGE `bytes` `size` BIGINT( 20 ) NOT NULL ,
  DROP `totalpagesfound`,
  DROP `actualreportscount`
$$$

update setting set value='10.1/3' where name='_dbversion'
$$$
-- END 10.1/3

-- START 10.1/4
update setting set value='10.1/4' where name='_dbversion'
$$$
-- END 10.1/4

-- START 10.1/5
update setting set value='10.1/5' where name='_dbversion'
$$$
-- END 10.1/5

-- START 10.1/6
update setting set value='10.1/6' where name='_dbversion'
$$$
-- END 10.1/6

-- START 10.1/7
CREATE TABLE `cmafeedcategory` (
  `feedcategoryid` int(11) NOT NULL,
  `cmacategoryid` int(11) NOT NULL,
  PRIMARY KEY (`feedcategoryid`,`cmacategoryid`),
  KEY `feedcategoryid` (`feedcategoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
update setting set value='10.1/7' where name='_dbversion'
$$$
-- END 10.1/7

-- START 10.1/8
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

update setting set value='10.1/8' where name='_dbversion'
$$$
-- END 10.1/8

-- START 10.1/9
ALTER TABLE `messageattachment`
	DROP `contentid`,
	DROP `filename`,
	DROP `size`
$$$

update setting set value='10.1/9' where name='_dbversion'
$$$
-- END 10.1/9

-- START 10.1/10
update setting set value='10.1/10' where name='_dbversion'
$$$
-- END 10.1/10

-- START 10.1/11
ALTER TABLE `burstattachment` ADD `secretfield` VARCHAR( 32 ) NOT NULL
$$$
update setting set value='10.1/11' where name='_dbversion'
$$$
-- END 10.1/11

-- START 10.1/12
ALTER TABLE `reportcontact` CHANGE `sequence` `sequence` SMALLINT NOT NULL
$$$
update setting set value='10.1/12' where name='_dbversion'
$$$
-- END 10.1/12

-- START 10.1/13
ALTER TABLE `reportemaildelivery` CHANGE `sequence` `sequence` SMALLINT NULL DEFAULT NULL
$$$
ALTER TABLE `reportemailtracking` CHANGE `sequence` `sequence` SMALLINT NOT NULL
$$$
update setting set value='10.1/13' where name='_dbversion'
$$$
-- END 10.1/13

-- START 10.1/14
update setting set value='10.1/14' where name='_dbversion'
$$$
-- END 10.1/14


-- START 10.2/1

CREATE TABLE `feedcategorytype` (
 `feedcategoryid` int(11) NOT NULL,
 `type` enum('rss','desktop','push') NOT NULL,
 PRIMARY KEY (`feedcategoryid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

update setting set value='10.2/1' where name='_dbversion'
$$$
-- END 10.2/1

-- START 10.2/2

ALTER TABLE `messagepart` CHANGE `type` `type` ENUM( 'A', 'T', 'V', 'I', 'MAL' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'A',
  ADD `messageattachmentid` INT NULL AFTER `imagecontentid`
$$$

update setting set value='10.2/2' where name='_dbversion'
$$$
-- END 10.2/2

-- START 10.2/3
ALTER TABLE `bursttemplate` ADD COLUMN `identifierTextPattern` VARCHAR(150);
$$$

update setting set value='10.2/3' where name='_dbversion'
$$$
-- END 10.2/3

-- START 10.2/4
ALTER TABLE `bursttemplate` CHANGE `identifierTextPattern` `identifiertextpattern` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL 
$$$

update setting set value='10.2/4' where name='_dbversion'
$$$
-- END 10.2/4


-- START 10.3/1

-- note this is a short lived table and will be replaced in near future after more infocenter schema is worked out
CREATE TABLE `ic_portalperson` (
 `portaluserid` int(11) NOT NULL,
 `personid` int(11) NOT NULL,
 PRIMARY KEY (`portaluserid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

update setting set value='10.3/1' where name='_dbversion'
$$$
-- END 10.3/1

-- START 10.3/2

-- deprecate fields that move to new useraccess table. no change to user imports they still use these deprecated fields
ALTER TABLE `user`
CHANGE `accessid` `accessid` INT( 11 ) NULL DEFAULT NULL COMMENT 'deprecated, see useraccess.accessid', 
CHANGE `personid` `personid` INT( 11 ) NULL DEFAULT NULL COMMENT 'deprecated, see useraccess.personid'
$$$

-- fix foobar accessid value
UPDATE `user` set accessid = null where accessid = 0
$$$

-- imports create with accessid=NULL and GUI to manage setting access
ALTER TABLE `guardiancategory` ADD `accessid` INT NULL
$$$
 
-- support for various types of access profile
ALTER TABLE `access` ADD `type` ENUM( 'cs', 'guardian', 'identity' ) NOT NULL DEFAULT 'cs'
$$$
 
-- create special access profile for user identities
insert into `access` (`name`, `description`, `type`) values ('User Identity', 'Links a user to their contact information', 'identity')
$$$
insert into `permission` (`accessid`, `name`, `value`) values (LAST_INSERT_ID(), 'fullaccess', '1')
$$$
 
-- user access to object type
CREATE TABLE `useraccess` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `accessid` int(11) NOT NULL,
  `type` enum('organization','section','person') NOT NULL DEFAULT 'person',
  `personid` int(11) DEFAULT NULL,
  `organizationid` int(11) DEFAULT NULL COMMENT 'future use',
  `sectionid` int(11) DEFAULT NULL COMMENT 'future use',
  `importid` int(11) DEFAULT NULL COMMENT 'future use',
  `importstatus` enum('none','checking','new') NOT NULL DEFAULT 'none' COMMENT 'future use',
  PRIMARY KEY (`id`),
  INDEX  `userid` (`userid` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
 
update setting set value='10.3/2' where name='_dbversion'
$$$
-- END 10.3/2

-- START 10.3/3

DROP TABLE `ic_portalperson`
$$$

update setting set value='10.3/3' where name='_dbversion'
$$$
-- END 10.3/3

-- START 10.3/4

ALTER  TABLE  `ttsvoice`  ADD  `name` VARCHAR( 50  )  NOT  NULL ,
  ADD  `enabled` TINYINT NOT  NULL DEFAULT  '0'
$$$

-- Loquendo voices enabled for old customers
UPDATE `ttsvoice` SET `name` = 'Susan', `enabled` = '0' 
  WHERE `language` = 'english' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Dave', `enabled` = '0' 
  WHERE `language` = 'english' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Esperanza', `enabled` = '0' 
  WHERE `language` = 'spanish' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Carlos', `enabled` = '0' 
  WHERE `language` = 'spanish' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Montserrat', `enabled` = '0' 
  WHERE `language` = 'catalan' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Jordi', `enabled` = '0' 
  WHERE `language` = 'catalan' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Lisheng', `enabled` = '0' 
  WHERE `language` = 'chinese' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Saskia', `enabled` = '0' 
  WHERE `language` = 'dutch' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Willem', `enabled` = '0' 
  WHERE `language` = 'dutch' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Milla', `enabled` = '0' 
  WHERE `language` = 'finnish' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Florence', `enabled` = '0' 
  WHERE `language` = 'french' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Bernard', `enabled` = '0' 
  WHERE `language` = 'french' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Katrin', `enabled` = '0' 
  WHERE `language` = 'german' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Stefan', `enabled` = '0' 
  WHERE `language` = 'german' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Afroditi', `enabled` = '0' 
  WHERE `language` = 'greek' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Paola', `enabled` = '0' 
  WHERE `language` = 'italian' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Matteo', `enabled` = '0' 
  WHERE `language` = 'italian' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Zosia', `enabled` = '0' 
  WHERE `language` = 'polish' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Krzysztof', `enabled` = '0' 
  WHERE `language` = 'polish' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Amalia', `enabled` = '0' 
  WHERE `language` = 'portuguese' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Eusebio', `enabled` = '0' 
  WHERE `language` = 'portuguese' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Olga', `enabled` = '0' 
  WHERE `language` = 'russian' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Annika', `enabled` = '0' 
  WHERE `language` = 'swedish' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Sven', `enabled` = '0' 
  WHERE `language` = 'swedish' and `gender` = 'male'
$$$

-- NeoSpeech voices enabled for new customers

INSERT INTO `ttsvoice` (`language`, `languagecode`, `gender`, `name`, `enabled`) VALUES 
  ('english', 'en', 'female', 'Julie', 1),
  ('english', 'en', 'male', 'James', 1),
  ('turkish', 'tr', 'male', 'Hasari', 1)
$$$


update setting set value='10.3/4' where name='_dbversion'
$$$
-- END 10.3/4


-- START 10.3/5

-- new customer is neospeech
insert into setting (name, value) values ('_defaultttsprovider', 'neospeech')
$$$

-- TODO this voice is temporary until we get the list of language-gender-name for neospeech
update ttsvoice set enabled = '1' where name = 'Hasari'
$$$

-- TODO set enabled loquendo voices once we know which neospeech languages we have
update ttsvoice set enabled = '1' where name = 'Sven'
$$$

update setting set value='10.3/5' where name='_dbversion'
$$$
-- END 10.3/5


ALTER TABLE `ttsvoice` ADD `provider` ENUM( 'loquendo', 'neospeech' ) NOT NULL DEFAULT 'loquendo'
$$$

update ttsvoice set provider = 'neospeech' where name in ('James','Julie','Hasari')
$$$

update setting set value='10.3/6' where name='_dbversion'
$$$
-- END 10.3/6
