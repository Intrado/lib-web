
CREATE TABLE `jobstats` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `jobid` int(11) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` double NOT NULL default '0',
  `format` enum('int','percent','float') NOT NULL default 'int',
  `sequence` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `byname` (`jobid`,`name`(20)),
  KEY `bysequence` (`jobid`,`sequence`)
) TYPE=MyISAM;


CREATE TABLE `monitor` (
  `id` int(11) NOT NULL auto_increment,
  `parentid` int(11) default NULL,
  `customerid` int(11) NOT NULL default '0',
  `type` varchar(20) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `options` text NOT NULL,
  `lastcheckin` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `general` (`type`,`name`,`lastcheckin`)
) TYPE=MyISAM;





-- dmstatus

CREATE TABLE `dmstatus` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`passcode` VARCHAR( 50 ) NOT NULL ,
`name` VARCHAR( 255 ) NOT NULL ,
`ip` VARCHAR( 15 ) NOT NULL ,
`lastseen` DATETIME NOT NULL
) ENGINE = MYISAM ;


CREATE TABLE `dmresourcestatus` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`dmstatusid` INT NOT NULL ,
`state` VARCHAR( 50 ) NOT NULL ,
`status` VARCHAR( 50 ) NOT NULL ,
`lastseen` DATETIME NOT NULL ,
`resourceid` INT NOT NULL
) ENGINE = MYISAM ;


CREATE TABLE `dmauth` (
`passcode` VARCHAR( 50 ) NOT NULL ,
`customerid` INT NOT NULL ,
`type` ENUM( 'disabled', 'nonemergency', 'all' ) NOT NULL ,
PRIMARY KEY ( `passcode` )
) ENGINE = MYISAM ;