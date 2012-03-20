-- $rev 1

ALTER TABLE `person` CHANGE `type` `type` ENUM( 'system', 'addressbook', 'manualadd', 'upload', 'subscriber',  'guardianauto',  'guardiancm') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'system'
$$$

-- fix subscriber person
update person set type = 'subscriber' where type = 'system' and importid is null
$$$

-- $rev 2
-- removed

-- $rev 3

-- need more chars for descriptive names
ALTER TABLE `template` CHANGE `type` `type` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

-- rename
update template set type = 'subscriber-accountexpire' where type = 'subscriber'
$$$

-- $rev 4

-- allow mapping guardian fields on person import
ALTER TABLE `importfield`
  ADD `guardiansequence` TINYINT( 4 ) NULL DEFAULT NULL AFTER `importid`,
  ADD `guardiancategoryid` INT(11) NULL DEFAULT NULL AFTER `guardiansequence`
$$$

CREATE TABLE `guardiancategory` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(50) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- dev team rev1 created
drop table if exists `personguardian`
$$$

CREATE TABLE `personguardian` (
  `personid` int(11) NOT NULL,
  `guardianpersonid` int(11) NOT NULL,
  `guardiancategoryid` int(11) NOT NULL,
  `importid` int(11) NULL,
  `importstatus` enum('none','checking','new') DEFAULT NULL,
  PRIMARY KEY (`personid`,`guardianpersonid`,`guardiancategoryid`),
  INDEX guardian ( `guardianpersonid`,`guardiancategoryid` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 5

CREATE TABLE `importmicroupdate` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `importid` int(11) NOT NULL,
 `updatemethod` ENUM( 'update', 'delete' ) NOT NULL,
 `data` blob NOT NULL,
 `datalength` int(11) NOT NULL,
 `datamodifiedtime` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `importid` (`importid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

