
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

ALTER TABLE `dmsetting` DROP `dmuuid`;
ALTER TABLE `dmsetting` DROP PRIMARY KEY;
ALTER TABLE `dmsetting` ADD `dmid` INT NOT NULL FIRST;
ALTER TABLE `dmsetting` ADD PRIMARY KEY ( `dmid` , `name` );

-- for system dms
CREATE TABLE `dmroute` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `match` varchar(20) NOT NULL,
  `strip` int(11) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `suffix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dmid` (`dmid`,`match`)
) ENGINE=InnoDB ;

ALTER TABLE `dm` ADD `command` VARCHAR( 255 ) NULL ;

CREATE TABLE `dmcalleridroute` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `callerid` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dmid` (`dmid`,`callerid`)
) ENGINE=InnoDB ;

ALTER TABLE `shard` ADD `isfull` TINYINT NOT NULL DEFAULT '0';

-- ASP 6.0 June 14, 2008

CREATE TABLE `dmdatfile` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `uploaddate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `data` mediumtext NOT NULL,
  `notes` TEXT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;

ALTER TABLE `dm` ADD `version` VARCHAR( 255 ) NULL ;

-- ASP 6.0.1

ALTER TABLE `dm` ADD `routetype` VARCHAR( 50 ) NOT NULL DEFAULT '';

ALTER TABLE `customer` ADD `oemid` VARCHAR( 50 ) NOT NULL AFTER `id` ,
ADD `nsid` VARCHAR( 50 ) NOT NULL AFTER `oemid` ;

ALTER TABLE `customer` ADD `oem` VARCHAR( 50 ) NOT NULL AFTER `id` ;

ALTER TABLE `customer` ADD `notes` VARCHAR( 255 ) NOT NULL AFTER `logintokenexpiretime` ;


CREATE TABLE `useractivation` (
`activationtoken` VARCHAR( 255 ) NOT NULL ,
`creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`customerid` INT NOT NULL ,
`userid` INT NOT NULL
) ENGINE = innodb;

-- ASP 6.2 starts below

 ALTER TABLE `loginattempt`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

 ALTER TABLE `loginattempt` CHANGE `login` `login` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ;

 ALTER TABLE `persontoken`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

 ALTER TABLE `persontoken`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

 ALTER TABLE `persontoken` CHANGE `validationdata` `validationdata` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL  ;

 ALTER TABLE `portalactivation`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

 ALTER TABLE `portalactivation` CHANGE `activationtoken` `activationtoken` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL  ;

 ALTER TABLE `portalactivation` CHANGE `newusername` `newusername` VARCHAR( 255 ) NULL DEFAULT NULL  ;

 ALTER TABLE `portaluser`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

 ALTER TABLE `portaluser` CHANGE `username` `username` VARCHAR( 255 ) NOT NULL ,
CHANGE `password` `password` VARCHAR( 50 ) NOT NULL DEFAULT ' ',
CHANGE `firstname` `firstname` VARCHAR( 100 ) NOT NULL ,
CHANGE `lastname` `lastname` VARCHAR( 100 ) NOT NULL ,
CHANGE `zipcode` `zipcode` VARCHAR( 10 ) NOT NULL ;

 ALTER TABLE `useractivation`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

 ALTER TABLE `useractivation` CHANGE `activationtoken` `activationtoken` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL  ;

CREATE TABLE `cmphoneactivation` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL ,
  `personid` int(11) NOT NULL ,
  `portaluserid` int(11) NOT NULL ,
  `code` VARCHAR( 20 ) NOT NULL ,
  `phone` VARCHAR( 20 ) NOT NULL ,
  `expirationdate` DATETIME NOT NULL ,
  PRIMARY KEY ( `id` ) ,
  INDEX ( `phone` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

ALTER TABLE `portaluser` ADD `notifysmstype` ENUM( 'none', 'message' ) NOT NULL DEFAULT 'none',
ADD `sms` VARCHAR( 20 ) NOT NULL ;

drop table cmphoneactivation;

CREATE TABLE `portalphoneactivation` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL ,
  `personid` int(11) NOT NULL ,
  `portaluserid` int(11) NOT NULL ,
  `code` VARCHAR( 20 ) NOT NULL ,
  `phone` VARCHAR( 20 ) NOT NULL ,
  `expirationdate` DATETIME NOT NULL ,
  PRIMARY KEY ( `id` ) ,
  INDEX ( `phone` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;


ALTER TABLE `portalactivation` ADD INDEX ( `creation` );

ALTER TABLE `persontoken` ADD INDEX ( `expirationdate` );

ALTER TABLE `portalphoneactivation` ADD INDEX ( `expirationdate` );

ALTER TABLE `customer` ADD INDEX ( `oem` , `oemid` ) ;

ALTER TABLE `aspadminuser` ADD `preferences` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
ADD `permissions` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;


insert into dmsetting (dmid,name,value) select id,'disable_congestion_throttle','1' from dm where `type`='system';

-- set superuser on id 1
UPDATE aspadminuser set permissions='logincustomer,newcustomer,editcustomer,editpriorities,customercontacts,users,imports,editimportalerts,lockedusers,smsblock,activejobs,editdm,superuser' where id=1;
-- everyone else gets full perms by default
UPDATE aspadminuser set permissions='logincustomer,newcustomer,editcustomer,editpriorities,customercontacts,users,imports,editimportalerts,lockedusers,smsblock,activejobs,editdm' where id!=1;

-- ASP_6-2

ALTER TABLE `dm` ADD `poststatus` TEXT NOT NULL default '' ;

ALTER TABLE `dm` CHANGE `authorizedip` `authorizedip` VARCHAR( 31 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ;

ALTER TABLE `portaluser` ADD `preferences` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `sms` ;


CREATE TABLE `authserver`.`webactivation` (
`activationtoken` VARCHAR( 255 ) NOT NULL ,
`creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`customerid` INT NULL ,
`userid` INT NULL ,
`subscriberid` INT NULL ,
`newusername` VARCHAR( 255 ) NULL ,
`forgotpassword` TINYINT NOT NULL ,
`options` TEXT NULL ,
PRIMARY KEY ( `activationtoken` )
) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

ALTER TABLE `webactivation` DROP `newusername`;

ALTER TABLE `webactivation` DROP `forgotpassword`;

drop table useractivation;

ALTER TABLE `portalphoneactivation` ADD `options` TEXT NULL;

ALTER TABLE `customer` ADD `limitedusername` VARCHAR( 50 ) NOT NULL DEFAULT '' AFTER `dbpassword` ,
ADD `limitedpassword` VARCHAR( 50 ) NOT NULL DEFAULT '' AFTER `limitedusername`;

ALTER TABLE `shard` ADD `readonlyhost` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `dbpassword` ;

-- missing indexes

ALTER TABLE `customer` ADD INDEX ( `shardid` ) ;

ALTER TABLE `webactivation` ADD INDEX ( `creation` ) ;

ALTER TABLE `dm` CHANGE `poststatus` `poststatus` MEDIUMTEXT NOT NULL ;
 
-- start here for 7.5

ALTER TABLE `dm` CHANGE `poststatus` `poststatus` MEDIUMTEXT ;

-- 7.8 manager enhancement

CREATE TABLE `aspadminquery` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`name` VARCHAR( 255 ) NOT NULL ,
	`notes` text NOT NULL,
	`query` TEXT NOT NULL ,
	`numargs` TINYINT NOT NULL
) ENGINE = InnoDB;

ALTER TABLE `aspadminuser` ADD `queries` TEXT NOT NULL ;

