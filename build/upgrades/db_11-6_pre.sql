-- $rev 1

ALTER TABLE `reportphoneoptout`
ADD COLUMN `optOutCode` SMALLINT UNSIGNED,
ADD COLUMN `sequence` TINYINT,
ADD COLUMN `jobTypeId` INT
$$$
