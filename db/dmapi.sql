
--
-- Table structure for table `jobtaskactive`
--

CREATE TABLE `jobtaskactive` (
  `id` bigint NOT NULL,
  `customerid` int(11) NOT NULL,
  `shardid` tinyint(4) NOT NULL,
  `tasktime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `renderedmessage` text character set utf8 NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `status` (`tasktime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `jobtaskcomplete`
--

CREATE TABLE `jobtaskcomplete` (
  `id` bigint NOT NULL,
  `customerid` int(11) NOT NULL,
  `shardid` tinyint(4) NOT NULL,
  `starttime` bigint(20) NOT NULL default '0',
  `duration` float default NULL,
  `result` enum('A','M','N','B','X','F','sent','unsent','printed','notprinted') collate utf8_bin NOT NULL,
  `resultdata` text character set utf8 NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;



-- NOTE if CommSuite we must create sessiondata from authserver schema

CREATE TABLE IF NOT EXISTS  `sessiondata` (
  `id` char(255) NOT NULL,
  `customerid` int(11) NOT NULL,
  `lastused` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `lastused` (`lastused`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE `tasksyncdata` (
`name` VARCHAR( 50 ) NOT NULL ,
`value` VARCHAR( 50 ) NOT NULL ,
PRIMARY KEY ( `name` )
) ENGINE = InnoDB ;




CREATE TABLE specialtaskactive (
  id varchar(50) collate utf8_bin NOT NULL,
  customerid int(11) NOT NULL,
  specialtaskid int(11) NOT NULL,
  shardid tinyint(4) NOT NULL,
  `type` varchar(50) collate utf8_bin NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
