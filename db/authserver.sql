
CREATE TABLE `aspadminuser` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(20) collate utf8_bin NOT NULL,
  `password` varchar(255) character set utf8 NOT NULL,
  `firstname` varchar(50) character set utf8 NOT NULL,
  `lastname` varchar(50) character set utf8 NOT NULL,
  `email` varchar(100) character set utf8 NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `login` (`login`,`password`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE `customer` (
  `id` int(11) NOT NULL auto_increment,
  `shardid` tinyint(4) NOT NULL,
  `urlcomponent` varchar(255) NOT NULL default '',
  `inboundnumber` varchar(20) NOT NULL default '',
  `dbusername` varchar(50) NOT NULL default '',
  `dbpassword` varchar(50) NOT NULL default '',
  `logintoken` varchar(255) NOT NULL default '',
  `logintokenexpiretime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `enabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `urlcomponent` (`urlcomponent`),
  KEY `inboundnumber` (`inboundnumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS  `sessiondata` (
  `id` char(255) NOT NULL,
  `customerid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `lastused` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `lastused` (`lastused`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `shard` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `name` VARCHAR(255) NOT NULL default '',
  `description` VARCHAR(255) NOT NULL default '',
  `dbhost` VARCHAR( 255 ) NOT NULL default '',
  `dbusername` VARCHAR( 50 ) NOT NULL default '',
  `dbpassword` VARCHAR( 50 ) NOT NULL default ''
) ENGINE = InnoDB DEFAULT CHARSET=utf8;


-- RELEASE ASP_2007-08_10 ----------------------------------------

ALTER TABLE `sessiondata` CHANGE `data` `data` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- RELEASE ASP_2007_09_27 ----------------------------------------
-- new schema for parent portal ---

CREATE TABLE `portaluser` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`username` VARCHAR( 255 ) NOT NULL ,
`password` VARCHAR( 50 ) NOT NULL ,
`firstname` VARCHAR( 100 ) NOT NULL ,
`lastname` VARCHAR( 100 ) NOT NULL ,
`zipcode` VARCHAR( 10 ) NOT NULL ,
`enabled` TINYINT NOT NULL DEFAULT '0',
UNIQUE (
`username`
)
) ENGINE = innodb;

CREATE TABLE `portalactivation` (
`activationtoken` VARCHAR( 255 ) NOT NULL ,
`creation` timestamp NOT NULL default CURRENT_TIMESTAMP ,
`portaluserid` INT NOT NULL DEFAULT '0'
) ENGINE = innodb;

CREATE TABLE `portalcustomer` (
`portaluserid` INT NOT NULL ,
`customerid` INT NOT NULL
) ENGINE = innodb;

