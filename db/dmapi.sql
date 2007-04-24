

CREATE TABLE `jobtaskactive` (
  `id` varchar(50) collate utf8_bin NOT NULL,
  `jobtaskid` bigint(20) NOT NULL,
  `jobworkitemid` bigint(20) NOT NULL,
  `personid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `customerid` int(11) NOT NULL,
  `assignmentgroup` tinyint(4) NOT NULL,
  `status` enum('new','calling','done') collate utf8_bin NOT NULL,
  `type` enum('phone','email','print') collate utf8_bin NOT NULL,
  `subtype` varchar(20) collate utf8_bin NOT NULL default 'notification',
  `issurvey` tinyint(4) NOT NULL default '0',
  `tasktime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `phone` varchar(20) collate utf8_bin default NULL,
  `numattempts` tinyint(4) NOT NULL,
  `starttime` bigint(20) NOT NULL default '0',
  `duration` float NOT NULL default '0',
  `callprogress` enum('C','A','M','N','B','X','F') collate utf8_bin default NULL,
  `renderedmessage` text character set utf8 NOT NULL,
  `resultdata` text character set utf8,
  PRIMARY KEY  (`id`),
  KEY `status` (`status`,`tasktime`,`assignmentgroup`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


CREATE TABLE IF NOT EXISTS  `sessiondata` (
  `id` char(255) NOT NULL,
  `customerid` int(11) NOT NULL,
  `lastused` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `lastused` (`lastused`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- tweak specialtask

ALTER TABLE `jobtaskactive` DROP `subtype` ;

CREATE TABLE `tasksyncdata` (
`name` VARCHAR( 50 ) NOT NULL ,
`value` VARCHAR( 50 ) NOT NULL ,
PRIMARY KEY ( `name` )
) ENGINE = MYISAM ;

-- tweak tasksyncdata

ALTER TABLE tasksyncdata ENGINE = InnoDB;

-- changing jobtaskactive to innodb for transactions
ALTER TABLE `jobtaskactive`  ENGINE = innodb
