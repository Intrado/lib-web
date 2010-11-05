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
  `data` mediumtext NOT NULL,
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

INSERT INTO systemmessages (message, icon, modifydate)
VALUES (
'<div style="color:#3e693f;font-size: 20px;font-weight: bold;">New Spring 2010 Release!</div>
  <ul>
  <li>See what is new in the Spring 2010 Release: <a href="help/html/New_in_Spring_2010_Release.pdf" target="new"><img src="img/icons/page_white_acrobat.gif" /> New in Spring 2010</a> 
  <li>Be sure to check out the overview of features: <a href="javascript: popup(''help/flash/overview_7-5.html'',650,480);"><img src="img/icons/control_play_blue.gif" /> Play Overview</a>
  </ul>', 'largeicons/news.jpg', NOW()
)
$$$

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
