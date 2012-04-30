
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

-- 7.7.2 manager enhancement

CREATE TABLE `aspadminquery` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`name` VARCHAR( 255 ) NOT NULL ,
	`notes` text NOT NULL,
	`query` TEXT NOT NULL ,
	`numargs` TINYINT NOT NULL
) ENGINE = InnoDB;

ALTER TABLE `aspadminuser` ADD `queries` TEXT NOT NULL ;

-- START REV 7.8/1

ALTER TABLE `portaluser` ADD `salt` VARCHAR( 29 ) NOT NULL AFTER `password` ,
ADD `passwordversion` TINYINT NOT NULL AFTER `salt` ;

update portaluser set passwordversion = 1 where length(password) > 16 ;

-- 7.7.x aspadminquery enhancement
ALTER TABLE `aspadminquery` ADD `options` TEXT NOT NULL;


-- START REV 8.0/5

-- fix password to allow NULL
ALTER TABLE `portaluser` CHANGE `password` `password` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
CHANGE `salt` `salt` VARCHAR( 29 ) CHARACTER SET utf8 COLLATE utf8_bin NULL,
CHANGE `passwordversion` `passwordversion` TINYINT( 4 ) NOT NULL DEFAULT '0' ;


-- START REV 8.1/1

-- add dm notes
ALTER TABLE `dm` ADD `notes` TEXT;

-- add soft delete feture for dat files
ALTER TABLE `dmdatfile` ADD `deleted` tinyint(4) NOT NULL default '0';

-- START REV 8.1/2

-- add soft delete for dm 
ALTER TABLE `dm` CHANGE `enablestate` `enablestate` enum('new','active','disabled','deleted') NOT NULL default 'new';

-- add table to keep track of unassigned tolll free numbers
CREATE TABLE `tollfreenumbers` (
 	`phone` VARCHAR( 20 ) NOT NULL,
 	 UNIQUE KEY `phone` (`phone`)
) ENGINE = InnoDB;

CREATE TABLE `dmgroup` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`carrier` varchar(50) NOT NULL ,
`state` CHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL
) ENGINE = InnoDB;

ALTER TABLE `dm` ADD `dmgroupid` INT NULL AFTER `dmuuid`;

ALTER TABLE tollfreenumbers DROP INDEX phone;
ALTER TABLE `tollfreenumbers` CHANGE  `phone` `phone` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL PRIMARY KEY;

-- create a table to hold server names/notes
CREATE TABLE IF NOT EXISTS `server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `notes` text NOT NULL,
  `production` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `production` (`production`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `serversetting` (
  `serverid` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `value` varchar(255) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`serverid`,`name`)
) ENGINE=InnoDB;

ALTER TABLE `server` DROP INDEX `id` ;
ALTER TABLE `server` CHANGE `name` `hostname` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `server` CHANGE `production` `runmode` ENUM( 'active', 'standby', 'testing' ) NOT NULL ;
ALTER TABLE `server` CHANGE `notes` `notes` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

DROP TABLE `serversetting`;

CREATE TABLE `service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `type` enum('commsuite','kona') NOT NULL,
  `runmode` enum('all','active','standby') NOT NULL,
  `notes` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serverid` (`serverid`,`type`,`runmode`)
) ENGINE=InnoDB;

CREATE TABLE `serviceattribute` (
  `serviceid` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `value` varchar(255) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`serviceid`,`name`)
) ENGINE=InnoDB;

ALTER TABLE `aspadminuser` ADD `deleted` tinyint(4) NOT NULL default '0';

-- new column authserver.customer.shortcodegroupid
ALTER TABLE `customer` ADD `shortcodegroupid` INT NOT NULL DEFAULT '1' ;
 
 
CREATE TABLE `shortcodegroup` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `description` varchar(255) NOT NULL,
 `queuecapacity` int(11) NOT NULL,
 `numthreads` int(11) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
 
 
CREATE TABLE `smsaggregator` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(20) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
 
 
CREATE TABLE `shortcode` (
 `shortcode` varchar(10) NOT NULL,
 `smsaggregatorid` int(11) NOT NULL,
 `shortcodegroupid` int(11) NOT NULL,
 PRIMARY KEY (`shortcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
 
 
