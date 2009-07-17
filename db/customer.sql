--
-- Database: some customer `c_0`
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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` bigint(20) NOT NULL auto_increment,
  `contenttype` varchar(255) NOT NULL default '',
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uploadkey` (`uploadkey`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `importjob`
--

CREATE TABLE `importjob` (
  `id` int(11) NOT NULL auto_increment,
  `importid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
  `questionnaireid` int(11) default NULL,
  `type` set('phone','email','print','survey') NOT NULL default 'phone',
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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `jobsetting`
--

CREATE TABLE `jobsetting` (
  `jobid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`jobid`,`name`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `jobstats`
--

CREATE TABLE `jobstats` (
  `jobid` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY  (`jobid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `jobtype`
--

CREATE TABLE `jobtype` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `priority` int(11) NOT NULL default '10000',
  `systempriority` tinyint(4) NOT NULL default '3',
  `timeslices` smallint(6) NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `customerid` (`priority`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
  `data` text NOT NULL,
  `lastused` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`type`,`deleted`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
  `smsenabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`),
  KEY `dedupe` (`phone`,`sequence`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `reportcontact`
--

CREATE TABLE `reportcontact` (
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
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
  `addressee` varchar(50) default NULL,
  `addr1` varchar(50) default NULL,
  `addr2` varchar(50) default NULL,
  `city` varchar(50) default NULL,
  `state` char(2) default NULL,
  `zip` varchar(10) default NULL,
  PRIMARY KEY  (`jobid`,`type`,`personid`,`sequence`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `reportperson`
--

CREATE TABLE `reportperson` (
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  `userid` int(11) NOT NULL,
  `messageid` int(11) default NULL,
  `status` enum('new','queued','assigned','fail','success','duplicate','blocked','nocontacts') NOT NULL,
  `numcontacts` tinyint(4) NOT NULL,
  `numduperemoved` tinyint(4) NOT NULL,
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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `smsjob`
--

CREATE TABLE `smsjob` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `listid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL,
  `txt` varchar(160) NOT NULL,
  `sendoptout` tinyint(4) NOT NULL,
  `sentdate` datetime NOT NULL,
  `status` enum('new','queued','sent','error') NOT NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `status` (`status`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `smsmsg`
--

CREATE TABLE `smsmsg` (
  `id` int(11) NOT NULL auto_increment,
  `smsjobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `phone` varchar(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `systemstats`
--

CREATE TABLE `systemstats` (
  `jobid` int(11) NOT NULL,
  `date` date NOT NULL,
  `hour` int(11) NOT NULL,
  `answered` int(11) NOT NULL,
  `machine` int(11) NOT NULL,
  `busy` int(11) NOT NULL,
  `noanswer` int(11) NOT NULL,
  `failed` int(11) NOT NULL,
  `disconnect` int(11) NOT NULL,
  PRIMARY KEY  (`jobid`,`date`,`hour`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `ttsvoice`
--

CREATE TABLE `ttsvoice` (
  `id` int(11) NOT NULL auto_increment,
  `language` varchar(20) NOT NULL default '',
  `gender` enum('male','female') NOT NULL default 'male',
  PRIMARY KEY  (`id`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `userjobtypes`
--

CREATE TABLE `userjobtypes` (
  `userid` int(11) NOT NULL default '0',
  `jobtypeid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`jobtypeid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- --------------------------------------------------------

--
-- Table structure for table `userrule`
--

CREATE TABLE `userrule` (
  `userid` int(11) NOT NULL default '0',
  `ruleid` int(11) NOT NULL default '0'
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

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
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$


-- ASP_RELEASE_2007_08_10

alter table reportperson add iscontacted tinyint(4) not null default 0 after status
$$$

update reportperson set iscontacted=1 where status='success'
$$$

-- Parent Portal

CREATE TABLE `portalperson` (
  `portaluserid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  PRIMARY KEY  (`portaluserid`,`personid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

CREATE TABLE `portalpersontoken` (
  `token` varchar(255) NOT NULL,
  `expirationdate` datetime NOT NULL,
  `personid` int(11) NOT NULL,
  `creationuserid` int(11) NOT NULL,
  PRIMARY KEY  (`token`),
  UNIQUE KEY `personid` (`personid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `jobtype`
ADD `infoforparents` VARCHAR( 255 ) NOT NULL DEFAULT ''  AFTER `timeslices` ,
ADD `issurvey` TINYINT NOT NULL DEFAULT '0' AFTER `infoforparents`
$$$

CREATE TABLE `jobtypepref` (
`jobtypeid` INT NOT NULL ,
`type` ENUM( 'phone', 'email', 'print', 'sms' ) NOT NULL ,
`sequence` TINYINT NOT NULL ,
`enabled` TINYINT NOT NULL DEFAULT '0',
PRIMARY KEY ( `jobtypeid` , `type` , `sequence` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

CREATE TABLE `contactpref` (
`personid` INT NOT NULL,
`jobtypeid` INT NOT NULL,
`type` ENUM( 'phone', 'email', 'print', 'sms' ) NOT NULL ,
`sequence` TINYINT NOT NULL,
`enabled` TINYINT NOT NULL DEFAULT '0',
PRIMARY KEY ( `personid` , `jobtypeid` , `type` , `sequence` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `reportcontact` CHANGE `result` `result` enum('C','A','M','N','B','X','F','sent','unsent','printed','notprinted','notattempted','duplicate','blocked','declined') NOT NULL default 'notattempted'
$$$

ALTER TABLE `reportperson` CHANGE `status` `status` enum('new','queued','assigned','fail','success','duplicate','blocked','nocontacts','declined') NOT NULL
$$$

ALTER TABLE `reportperson`
ADD `numdeclined` tinyint(4) NOT NULL default '0' AFTER `numduperemoved`
$$$


-- alter sms

ALTER TABLE `job`
ADD   `smsmessageid` int(11) default NULL AFTER `printmessageid`
$$$

ALTER TABLE `job`
CHANGE `type` `type` set('phone','email','print','sms','survey') NOT NULL default 'phone'
$$$

ALTER TABLE `joblanguage`
CHANGE `type` `type` enum('phone','email','print','sms') NOT NULL default 'phone'
$$$

ALTER TABLE `message`
CHANGE `type` `type` enum('phone','email','print','sms') NOT NULL default 'phone'
$$$

ALTER TABLE `reportcontact`
CHANGE `type` `type` enum('phone','email','print','sms') NOT NULL
$$$

ALTER TABLE `reportperson`
CHANGE `type` `type` enum('phone','email','print','sms') NOT NULL
$$$

ALTER TABLE `messagepart` ADD `maxlen` SMALLINT NULL
$$$

CREATE TABLE `sms` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `sms` varchar(20) NOT NULL default '',
  `sequence` tinyint(4) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`),
  KEY `dedupe` (`sms`,`sequence`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `reportcontact`
ADD `sms` varchar(20) default NULL AFTER `email`
$$$


-- import enhancements

ALTER TABLE `importfield` add `action` ENUM( 'copy', 'staticvalue', 'number', 'currency', 'date', 'lookup' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'copy' after mapto
$$$

ALTER TABLE `importfield` add `val` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
$$$

ALTER TABLE `importfield` CHANGE `mapfrom` `mapfrom` TINYINT( 4 ) NULL
$$$

-- more changes

ALTER TABLE phone DROP smsenabled
$$$

ALTER TABLE jobtype DROP `priority`
$$$

ALTER TABLE `portalpersontoken` CHANGE `expirationdate` `expirationdate` DATE NOT NULL
$$$

-- add curdate and skipheaders to imports

ALTER TABLE `import` ADD `skipheaderlines` TINYINT NOT NULL DEFAULT '0' AFTER `datamodifiedtime`
$$$

ALTER TABLE `importfield` CHANGE `action` `action` ENUM( 'copy', 'staticvalue', 'number', 'currency', 'date', 'lookup', 'curdate' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'copy'
$$$

-- timeslices moved to system setting
ALTER TABLE jobtype DROP `timeslices`
$$$

-- TODO remove the first alter when we added 'declined' we would not want to alter a huge table twice!
ALTER TABLE `reportcontact` CHANGE `result` `result` enum('C','A','M','N','B','X','F','sent','unsent','printed','notprinted','notattempted','duplicate','blocked') NOT NULL default 'notattempted'
$$$

DROP TABLE smsjob
$$$

DROP TABLE smsmsg
$$$

ALTER TABLE `systemstats` ADD `attempt` TINYINT NOT NULL DEFAULT '0' AFTER `jobid`
$$$

ALTER TABLE `systemstats` DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `jobid` , `attempt` , `date` , `hour` )
$$$

-- New table for destination labels
CREATE TABLE `destlabel` (
`type` ENUM( 'phone', 'email', 'sms' ) NOT NULL ,
`sequence` TINYINT NOT NULL ,
`label` VARCHAR( 20 ) NOT NULL ,
PRIMARY KEY ( `type` , `sequence` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `jobtype` CHANGE `infoforparents` `info` VARCHAR( 255 ) NOT NULL
$$$

ALTER TABLE `portalpersontoken` DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `token` , `personid` )
$$$

-- Dec 10

ALTER TABLE `portalpersontoken` DROP INDEX `personid`
$$$

ALTER TABLE `portalpersontoken` DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `personid` )
$$$

-- Dec 13

ALTER TABLE `portalperson` ADD `notifyemail` VARCHAR( 100 ) NULL
$$$

-- email attachments

CREATE TABLE `messageattachment` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`messageid` INT NOT NULL ,
`contentid` BIGINT NOT NULL ,
`filename` VARCHAR( 255 ) NOT NULL ,
`size` INT NOT NULL ,
`deleted` TINYINT NOT NULL DEFAULT '0',
INDEX ( `messageid` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

-- After ASP_5-2 april 3

CREATE TABLE `custdm` (
  `dmid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `enablestate` enum('new','active','disabled') NOT NULL,
  PRIMARY KEY  (`dmid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$


CREATE TABLE `dmroute` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `match` varchar(20) NOT NULL,
  `strip` int(11) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `suffix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dmid` (`dmid`,`match`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `destlabel` ADD `notes` TEXT NULL
$$$
ALTER TABLE `import` ADD `notes` TEXT NULL AFTER `description`
$$$
ALTER TABLE `destlabel` CHANGE `notes` `notes` VARCHAR( 255 ) NULL
$$$
ALTER TABLE `custdm` ADD `routechange` INT NULL
$$$
ALTER TABLE `import` ADD `alertoptions` TEXT NULL
$$$


CREATE TABLE `dmcalleridroute` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `callerid` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dmid` (`dmid`,`callerid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$


ALTER TABLE `custdm` ADD `telco_type` ENUM( 'Test', 'Asterisk', 'Jtapi' ) NOT NULL DEFAULT 'Test' AFTER `enablestate`
$$$

ALTER TABLE `custdm` CHANGE `routechange` `routechange` TINYINT( 4 )
$$$

ALTER TABLE `reportcontact` ADD `voicereplyid` INT(11) NULL ,
ADD `response` TINYINT(4) NULL
$$$

ALTER TABLE `user` CHANGE `password` `password` VARCHAR( 50 ) NOT NULL ,
CHANGE `pincode` `pincode` VARCHAR( 50 ) NOT NULL
$$$

-- After 6.0.1

ALTER TABLE `user` ADD `aremail` TEXT NOT NULL AFTER `email`
$$$
UPDATE `user` set `aremail` = `email`
$$$
UPDATE `user` set `email` = ''
$$$
ALTER TABLE `user` CHANGE `email` `email` VARCHAR( 255 ) NOT NULL
$$$

ALTER TABLE `import` ADD `datatype` ENUM( 'person', 'user', 'association' ) NOT NULL DEFAULT 'person' AFTER `type`,
CHANGE `updatemethod` `updatemethod` ENUM( 'updateonly', 'update', 'full', 'createonly' ) NOT NULL DEFAULT 'full'
$$$

ALTER TABLE `user` ADD `staffpkey` VARCHAR( 255 ) NULL,
ADD `importid` TINYINT NULL ,
ADD `lastimport` DATETIME NULL
$$$

CREATE TABLE `personassociation` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`personid` INT NOT NULL ,
`c01` VARCHAR( 255 ) NOT NULL ,
`c02` VARCHAR( 255 ) NOT NULL ,
`c03` VARCHAR( 255 ) NOT NULL ,
`c04` VARCHAR( 255 ) NOT NULL ,
`c05` VARCHAR( 255 ) NOT NULL ,
`c06` VARCHAR( 255 ) NOT NULL ,
`c07` VARCHAR( 255 ) NOT NULL ,
`c08` VARCHAR( 255 ) NOT NULL ,
`c09` VARCHAR( 255 ) NOT NULL ,
`c10` VARCHAR( 255 ) NOT NULL
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

CREATE TABLE `groupdata` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`personid` INT NOT NULL ,
`fieldnum` TINYINT NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL,
KEY `personfield`  (`personid`,`fieldnum`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

CREATE TABLE `reportgroupdata` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`jobid` INT NOT NULL,
`personid` INT NOT NULL ,
`fieldnum` TINYINT NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL,
KEY `jobpersonfield`  (`jobid`,`personid`,`fieldnum`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `import` CHANGE `datatype` `datatype` ENUM( 'person', 'user', 'enrollment' ) NOT NULL DEFAULT 'person'
$$$

drop table `personassociation`
$$$

CREATE TABLE `enrollment` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`personid` INT NOT NULL ,
`c01` VARCHAR( 255 ) NOT NULL ,
`c02` VARCHAR( 255 ) NOT NULL ,
`c03` VARCHAR( 255 ) NOT NULL ,
`c04` VARCHAR( 255 ) NOT NULL ,
`c05` VARCHAR( 255 ) NOT NULL ,
`c06` VARCHAR( 255 ) NOT NULL ,
`c07` VARCHAR( 255 ) NOT NULL ,
`c08` VARCHAR( 255 ) NOT NULL ,
`c09` VARCHAR( 255 ) NOT NULL ,
`c10` VARCHAR( 255 ) NOT NULL
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `enrollment` ADD INDEX `staffid` ( `c01` )
$$$
ALTER TABLE `userrule` ADD INDEX ( `userid` )
$$$
ALTER TABLE `user` ADD INDEX ( `staffpkey` )
$$$
ALTER TABLE `enrollment` ADD INDEX ( `personid` )
$$$

TRUNCATE `groupdata`
$$$

ALTER TABLE `groupdata` ADD `importid` TINYINT NOT NULL
$$$

ALTER TABLE `groupdata` ADD INDEX ( `importid` )
$$$

ALTER TABLE `groupdata` CHANGE `id` `id` BIGINT( 11 ) NOT NULL AUTO_INCREMENT
$$$

ALTER TABLE `reportgroupdata` CHANGE `id` `id` BIGINT( 11 ) NOT NULL AUTO_INCREMENT
$$$

-- ASP 6.1
-- Start here for release 6.2

create table if not exists customercallstats (
  jobid int(11) NOT NULL,
  userid int(11) NOT NULL,
  finishdate datetime default NULL,
  attempted int(11),
  primary key (jobid)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

CREATE TABLE `dmschedule` (
`id` INT NOT NULL auto_increment ,
`dmid` INT NOT NULL ,
`daysofweek` VARCHAR( 20 ) NOT NULL ,
`starttime` TIME NOT NULL ,
`endtime` TIME NOT NULL ,
`resourcepercentage` float NOT NULL DEFAULT '1',
PRIMARY KEY ( `id` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `groupdata` CHANGE `importid` `importid` INT NOT NULL
$$$

CREATE TABLE IF NOT EXISTS `importlogentry` (
  `id` bigint(20) NOT NULL auto_increment,
  `importid` int(11) NOT NULL,
  `severity` enum('info','error','warn') NOT NULL,
  `txt` varchar(255) NOT NULL,
  `linenum` int(11) NULL,
  PRIMARY KEY  (`id`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `reportcontact` ADD `dispatchtype` ENUM( 'customer', 'system' ) NOT NULL DEFAULT 'system'
$$$

CREATE TABLE `joblist` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`jobid` INT NOT NULL ,
`listid` INT NOT NULL ,
`thesql` TEXT,
KEY `jobid` (`jobid`,`listid`)
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `rule` CHANGE `op` `op` ENUM( 'eq', 'ne', 'sw', 'ew', 'cn', 'in', 'reldate', 'date_range',
	'num_eq', 'num_ne', 'num_gt', 'num_ge', 'num_lt', 'num_le', 'num_range', 'date_offset' ) NOT NULL DEFAULT 'eq'
$$$

ALTER TABLE `portalperson` ADD `notifysms` VARCHAR( 20 ) NULL
$$$

ALTER TABLE `user` CHANGE `importid` `importid` INT( 11 ) NULL DEFAULT NULL
$$$

CREATE TABLE `personsetting` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`personid` INT NOT NULL ,
`name` VARCHAR( 50 ) NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL ,
INDEX ( `personid` , `name` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `joblanguage` ADD `translationeditlock` tinyint(4) NOT NULL default 0
$$$

ALTER TABLE `rule` CHANGE `op` `op` ENUM( 'eq', 'ne', 'sw', 'ew', 'cn', 'in', 'reldate', 'date_range',
	'num_eq', 'num_ne', 'num_gt', 'num_ge', 'num_lt', 'num_le', 'num_range', 'date_offset', 'reldate_range' ) NOT NULL DEFAULT 'eq'
$$$

-- Post 6.2 below here.

-- Add aditional import field types
ALTER TABLE `importfield` CHANGE `action` `action` ENUM( 'copy', 'staticvalue', 'number', 'currency', 'date', 'lookup', 'curdate', 
	'numeric', 'currencyleadingzero' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'copy'
$$$


ALTER TABLE `custdm` ADD `poststatus` TEXT NOT NULL default ''
$$$


CREATE TABLE `subscriber` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`username` VARCHAR( 255 ) NOT NULL ,
`password` VARCHAR( 50 ) NOT NULL ,
`personid` INT NULL ,
`lastlogin` DATETIME NULL ,
`enabled` TINYINT NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin
$$$

ALTER TABLE `persondatavalues` ADD `editlock` TINYINT NOT NULL DEFAULT '0'
$$$

ALTER TABLE `subscriber` ADD `preferences` TEXT NOT NULL DEFAULT ''
$$$

CREATE TABLE `subscriberpending` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`subscriberid` INT NOT NULL ,
`type` ENUM( 'phone', 'email', 'sms' ) NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL ,
`token` VARCHAR( 255 ) NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin
$$$

CREATE TABLE IF NOT EXISTS `prompt` (
  `id` int(11) NOT NULL auto_increment,
  `type` enum('intro','emergencyintro','langmenu') NOT NULL,
  `messageid` int(11) NOT NULL,
  `dtmf` tinyint(4) default NULL,
  `language` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `subscriber` ADD UNIQUE `username` ( `username` ) 
$$$

ALTER TABLE `subscriber` ADD `lastreminder` DATETIME NULL DEFAULT NULL AFTER `lastlogin`
$$$

-- missing indexes

ALTER TABLE `permission` ADD INDEX ( `accessid` )
$$$

ALTER TABLE `surveyquestionnaire` ADD INDEX ( `userid` ) 
$$$

ALTER TABLE `job` ADD INDEX `useraccess` ( `userid` , `status` , `deleted` ) 
$$$

ALTER TABLE `systemstats` ADD INDEX `graphs` ( `date` , `attempt` ) 
$$$

ALTER TABLE `person` DROP INDEX `pkeysortb` 
$$$

ALTER TABLE `person` DROP INDEX `pkeysort` ,
ADD INDEX `pkeysort` ( `pkey` , `type` , `deleted` ) 
$$$

ALTER TABLE `blockednumber` ADD INDEX ( `userid` ) 
$$$

ALTER TABLE `person` DROP INDEX `namesort` 
$$$

ALTER TABLE `person` DROP INDEX `getbykey` 
$$$

ALTER TABLE `person` DROP INDEX `general` 
$$$

ALTER TABLE `person` ADD INDEX ( `f01` ) 
$$$

ALTER TABLE `person` ADD INDEX ( `f02` ) 
$$$

ALTER TABLE `listentry` ADD INDEX `listrule` ( `listid` , `type` , `personid` )
$$$

ALTER TABLE `setting` DROP INDEX `lookup` , ADD UNIQUE `name` ( `name` ) 
$$$

ALTER TABLE `job` ADD `modifydate` datetime AFTER `createdate`
$$$

ALTER TABLE `job` ADD INDEX ( `modifydate` )
$$$

ALTER TABLE `message` ADD `modifydate` datetime AFTER `data`
$$$

ALTER TABLE `message` ADD INDEX ( `modifydate` )
$$$

ALTER TABLE `list` ADD `modifydate` datetime AFTER `description`
$$$

ALTER TABLE `list` ADD INDEX ( `modifydate` )
$$$

ALTER TABLE `reportsubscription` ADD `modifydate` datetime AFTER `time`
$$$

ALTER TABLE `reportsubscription` ADD INDEX ( `modifydate` )
$$$

CREATE TABLE IF NOT EXISTS `systemmessages` (
  `id` int(11) NOT NULL auto_increment,
  `message` VARCHAR( 1000 ) NOT NULL,
  `icon` VARCHAR( 50 ),
  `modifydate` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  INDEX (`modifydate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `reportcontact` CHANGE `resultdata` `resultdata` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL 
$$$