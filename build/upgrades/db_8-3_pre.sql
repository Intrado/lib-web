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
  ADD `guardiansequence` TINYINT( 4 ) NULL DEFAULT NULL AFTER `importid`
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

-- $rev 6

ALTER table `guardiancategory`
ADD `sequence` tinyint(4) NOT NULL
$$$

-- $rev 7

ALTER TABLE user 
    ADD globaluserid int DEFAULT NULL,
    ADD personid int DEFAULT NULL
$$$
 
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `profileid` int(11) NOT NULL,
  `organizationid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
ALTER TABLE `organization` 
    ADD `parentorganizationid` INT NULL AFTER `id`,
    ADD `createdtimestamp` INT DEFAULT NULL ,
    ADD `modifiedtimestamp` INT DEFAULT NULL
$$$
 
ALTER TABLE `setting` ADD `organizationid` INT NULL AFTER `id`
$$$

-- $rev 8

ALTER TABLE `job` CHANGE `status` `status` ENUM( 'new', 'scheduled', 'processing', 'procactive', 'active', 'complete', 'cancelled', 'cancelling', 'repeating', 'template' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'new'
$$$

-- $rev 9

CREATE TABLE `userlink` (
  `userid` int(11) NOT NULL,
  `subordinateuserid` int(11) NOT NULL,
  PRIMARY KEY (`userid`,`subordinateuserid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$


-- $rev 10

CREATE TABLE `reportemaildelivery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` bigint(20) NOT NULL,
  `jobid` int(11) DEFAULT NULL,
  `personid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `sequence` int(11) DEFAULT NULL,
  `replytoname` varchar(100) NOT NULL,
  `replytodomain` varchar(100) NOT NULL,
  `recipientname` varchar(100) NOT NULL,
  `recipientdomain` varchar(100) NOT NULL,
  `statuscode` smallint(4) unsigned NOT NULL,
  `responsetext` text NOT NULL,
  `recordsource` enum('customer_job','contact_manager','password_reset','report_subscription','subscriber_expiration','cm_password_reset','job_monitor','internal_monitoring') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`timestamp`),
  KEY `recipient` (`timestamp`,`recipientname`,`recipientdomain`),
  KEY `status` (`timestamp`,`statuscode`),
  KEY `source` (`timestamp`,`recordsource`),
  KEY `user` (`timestamp`,`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=17838 DEFAULT CHARSET=utf8
$$$