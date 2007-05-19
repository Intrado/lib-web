-- --------------------------------------------------------

--
-- Table structure for table `job`
--
-- KEEP SCHEMA FOR JOB TABLE IDENTICAL TO THE CUSTOMER DB
-- EXCEPT you need to add the customerid field and include it onto the primary key!!!
-- AND you need to add the timezone field

CREATE TABLE job (
  id int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL,
  `timezone` varchar(50) NOT NULL,
  userid int(11) NOT NULL default '0',
  scheduleid int(11) default NULL,
  jobtypeid int(11) default '0',
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  listid int(11) NOT NULL default '0',
  phonemessageid int(11) default NULL,
  emailmessageid int(11) default NULL,
  printmessageid int(11) default NULL,
  questionnaireid int(11) default NULL,
  `type` set('phone','email','print','survey') NOT NULL default 'phone',
  createdate datetime NOT NULL default '0000-00-00 00:00:00',
  startdate date NOT NULL default '0000-00-00',
  enddate date NOT NULL default '2006-07-04',
  starttime time NOT NULL default '00:00:00',
  endtime time NOT NULL default '00:00:00',
  finishdate datetime default NULL,
  `status` enum('new','processing','active','complete','cancelled','cancelling','repeating') NOT NULL default 'new',
  deleted tinyint(4) NOT NULL default '0',
  ranautoreport tinyint(4) NOT NULL default '0',
  priorityadjust int(11) NOT NULL default '0',
  cancelleduserid int(11) default NULL,
  `thesql` text,
  PRIMARY KEY  (id,customerid),
  KEY `status` (`status`,id),
  KEY startdate (startdate),
  KEY enddate (enddate),
  KEY starttime (starttime),
  KEY endtime (endtime),
  KEY startdate_2 (startdate,enddate,starttime,endtime,id),
  KEY scheduleid (scheduleid),
  KEY ranautoreport (ranautoreport,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `jobsetting`
--

CREATE TABLE `jobsetting` (
  `jobid` bigint(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `jobtask`
--

CREATE TABLE `jobtask` (
  `jobid` bigint(20) NOT NULL,
  `type` enum('phone','email','print','sms') NOT NULL,
  `personid` int(11) NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `status` enum('active','pending','assigned','progress','waiting','thottled') NOT NULL,
  `attempts` tinyint(4) NOT NULL default '0',
  `renderedmessage` text,
  `lastresult` enum('A','M','N','B','X','F','sent','unsent','printed','notprinted') default NULL,
  `lastresultdata` text,
  `lastduration` float default NULL,
  `lastattempttime` bigint(20) default NULL,
  `nextattempttime` bigint(20) default NULL,
  `phone` varchar(20) default NULL,
  PRIMARY KEY  (`jobid`,`type`,`personid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

CREATE TABLE `importqueue` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL,
  `localimportid` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `import` (`customerid`,`localimportid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


