-- Upgrade from release 5.2 to 5.3 -- June 13, 2008

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
declare l_custid int DEFAULT _$CUSTOMERID_;
declare l_type varchar(50);
DECLARE rdm VARCHAR(50);
DECLARE dtype VARCHAR(50) DEFAULT 'system';

select type from specialtask where id=in_specialtaskid into l_type;

SELECT value INTO rdm FROM setting WHERE name='_dmmethod';
IF rdm='hybrid' or rdm='cs' THEN
  SET dtype = 'customer';
END IF;

insert ignore into aspshard.specialtaskqueue (customerid,localspecialtaskid,type,dispatchtype) values (l_custid,in_specialtaskid,l_type,dtype);
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

-- ---------------------------------------------------------------------
-- data changes (not just schema) from here on...

insert into setting (name, value) values ('_dmmethod', 'asp')
$$$


