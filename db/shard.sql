-- --------------------------------------------------------

CREATE TABLE qjob (
  customerid int(11) NOT NULL,
  id int(11) NOT NULL,
  userid int(11) NOT NULL default '0',
  scheduleid int(11) default NULL,
  jobtypeid int(11) default '0',
  listid int(11) NOT NULL default '0',
  phonemessageid int(11) default NULL,
  emailmessageid int(11) default NULL,
  printmessageid int(11) default NULL,
  questionnaireid int(11) default NULL,
  `timezone` varchar(50) NOT NULL,
  startdate date NOT NULL default '0000-00-00',
  enddate date NOT NULL default '0000-00-00',
  starttime time NOT NULL default '00:00:00',
  endtime time NOT NULL default '00:00:00',
  `status` enum('new','processing','active','complete','cancelled','cancelling','repeating') NOT NULL default 'new',
  `attempts` tinyint(4) NOT NULL default '0',
  `sequence` tinyint(4) NOT NULL default '0',
  `thesql` text,
  PRIMARY KEY  (customerid,id),
  KEY `status` (`status`,id),
  KEY startdate (startdate),
  KEY enddate (enddate),
  KEY starttime (starttime),
  KEY endtime (endtime),
  KEY startdate_2 (startdate,enddate,starttime,endtime,id),
  KEY scheduleid (scheduleid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

CREATE TABLE `qjobsetting` (
  `customerid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`customerid`,`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `qjobtask` (
  `customerid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  `type` enum('phone','email','print','sms') NOT NULL,
  `personid` int(11) NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `status` enum('active','pending','assigned','progress','waiting','throttled') NOT NULL,
  `attempts` tinyint(4) NOT NULL default '0',
  `renderedmessage` text,
  `lastresult` enum('A','M','N','B','X','F','sent','unsent','printed','notprinted') default NULL,
  `lastresultdata` text,
  `lastduration` float default NULL,
  `lastattempttime` bigint(20) default NULL,
  `nextattempttime` bigint(20) default NULL,
  `phone` varchar(20) default NULL,
  PRIMARY KEY  (`customerid`,`jobid`,`type`,`personid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE qschedule (
  `customerid` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `dow` varchar(20) NOT NULL default '',
  `time` time NOT NULL default '00:00:00',
  nextrun datetime default NULL,
  PRIMARY KEY  (customerid,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

CREATE TABLE `importqueue` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL,
  `localimportid` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `import` (`customerid`,`localimportid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

