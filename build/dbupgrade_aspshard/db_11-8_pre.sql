-- $rev 1
-- no-op

-- $rev 2
CREATE TABLE `cloudContentSynchronization` (
    `cloudStorageProviderName` varchar(50) NOT NULL,
    `customerId` int NOT NULL,
    `lastCheckedContentId` bigint NOT NULL,
    `scanInitiatedTimeMs` bigint NOT NULL,
    PRIMARY KEY (`cloudStorageProviderName`,`customerId`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 3
CREATE TABLE emailevent (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestampMs` bigint(20) NOT NULL,
  `type` enum('message','track','generation','unsubscribe','relay') not null,
  `subType` enum('bounce','delivery','injection','sms_status','spam_complaint','out_of_band','policy_rejection','delay','click','open','failure','rejection','link','list','tempFail','permFail') not null,
  `jobId` int(11) DEFAULT NULL,
  `personId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `sequence` smallint(6) DEFAULT NULL,
  `source` enum('customer_job','contact_manager','password_reset','report_subscription','subscriber_expiration','cm_password_reset','job_monitor','internal_monitoring') DEFAULT NULL,
  `messageId` varchar(64) DEFAULT NULL,
  `vendorEventId` varchar(32) DEFAULT NULL,
  `fromName` varchar(100) NOT NULL,
  `fromDomain` varchar(100) NOT NULL,
  `toName` varchar(100) NOT NULL,
  `toDomain` varchar(100) NOT NULL,
  `statusCode` smallint(4) unsigned NOT NULL,
  `responseText` text NOT NULL,
  `rawResponseText` text NOT NULL,
  `numRetries` int DEFAULT NULL,
  `queueTime` int DEFAULT NULL,
  `vendorBounceCode` varchar(64) DEFAULT NULL,
  `feedBackType` enum('abuse','fraud','other','virus') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp_statusCode` (`timeStampMs`,`statusCode`),
  KEY `statusCode_timestamp` (`statusCode`,`timeStampMs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 4

CREATE TABLE smslanguage (
  code VARCHAR(3) PRIMARY KEY,
  name VARCHAR(100) NOT NULL
)
$$$

-- $rev 5
ALTER TABLE smsrenderedmessage MODIFY renderedmessage TEXT CHARSET utf8mb4
$$$
