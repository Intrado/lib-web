-- CommSuite 5.2 to 6.0 upgrade schema

-- ----------------------------------------------------------------------------------
-- authserver database

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

ALTER TABLE `dm` ADD `command` VARCHAR( 255 ) NULL ;
ALTER TABLE `dm` DROP PRIMARY KEY ;
ALTER TABLE `dm` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
ALTER TABLE `dm` ADD UNIQUE (`dmuuid`) ;

ALTER TABLE `dmsetting` DROP `dmuuid`;
ALTER TABLE `dmsetting` DROP PRIMARY KEY;
ALTER TABLE `dmsetting` ADD `dmid` INT NOT NULL FIRST;
ALTER TABLE `dmsetting` ADD PRIMARY KEY ( `dmid` , `name` );

-- for system dms

ALTER TABLE `shard` ADD `isfull` TINYINT NOT NULL DEFAULT '0';


-- ----------------------------------------------------------------------------------
-- shard database

ALTER TABLE `jobstatdata` ADD `type` ENUM( 'system', 'customer' ) NOT NULL DEFAULT 'system' FIRST ;
ALTER TABLE `jobstatdata` ADD INDEX `remotedm` ( `type` , `customerid` )  ;
ALTER TABLE `qjob` ADD `dispatchtype` ENUM( 'customer', 'system' ) NOT NULL DEFAULT 'system';
ALTER TABLE `specialtaskqueue` ADD `dispatchtype` ENUM( 'customer', 'system' ) NOT NULL DEFAULT 'system';


-- -----------------------------------------------------
-- customer database

delimiter $$$

CREATE TABLE `custdm` (
  `dmid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `enablestate` enum('new','active','disabled') NOT NULL,
  PRIMARY KEY  (`dmid`)
) ENGINE=InnoDB
$$$


CREATE TABLE `dmroute` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `match` varchar(20) NOT NULL,
  `strip` int(11) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `suffix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dmid` (`dmid`,`match`)
) ENGINE=InnoDB
$$$

ALTER TABLE `destlabel` ADD `notes` TEXT NULL
$$$
ALTER TABLE `import` ADD `notes` TEXT NULL AFTER `description`
$$$
ALTER TABLE `destlabel` CHANGE `notes` `notes` VARCHAR( 255 ) NULL
$$$
ALTER TABLE `custdm` ADD `routechange` INT NULL
$$$
ALTER TABLE `import` ADD `alertoptions` TEXT NULL
$$$


drop procedure start_specialtask
$$$

create procedure start_specialtask( in_specialtaskid int)
begin
declare l_custid int DEFAULT 1;
declare l_type varchar(50);
DECLARE rdm VARCHAR(50);
DECLARE dtype VARCHAR(50) DEFAULT 'system';

select type from specialtask where id=in_specialtaskid into l_type;

SELECT value INTO rdm FROM setting WHERE name='_dmmethod';
IF rdm='hybrid' or rdm='cs' THEN
  SET dtype = 'customer';
END IF;

insert ignore into specialtaskqueue (customerid,localspecialtaskid,type,dispatchtype) values (l_custid,in_specialtaskid,l_type,dtype);
end
$$$


CREATE TABLE `dmcalleridroute` (
  `id` int(11) NOT NULL auto_increment,
  `dmid` int(11) NOT NULL,
  `callerid` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dmid` (`dmid`,`callerid`)
) ENGINE=InnoDB
$$$


ALTER TABLE `custdm` ADD `telco_type` ENUM( 'Test', 'Asterisk', 'Jtapi' ) NOT NULL DEFAULT 'Test' AFTER `enablestate`
$$$

ALTER TABLE `custdm` CHANGE `routechange` `routechange` TINYINT( 4 )
$$$

ALTER TABLE `reportcontact` ADD `voicereplyid` INT(11) NULL ,
ADD `response` TINYINT(4) NULL
$$$

delete from setting where name = '_dmmethod'
$$$
insert into setting (name, value) values ('_dmmethod', 'cs')
$$$

delimiter ;

