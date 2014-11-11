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
  ADD UNIQUE KEY `appInstance` (`name`,`version`,`cmaAppId`)
$$$

