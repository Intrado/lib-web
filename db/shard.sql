-- --------------------------------------------------------

--
-- Table structure for table `job`
--

CREATE TABLE `job` (
  `id` bigint(20) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL,
  `localjobid` int(11) NOT NULL,
  `hasphone` tinyint(4) NOT NULL default '0',
  `hasemail` tinyint(4) NOT NULL default '0',
  `hasprint` tinyint(4) NOT NULL default '0',
  `hasquestionnaire` tinyint(4) NOT NULL default '0',
  `status` enum('new','processing','active','cancelling','repeating') NOT NULL default 'new',
  `timezone` varchar(50) NOT NULL,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `starttime` time NOT NULL,
  `endtime` time NOT NULL,
  `thesql` text,
  `priorityadjust` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

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