CREATE TABLE `shortcodeareacode` (
 `shortcode` varchar(10) NOT NULL,
 `areacode` VARCHAR( 3 ) NULL DEFAULT NULL,
 PRIMARY KEY (`shortcode`,`areacode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
 
CREATE TABLE `shortcodetext` (
 `shortcode` varchar(10) NOT NULL,
 `messagetype` varchar(20) NOT NULL,
 `text` varchar(200) NOT NULL,
 PRIMARY KEY (`shortcode`,`messagetype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
 

INSERT INTO `smsaggregator` (`name`) VALUES ('air2web') ;

INSERT INTO `shortcodegroup` (`description`, `queuecapacity`, `numthreads`) VALUES ('SchoolMessenger', 10000, 1) ;

INSERT INTO `shortcode` (`shortcode` ,`smsaggregatorid` ,`shortcodegroupid`) VALUES
('68453', 1, 1),
('724665', 1, 1) ;

INSERT INTO `shortcodeareacode` (`shortcode`, `areacode`) VALUES
('68453', ''),
('724665', '204'),
('724665', '289'),
('724665', '306'),
('724665', '403'),
('724665', '416'),
('724665', '418'),
('724665', '450'),
('724665', '506'),
('724665', '514'),
('724665', '519'),
('724665', '604'),
('724665', '613'),
('724665', '647'),
('724665', '705'),
('724665', '709'),
('724665', '778'),
('724665', '780'),
('724665', '807'),
('724665', '819'),
('724665', '867'),
('724665', '902'),
('724665', '905') ;

INSERT INTO `shortcodetext` (`shortcode` ,`messagetype` ,`text`) VALUES
('68453',  'OPTIN',   'You are registered to receive aprox 3 msgs/mo. Txt STOP to quit, HELP for help. Msg&data rates may apply. schoolmessenger.com/tm'),
('724665', 'OPTIN',   'You are registered to receive aprox 3 msgs/mo. Txt STOP to quit, HELP for help. Std msg/data rates apply. schoolmessenger.com/tm'),
('68453',  'OPTOUT',  'You are unsubscribed. Reply Y to re-subscribe for aprox 3 msgs/mo. HELP for help. Msg&data rates may apply. schoolmessenger.com/tm'),
('724665', 'OPTOUT',  'You are unsubscribed. Reply Y to re-subscribe for aprox 3 msgs/mo. HELP for help. Std msg/data rates apply. schoolmessenger.com/tm'),
('68453',  'HELP',    'Text messages by SchoolMessenger. Reply Y for aprox 3 msgs/mo. Text STOP to quit. Msg&data rates may apply. schoolmessenger.com/tm'),
('724665', 'HELP',    'Text messages by SchoolMessenger. Reply Y for aprox 3 msgs/mo. Text STOP to quit. Std msg/data rates apply. schoolmessenger.com/tm'),
('68453',  'INFO',    'Unknown response. Reply Y to subscribe for aprox 3 msgs/mo. Text STOP to quit. For more information reply HELP.'),
('724665', 'INFO',    'Unknown response. Reply Y to subscribe for aprox 3 msgs/mo. Text STOP to quit. For more information reply HELP.'),
('68453',  'PENDINGOPTIN', '%s messages. Reply Y for aprx 3 msgs/mo. Txt HELP 4 info. Msg&data rates may apply. See schoolmessenger.com/tm'),
('724665', 'PENDINGOPTIN', '%s messages. Reply Y for aprx 3 msgs/mo. Txt HELP 4 info. Std msg/data rates apply. See schoolmessenger.com/tm') ;


UPDATE aspadminuser set deleted=1 where password='disabled' and queries='';

CREATE TABLE `smsinbound` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `datereceived` varchar(20) NOT NULL,
 `shortcode` varchar(10) NOT NULL,
 `smsnumber` varchar(20) NOT NULL,
 `message_id` varchar(20) NOT NULL,
 `message` varchar(160) NOT NULL,
 `message_orig` varchar(160) NOT NULL,
 `carrier` varchar(20) NOT NULL,
 `channel` varchar(20) NOT NULL,
 `router` varchar(20) NOT NULL,
 `action` ENUM( 'SKIP', 'INFO', 'HELP', 'OPTIN', 'OPTOUT' ) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- START REV 8.2/1

CREATE TABLE `dmgroupblock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dmgroupid` int(11) NOT NULL,
  `pattern` varchar(50) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

ALTER TABLE `dmgroupblock` ADD INDEX ( `dmgroupid` );


-- START REV 8.3/1

CREATE TABLE `portaluseridentification` (
`portaluserid` INT NOT NULL ,
`type` ENUM( 'local', 'facebook', 'twitter', 'google', 'yahoo' ) NOT NULL ,
`username` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`secret` VARCHAR( 120 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`createdtimestamp` INT NOT NULL ,
`modifiedtimestamp` INT NOT NULL ,
PRIMARY KEY ( `portaluserid` , `type` )
) ENGINE = InnoDB;
 
-- move password into identification table
INSERT INTO portaluseridentification (portaluserid, type, username, secret, createdtimestamp, modifiedtimestamp) 
    SELECT id, 'local', username, concat('{ver:', passwordversion, ',salt:"', salt, '", hash:"', password, '"}'), unix_timestamp(), unix_timestamp() 
    FROM portaluser WHERE passwordversion = 2;
-- no salt on older versions
INSERT INTO portaluseridentification (portaluserid, type, username, secret, createdtimestamp, modifiedtimestamp) 
    SELECT id, 'local', username, concat('{ver:', passwordversion, ', hash:"', password, '"}'), unix_timestamp(), unix_timestamp() 
    FROM portaluser WHERE passwordversion != 2;

-- remove obsolete fields, moved into portaluseridentfication
ALTER TABLE `portaluser`
  DROP `username`,
  DROP `password`,
  DROP `salt`,
  DROP `passwordversion`;

-- customer product association
CREATE TABLE `customerproduct` (
`customerid` INT NOT NULL ,
`product` ENUM( 'cs', 'tai' ) NOT NULL ,
`createdtimestamp` INT NOT NULL COMMENT 'when product was added',
`modifiedtimestamp` INT NOT NULL ,
`enabled` TINYINT NOT NULL ,
PRIMARY KEY ( `customerid` , `product` )
) ENGINE = InnoDB;

-- all existing customers of commsuite product
INSERT INTO customerproduct
    SELECT id, 'cs', unix_timestamp(), unix_timestamp(), enabled from customer;

-- add local user id, note existing portaluser for contact manager do not have commsuite users so default=0 is fine
ALTER TABLE `portalcustomer` 
ADD `userid` INT NULL COMMENT 'local userid',
DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `portaluserid` , `customerid` , `userid` )
;

