
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

ALTER TABLE `customer`
ADD `portaldbuser` VARCHAR( 50 ) NOT NULL default '' AFTER `dbpassword` ,
ADD `portaldbpass` VARCHAR( 50 ) NOT NULL default '' AFTER `portaldbuser` ;

CREATE TABLE `persontoken` (
  `customerid` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `validationdata` varchar(50) NOT NULL,
  `expirationdate` datetime NOT NULL,
  `personid` int(11) NOT NULL,
  PRIMARY KEY  (`customerid`,`token`,`validationdata`)
) ENGINE=InnoDB;

CREATE TABLE `portalactivation` (
  `activationtoken` varchar(255) NOT NULL,
  `creation` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `portaluserid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`activationtoken`)
) ENGINE=InnoDB;

ALTER TABLE `portalactivation` ADD `newusername` VARCHAR( 255 ) NULL ;

CREATE TABLE `portalcustomer` (
  `portaluserid` int(11) NOT NULL,
  `customerid` int(11) NOT NULL,
  PRIMARY KEY  (`portaluserid`,`customerid`)
) ENGINE=InnoDB;

CREATE TABLE `portaluser` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(50) NOT NULL default ' ',
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `zipcode` varchar(10) NOT NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  `lastlogin` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB;


ALTER TABLE `portalactivation` ADD `forgotpassword` TINYINT NOT NULL DEFAULT '0';


ALTER TABLE `persontoken` CHANGE `expirationdate` `expirationdate` DATE NOT NULL;

ALTER TABLE `customer`
DROP `portaldbuser` ,
DROP `portaldbpass` ;

ALTER TABLE `portaluser` CHANGE `lastlogin` `lastlogin` BIGINT NULL DEFAULT NULL ;


-- Dec 10

ALTER TABLE `persontoken` DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `customerid` , `validationdata` , `token` ) ;

ALTER TABLE `persontoken` ADD INDEX `token` ( `token` ) ;

-- Dec 13

ALTER TABLE `portaluser` ADD `notify` ENUM( 'none', 'message' ) NOT NULL DEFAULT 'none' ;

-- Dec 17

 CREATE TABLE `loginattempt` (
`customerid` INT NOT NULL ,
`login` VARCHAR( 20 ) NOT NULL ,
`ipaddress` VARCHAR( 15 ) NOT NULL ,
`attempts` TINYINT NOT NULL ,
`lastattempt` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
`status` ENUM( 'enabled', 'disabled', 'lockout' ) NOT NULL DEFAULT 'enabled' ,
PRIMARY KEY ( `customerid` , `login` ) ,
INDEX ( `status` )
) ENGINE = innodb ;

-- 5.2


CREATE TABLE `dm` (
  `dmuuid` varchar(50) NOT NULL,
  `type` enum('system','customer') NOT NULL default 'customer',
  `name` varchar(255) character set utf8 NOT NULL,
  `authorizedip` varchar(15) default NULL,
  `lastseen` bigint(20) default NULL,
  `lastip` varchar(15) default NULL,
  `customerid` int(11) default NULL,
  `enablestate` enum('new','active','disabled') NOT NULL default 'new',
  PRIMARY KEY  (`dmuuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `dmsetting` (
`dmuuid` VARCHAR( 255 ) NOT NULL ,
`name` VARCHAR( 255 ) NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `dmuuid` , `name` )
) ENGINE = innodb;

ALTER TABLE `dm` DROP PRIMARY KEY ;
ALTER TABLE `dm` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
ALTER TABLE `dm` ADD UNIQUE (`dmuuid`) ;

