-- $rev 1

ALTER TABLE content
  ADD COLUMN width INT NULL,
  ADD COLUMN height INT NULL,
  ADD COLUMN originalContentId BIGINT NULL
$$$

-- $rev 2

ALTER TABLE `messageattachment`
  ADD COLUMN `displayName` varchar(255)
$$$

RENAME TABLE `reportsdddelivery` TO `reportdocumentdelivery`
$$$

ALTER TABLE `reportdocumentdelivery`
  DROP KEY `customerjobperson`,
  ADD PRIMARY KEY (`customerId`,`jobId`,`personId`,`messageAttachmentId`,`action`),
  MODIFY COLUMN `action` ENUM('click','download','bad_password')
$$$

