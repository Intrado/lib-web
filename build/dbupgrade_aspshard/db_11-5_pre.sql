-- $rev 1

ALTER TABLE `smsjobtask` MODIFY COLUMN `lastresult` ENUM('sent', 'unsent', 'fail', 'tempfail', 'cancelling', 'endoflife', 'queued') DEFAULT 'unsent'
$$$

ALTER TABLE `qjobtask` ADD COLUMN `recipientpersonid` INT NULL AFTER `sequence`, ADD COLUMN `originaluuid` BIGINT UNSIGNED NULL AFTER `uuid`
$$$

ALTER TABLE `smsjobtask` ADD COLUMN `recipientpersonid` INT NULL AFTER `sequence`, ADD COLUMN `originaluuid` BIGINT UNSIGNED NULL AFTER `uuid`
$$$

ALTER TABLE `emailjobtask` ADD COLUMN `recipientpersonid` INT NULL AFTER `sequence`, ADD COLUMN `originaluuid` BIGINT UNSIGNED NULL AFTER `uuid`
$$$

ALTER TABLE `devicejobtask` ADD COLUMN `recipientpersonid` INT NULL AFTER `sequence`, ADD COLUMN `originaluuid` BIGINT UNSIGNED NULL AFTER `uuid`
$$$
