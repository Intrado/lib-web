-- $rev 1

-- note this is a short lived table and will be replaced in near future after more infocenter schema is worked out
CREATE TABLE `ic_portalperson` (
 `portaluserid` int(11) NOT NULL,
 `personid` int(11) NOT NULL,
 PRIMARY KEY (`portaluserid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
