-- $rev 1

CREATE TABLE `device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `token` varchar(204) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `osVersion` varchar(20) NOT NULL,
  `osType` enum('ios','android') NOT NULL,
  `appInstanceId` int(11) NOT NULL,
  `enabled` tinyint(4) NOT NULL,
  `registrationTimestampMs` bigint(20) NOT NULL,
  `lastSeenTimestampMs` bigint(20) NOT NULL,
  `modifiedTimestampMs` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
 
CREATE TABLE `appinstance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `version` varchar(20) NOT NULL,
  `cmaAppId` int(11) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `registrationlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `token` varchar(204) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `osVersion` varchar(20) NOT NULL,
  `osType` enum('ios','android') NOT NULL,
  `appInstanceId` int(11) NOT NULL,
  `timestampMs` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY (`uuid`),
  KEY (`timestampMs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 2

ALTER TABLE `appinstance`
  ADD INDEX `appInstance` (`name`,`version`,`cmaAppId`)
$$$

-- $rev 3

CREATE TABLE `notification` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `deviceId` int(11) NOT NULL,
  `message` varchar(4096),
  `owner` varchar(50),
  `requestData` mediumtext NOT NULL,
  `responseData` mediumtext NOT NULL,
  `createdTimestampMs` bigint,
  `updatedTimestampMs` bigint,
  `responseReceivedTimestampMs` bigint,
  `status` ENUM('ACCEPTED','SENDING','CONGESTION','EXPIRED_TOKEN','EXPIRED_AUTH', 'TEMPORARY_ERROR', 'INTERNAL_ERROR', 'REJECTED', 'SENT', 'RECEIVED', 'UNSENT') CHARACTER SET utf8 COLLATE utf8_general_ci,
  `attempts` tinyint,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$


-- $rev 4
-- add apple and google key columns
ALTER TABLE `appinstance` 
	ADD COLUMN `appleCert` BLOB NOT NULL,
	ADD COLUMN `applePassPhrase` varchar(100) NOT NULL,
	ADD COLUMN `googleApiKey` varchar(100) NOT NULL
$$$

-- $rev 5

ALTER TABLE `notification` ADD `responseHeaders` TEXT NULL DEFAULT NULL AFTER `responseData`
$$$

-- $rev 6

ALTER TABLE `device` CHANGE `token` `token` VARCHAR(204) CHARACTER SET ascii COLLATE ascii_bin NULL DEFAULT NULL
$$$
