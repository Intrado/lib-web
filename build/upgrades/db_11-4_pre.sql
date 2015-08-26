-- $rev 1

ALTER TABLE content
  ADD COLUMN width INT NULL,
  ADD COLUMN height INT NULL,
  ADD COLUMN originalContentId BIGINT NULL
$$$

-- $rev 2

ALTER TABLE `messageattachment`
  ADD COLUMN `url` text,
  ADD COLUMN `displayName` varchar(255)
$$$

RENAME TABLE `reportsdddelivery` TO `reportdocumentdelivery`
$$$

ALTER TABLE `reportdocumentdelivery`
  DROP KEY `customerjobperson`,
  ADD PRIMARY KEY (`customerId`,`jobId`,`personId`,`messageAttachmentId`,`action`),
  MODIFY COLUMN `action` ENUM('click','download','bad_password')
$$$

-- $rev 3

ALTER TABLE `usersetting`
  MODIFY COLUMN `value` TEXT NOT NULL
$$$

UPDATE `usersetting` SET `value` = CONCAT('[', `value`, ']') WHERE `name` = 'tw_access_token';
$$$

-- $rev 4

-- no schema changed just insert _cmaapptype legacy if _cmaappid exists

-- $rev 5

CREATE TABLE reportphoneoptout (
  jobId INT NOT NULL,
  personId INT NOT NULL,
  sequence TINYINT NOT NULL,
  lastUpdateMs BIGINT NOT NULL,
  numRequests INT DEFAULT 1 NOT NULL,
  PRIMARY KEY (jobId, personId, sequence),
  KEY (lastUpdateMs)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 6

ALTER TABLE reportphoneoptout
  DROP COLUMN sequence,
  DROP COLUMN numRequests,
  ADD COLUMN phone VARCHAR(20) NOT NULL DEFAULT '' AFTER personId,
  ADD INDEX (phone),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (jobId, personId, phone)
$$$
