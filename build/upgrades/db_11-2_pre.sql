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

-- $rev 2

ALTER TABLE reportsdddelivery DEFAULT CHARSET=utf8,
  MODIFY COLUMN action ENUM('SEND', 'CLICK', 'DOWNLOAD', 'BAD_PASSWORD') NOT NULL
$$$

-- $rev 3

UPDATE burst SET status = 'sent' WHERE jobid IS NOT NULL
$$$

-- $rev 4

ALTER TABLE burstattachment
  ADD KEY (burstid)
$$$

ALTER TABLE messageattachment
  ADD KEY (burstattachmentid)
$$$

ALTER TABLE reportsdddelivery
  ADD KEY rsdd_ma_ts (messageAttachmentId, timestampMs),
  ADD KEY rsdd_ma_act_ts (messageAttachmentId, action, timestampMs)
$$$

-- $rev 5

ALTER TABLE reportsdddelivery
  MODIFY COLUMN action ENUM('CLICK', 'DOWNLOAD', 'BAD_PASSWORD') NOT NULL
$$$

-- $rev 6

update jobsetting j1 join jobsetting j2 on j1.jobid=j2.jobid
set j1.value = '0'
where j1.name = 'skipduplicates' and j2.name = 'skipemailduplicates'
and (j1.value = '0' or j2.value = '0')
$$$

delete from jobsetting where name in ('skipemailduplicates', 'skipsmsduplicates')
$$$

-- $rev 7

-- unused or redundant indexes per CS-7289
-- and one new index per CS-7302

ALTER TABLE access
  DROP INDEX `id`
$$$

ALTER TABLE cmafeedcategory
  DROP INDEX `feedcategoryid`,
  ADD INDEX (cmacategoryid, feedcategoryid);
$$$

ALTER TABLE job
  DROP INDEX `enddate`,
  DROP INDEX `endtime`,
  DROP INDEX `starttime`,
  DROP INDEX `startdate`
$$$

ALTER TABLE reportemaildelivery
  DROP INDEX `time`
$$$

ALTER TABLE reportsubscription
  DROP INDEX `modifydate`,
  DROP INDEX `nextrun`
$$$

-- $rev 8

ALTER TABLE user
  ADD COLUMN `sms` varchar(20) NOT NULL DEFAULT '' AFTER `aremail`
$$$
