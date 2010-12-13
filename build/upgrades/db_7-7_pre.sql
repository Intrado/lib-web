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
