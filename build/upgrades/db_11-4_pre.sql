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

