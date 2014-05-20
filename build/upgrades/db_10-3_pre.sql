-- $rev 1

-- note this is a short lived table and will be replaced in near future after more infocenter schema is worked out
CREATE TABLE `ic_portalperson` (
 `portaluserid` int(11) NOT NULL,
 `personid` int(11) NOT NULL,
 PRIMARY KEY (`portaluserid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 2

-- deprecate fields that move to new useraccess table. no change to user imports they still use these deprecated fields
ALTER TABLE `user`
CHANGE `accessid` `accessid` INT( 11 ) NULL DEFAULT NULL COMMENT 'deprecated, see useraccess.accessid', 
CHANGE `personid` `personid` INT( 11 ) NULL DEFAULT NULL COMMENT 'deprecated, see useraccess.personid'
$$$

-- fix foobar accessid value
UPDATE `user` set accessid = null where accessid = 0
$$$

-- imports create with accessid=NULL and GUI to manage setting access
ALTER TABLE `guardiancategory` ADD `accessid` INT NULL
$$$
 
-- support for various types of access profile
ALTER TABLE `access` ADD `type` ENUM( 'cs', 'guardian', 'identity' ) NOT NULL DEFAULT 'cs'
$$$
 
-- create special access profile for user identities
insert into `access` (`name`, `description`, `type`) values ('User Identity', 'Links a user to their contact information', 'identity')
$$$
insert into `permission` (`accessid`, `name`, `value`) values (LAST_INSERT_ID(), 'fullaccess', '1')
$$$
 
-- user access to object type
CREATE TABLE `useraccess` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `accessid` int(11) NOT NULL,
  `type` enum('organization','section','person') NOT NULL DEFAULT 'person',
  `personid` int(11) DEFAULT NULL,
  `organizationid` int(11) DEFAULT NULL COMMENT 'future use',
  `sectionid` int(11) DEFAULT NULL COMMENT 'future use',
  `importid` int(11) DEFAULT NULL COMMENT 'future use',
  `importstatus` enum('none','checking','new') NOT NULL DEFAULT 'none' COMMENT 'future use',
  PRIMARY KEY (`id`),
  INDEX  `userid` (`userid` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
 
-- $rev 3

DROP TABLE `ic_portalperson`
$$$
