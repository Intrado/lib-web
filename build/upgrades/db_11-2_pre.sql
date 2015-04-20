-- $rev 1

-- SDD delivery events
CREATE TABLE reportsdddelivery (
  customerId INT NOT NULL,
  jobId INT NOT NULL,
  personId INT NOT NULL,
  messageAttachmentId INT NOT NULL,
  action ENUM('SENT','BAD_PASSWORD','DOWNLOAD') CHARACTER SET utf8 COLLATE utf8_general_ci,
  timestampMs BIGINT NOT NULL,
  actionCount INT NOT NULL,
  UNIQUE KEY `customerjobperson` (`customerId`,`jobId`,`personId`, `messageAttachmentId`, `action`)
)
$$$

ALTER TABLE `burst` ADD `jobId` INT NULL DEFAULT NULL AFTER `bursttemplateid`
$$$

UPDATE messageattachment ma 
JOIN burstattachment ba ON (ma.burstattachmentid = ba.id)
JOIN burst b ON (b.id = ba.burstid)
JOIN message m ON (ma.messageid = m.id)
JOIN job j ON (m.messagegroupid = j.messagegroupid)
JOIN messagegroup mg ON (m.messagegroupid = mg.id)
SET b.jobId = j.id
WHERE ma.type = 'burst'
$$$
