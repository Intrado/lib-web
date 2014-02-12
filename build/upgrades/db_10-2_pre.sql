-- $rev 1

CREATE TABLE `feedcategorytype` (
 `feedcategoryid` int(11) NOT NULL,
 `type` enum('rss','desktop','push') NOT NULL,
 PRIMARY KEY (`feedcategoryid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$


