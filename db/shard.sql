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
  `status` enum('new','processing','procactive','active','cancelling','repeating') NOT NULL default 'new',
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
PRIMARY KEY (`customerid~,`jobid`,`personid`)
) ENGINE = innodb;

