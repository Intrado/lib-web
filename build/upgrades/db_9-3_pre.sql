-- $rev 1

-- jobtype rename to notificationtype using 'messaging' for tai
ALTER TABLE jobtype ADD type enum ('job','survey','messaging') DEFAULT 'job'
$$$
UPDATE jobtype SET type='survey' WHERE issurvey
$$$
ALTER TABLE jobtype DROP issurvey
$$$
RENAME TABLE jobtype TO notificationtype
$$$
CREATE VIEW jobtype AS SELECT id, name, systempriority, info, deleted, type='survey' AS issurvey FROM notificationtype WHERE type IN ('job','survey')
$$$

-- tai user display preference
ALTER TABLE  `role` ADD  `userdisplayname` VARCHAR( 255 ) NULL
$$$

