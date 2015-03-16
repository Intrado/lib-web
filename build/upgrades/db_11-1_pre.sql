-- $rev 1

CREATE TABLE `persondevice` (
  `personid` int(11) NOT NULL,
  `deviceUuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  PRIMARY KEY (`personid`,`deviceUuid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
 
ALTER TABLE `contactpref` CHANGE `type` `type` ENUM('phone','email','print','sms','device') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

-- $rev 2

-- no schema just update _hasinfocenter settings

-- $rev 3

-- no schema, disable all _hasinfocenter (keeping _hasicplus)
-- manual process by support to enable infocenter and guardian data for our customers

-- $rev 4

-- rename guardian profile permission
update permission set name = 'icplus' where name = 'infocenter'
$$$

-- $rev 5

-- rename persondevice table to device
drop table if exists `persondevice`
$$$
CREATE TABLE `device` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `personId` int(11) NOT NULL,
 `deviceUuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 `sequence` tinyint(4) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `personId` (`personId`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$


-- $rev 6

-- indicate if list should include the people themselves
ALTER TABLE `list` ADD `recipientmode` enum ('self','guardian','selfAndGuardian') NOT NULL DEFAULT 'selfAndGuardian'
$$$

-- restrict targeted recipients based on guardiancategory relation to list people. if no entries, include all categories.
CREATE TABLE `listguardiancategory` (
  `listId` int(11) NOT NULL,
  `guardianCategoryId` int(11) NOT NULL,
  PRIMARY KEY (`listId`,`guardianCategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 7
-- optional upgrade on test servers (should have been applied to production in 11.0/8) for reportcontact.recipientpersonid

-- $rev 8

-- for device types, reportdevice is used instead of reportcontact
CREATE TABLE `reportdevice` (
  `jobId` int(11) NOT NULL,
  `personId` int(11) NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `deviceUuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numAttempts` tinyint(4) NOT NULL,
  `startTimeMs` bigint(20) DEFAULT NULL,
  `result` enum('sent','unsent') NOT NULL,
  PRIMARY KEY (`jobId`,`personId`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- record each attempt, rather than cramming into reportcontact.attemptdata
CREATE TABLE `reportdeviceattempt` (
  `jobId` int(11) NOT NULL,
  `personId` int(11) NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `attempt` tinyint(4) NOT NULL,
  `startTimeMs` bigint(20) NOT NULL,
  `result` enum('sent','unsent') NOT NULL,
  `notificationReceiptId` bigint(20) NOT NULL,
  PRIMARY KEY (`jobId`,`personId`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `reportperson` CHANGE `type` `type` ENUM('phone','email','print','sms','device') NOT NULL
$$$

-- $rev 9

ALTER TABLE `reportdeviceattempt` CHANGE `notificationReceiptId` `notificationId` BIGINT(20) NULL DEFAULT NULL
$$$

ALTER TABLE `reportdevice` ADD `recipientPersonId` INT NULL DEFAULT NULL AFTER `sequence`
$$$

-- default null for insert stmt used with insert update on duplicate key
ALTER TABLE `reportdevice` CHANGE `deviceUuid` `deviceUuid` VARCHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL DEFAULT NULL
$$$

-- $rev 10

ALTER TABLE `reportdeviceattempt`
  DROP PRIMARY KEY,
   ADD PRIMARY KEY(
     `jobId`,
     `personId`,
     `sequence`,
     `attempt`)
$$$

-- $rev 11

INSERT IGNORE INTO `setting` SET `name` = '_customerid', `value` = '_$CUSTOMERID_'+0
$$$

-- $rev 12

-- these tables are part of a new feature, so they should be empty.
ALTER TABLE `reportdevice` MODIFY `result` ENUM('sent','unsent','notattempted','duplicate','blocked','nocontacts','declined') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

-- this table may be large, but adding new enum values to the end of the list
-- is only a metadata change, and does not take a long time regardless of the table size.
ALTER TABLE `reportcontact` MODIFY `result` ENUM('C','A','M','N','B','X','F','sent','unsent','printed','notprinted','notattempted','duplicate','blocked','nocontacts','declined') NOT NULL DEFAULT 'notattempted'
$$$
