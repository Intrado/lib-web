-- $rev 1

RENAME TABLE `reportarchive` TO `reportarchive_old`
$$$

CREATE TABLE `reportarchive` (
  `reportdate` date NOT NULL,
  `contentid` bigint(20) default NULL,
  INDEX `date` (`reportdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

INSERT INTO reportarchive(
SELECT concat( name, "-01" ) AS cdate, contentid
FROM reportarchive_old )
$$$

DROP TABLE `reportarchive_old`
$$$

-- $rev 2

ALTER TABLE `email` ADD INDEX `dedupe` ( `email` , `sequence` )
$$$

-- $rev 3
ALTER TABLE `messagegroup` ADD `originalmessagegroupid` INT NULL AFTER `id` 
$$$

-- $rev 4
ALTER TABLE `subscriber` ADD INDEX `lastlogin` ( `lastlogin` , `enabled` , `personid` )
$$$

-- $rev 5
ALTER TABLE `language` CHANGE `code` `code` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL 
$$$

-- $rev 6
-- empty rev to revert incorrect insert into customer settings for enabling the hassmapi setting
$$$