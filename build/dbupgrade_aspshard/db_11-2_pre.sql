-- $rev 1

do 1
$$$

-- $rev 2

-- unused or redundant indexes per CS-7289

ALTER TABLE `devicejobtask`
  DROP INDEX `dispatch`,
  DROP INDEX `jobstats`
$$$

ALTER TABLE `qjob`
  DROP INDEX `enddate`,
  DROP INDEX `endtime`,
  DROP INDEX `startdate_2`,
  DROP INDEX `starttime`
$$$

ALTER TABLE `specialtaskqueue`
  DROP INDEX `uuid`
$$$

