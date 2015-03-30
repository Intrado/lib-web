-- $rev 1

select 1
$$$

-- $rev 2

-- JobPeopleProcessor needs to know the recipients for each target in the lists of the job. this info lies in the JobProcessor
alter table qjobperson
add recipientpersonid int,
drop primary key,
add primary key (customerid, jobid, personid, recipientpersonid)
$$$

-- $rev 3

CREATE TABLE `devicejobtask` (
  `uuid` bigint(20) unsigned NOT NULL,
  `customerid` int(11) NOT NULL DEFAULT '0',
  `jobid` int(11) NOT NULL DEFAULT '0',
  `personid` int(11) NOT NULL DEFAULT '0',
  `sequence` tinyint(4) NOT NULL DEFAULT '0',
  `contactsequence` tinyint(4) NOT NULL DEFAULT '0',
  `status` enum('active','assigned','progress','waiting') NOT NULL,
  `attempts` tinyint(4) NOT NULL DEFAULT '0',
  `renderedmessageid` bigint(20) DEFAULT NULL,
  `lastresult` enum('sent','unsent','fail','tempfail','cancelling','endoflife') DEFAULT 'unsent',
  `lastattempttime` bigint(20) DEFAULT NULL,
  `nextattempttime` bigint(20) DEFAULT NULL,
  `leasetime` bigint(20) DEFAULT NULL,
  `deviceUuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `notificationReceiptId` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `id` (`customerid`,`jobid`,`personid`,`sequence`),
  KEY `progresshandler` (`status`,`lastattempttime`),
  KEY `waiting` (`status`,`nextattempttime`),
  KEY `jobstats` (`customerid`,`jobid`,`attempts`,`sequence`),
  KEY `dispatch` (`customerid`,`status`,`nextattempttime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
 
CREATE TABLE `deviceleasetask` (
  `taskUuid` bigint(20) NOT NULL,
  `leaseTimeMs` bigint(20) NOT NULL,
  PRIMARY KEY (`taskUuid`),
  KEY `leasetime` (`leaseTimeMs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
  
CREATE TABLE `devicerenderedmessage` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `renderedMessage` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 4

ALTER TABLE `devicejobtask` CHANGE `notificationReceiptId` `notificationId` BIGINT(20) NULL DEFAULT NULL
$$$

-- $rev 5

ALTER TABLE `qjobtask` CHANGE `contactsequence` `contactsequence` SMALLINT NOT NULL DEFAULT '0';
$$$

ALTER TABLE `emailjobtask` CHANGE `contactsequence` `contactsequence` SMALLINT NOT NULL DEFAULT '0';
$$$

ALTER TABLE `smsjobtask` CHANGE `contactsequence` `contactsequence` SMALLINT NOT NULL DEFAULT '0';
$$$

ALTER TABLE `devicejobtask` CHANGE `contactsequence` `contactsequence` SMALLINT NOT NULL DEFAULT '0';
$$$

