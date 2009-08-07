-- Upgrade from release 6.2 to 7.0


-- Add aditional import field types
ALTER TABLE `importfield` CHANGE `action` `action` ENUM( 'copy', 'staticvalue', 'number', 'currency', 'date', 'lookup', 'curdate', 
	'numeric', 'currencyleadingzero' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'copy'
$$$


ALTER TABLE `custdm` ADD `poststatus` TEXT NOT NULL default ''
$$$


CREATE TABLE `subscriber` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`username` VARCHAR( 255 ) NOT NULL ,
`password` VARCHAR( 50 ) NOT NULL ,
`personid` INT NULL ,
`lastlogin` DATETIME NULL ,
`enabled` TINYINT NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin
$$$

ALTER TABLE `persondatavalues` ADD `editlock` TINYINT NOT NULL DEFAULT '0'
$$$

ALTER TABLE `subscriber` ADD `preferences` TEXT NOT NULL DEFAULT ''
$$$

-- dev network is here

update fieldmap set options = 'searchable,text,firstname,subscribe,dynamic' where options like '%firstname%'
$$$

update fieldmap set options = 'searchable,text,lastname,subscribe,dynamic' where options like '%lastname%'
$$$

update fieldmap set options = 'searchable,multisearch,language,subscribe,static' where options like '%language%'
$$$

CREATE TABLE `subscriberpending` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`subscriberid` INT NOT NULL ,
`type` ENUM( 'phone', 'email', 'sms' ) NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL ,
`token` VARCHAR( 255 ) NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin
$$$

insert into setting (name, value) select '_subscriberloginpicturecontentid', value from setting where name='_loginpicturecontentid'
$$$


CREATE TABLE IF NOT EXISTS `prompt` (
  `id` int(11) NOT NULL auto_increment,
  `type` enum('intro','emergencyintro','langmenu') NOT NULL,
  `messageid` int(11) NOT NULL,
  `dtmf` tinyint(4) default NULL,
  `language` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `subscriber` ADD UNIQUE `username` ( `username` ) 
$$$

ALTER TABLE `subscriber` ADD `lastreminder` DATETIME NULL DEFAULT NULL AFTER `lastlogin`
$$$

-- missing indexes

ALTER TABLE `permission` ADD INDEX ( `accessid` )
$$$

ALTER TABLE `surveyquestionnaire` ADD INDEX ( `userid` ) 
$$$

ALTER TABLE `job` ADD INDEX `useraccess` ( `userid` , `status` , `deleted` ) 
$$$

ALTER TABLE `systemstats` ADD INDEX `graphs` ( `date` , `attempt` ) 
$$$

ALTER TABLE `person` DROP INDEX `pkeysortb` 
$$$

ALTER TABLE `person` DROP INDEX `pkeysort` ,
ADD INDEX `pkeysort` ( `pkey` , `type` , `deleted` ) 
$$$

ALTER TABLE `blockednumber` ADD INDEX ( `userid` ) 
$$$

ALTER TABLE `person` DROP INDEX `namesort` 
$$$

ALTER TABLE `person` DROP INDEX `getbykey` 
$$$

ALTER TABLE `person` DROP INDEX `general` 
$$$

ALTER TABLE `person` ADD INDEX ( `f01` ) 
$$$

ALTER TABLE `person` ADD INDEX ( `f02` ) 
$$$

ALTER TABLE `listentry` ADD INDEX `listrule` ( `listid` , `type` , `personid` )
$$$

delete s2 from setting s1 left join setting s2 on (s1.name = s2.name and s1.id > s2.id) where s2.id is not null
$$$
ALTER TABLE `setting` DROP INDEX `lookup` , ADD UNIQUE `name` ( `name` ) 
$$$

ALTER TABLE `job` ADD `modifydate` datetime AFTER `createdate`
$$$

ALTER TABLE `job` ADD INDEX ( `modifydate` )
$$$

ALTER TABLE `message` ADD `modifydate` datetime AFTER `data`
$$$

ALTER TABLE `message` ADD INDEX ( `modifydate` )
$$$

ALTER TABLE `list` ADD `modifydate` datetime AFTER `description`
$$$

ALTER TABLE `list` ADD INDEX ( `modifydate` )
$$$

ALTER TABLE `reportsubscription` ADD `modifydate` datetime AFTER `time`
$$$

ALTER TABLE `reportsubscription` ADD INDEX ( `modifydate` )
$$$

CREATE TABLE IF NOT EXISTS `systemmessages` (
  `id` int(11) NOT NULL auto_increment,
  `message` VARCHAR( 1000 ) NOT NULL,
  `icon` VARCHAR( 50 ),
  `modifydate` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  INDEX (`modifydate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `reportcontact` CHANGE `resultdata` `resultdata` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL 
$$$


-- set all 3dblue to classroom

update setting s1
inner join setting s2 on (s1.value='3dblue' and s2.name='_brandprimary')
inner join setting s3 on (s1.value='3dblue' and s3.name='_brandtheme1')
inner join setting s4 on (s1.value='3dblue' and s4.name='_brandtheme2')
inner join setting s5 on (s1.value='3dblue' and s5.name='_brandratio')

set s1.value='classroom',
s2.value='3e693f',
s3.value='3e693f',
s4.value='b47727',
s5.value='.2'

where s1.name='_brandtheme' and s1.value='3dblue'

$$$

-- update user prefs from 3dblue to classroom

update usersetting u1
join usersetting u2 on (u1.userid = u2.userid and u2.name='_brandprimary')
join usersetting u3 on (u1.userid = u3.userid and u3.name='_brandtheme1')
join usersetting u4 on (u1.userid = u4.userid and u4.name='_brandtheme2')
join usersetting u5 on (u1.userid = u5.userid and u5.name='_brandratio')

set u1.value='classroom',
u2.value='3e693f',
u3.value='3e693f',
u4.value='b47727',
u5.value='.2'

where u1.name = '_brandtheme' and u1.value='3dblue'

$$$