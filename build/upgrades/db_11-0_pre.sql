-- $rev 1


-- enrollment table for grades
CREATE TABLE `enrollment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `personid` int(11) NOT NULL,
  `sectionid` int(11) NOT NULL,
  `lettergrade` varchar(20) NOT NULL,
  `percentgrade` varchar(20) NOT NULL,
  `importid` int(11) NOT NULL,
  `importstatus` enum('none','checking','new') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `personid` (`personid`),
  KEY `sectionid` (`sectionid`),
  KEY `importid` (`importid`,`importstatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

