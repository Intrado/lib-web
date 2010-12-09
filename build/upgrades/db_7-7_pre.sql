-- $rev 1

CREATE TABLE reportarchive_old LIKE reportarchive 
$$$

INSERT INTO reportarchive_old(
SELECT *
FROM reportarchive )
$$$

DROP TABLE `reportarchive`
$$$

CREATE TABLE `reportarchive` (
  `reportdate` date NOT NULL,
  `contentid` bigint(20) NOT NULL,
  INDEX `date` (`reportdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

INSERT INTO reportarchive(
SELECT concat( name, "-01" ) AS cdate, contentid
FROM reportarchive_old )
$$$

DROP TABLE `reportarchive_old`
$$$
