-- $rev 1

CREATE TABLE IF NOT EXISTS `reportemailtracking` (
	`jobid` int(11) NOT NULL,
	`personid` int(11) NOT NULL,
	`sequence` tinyint(4) NOT NULL,
	`timestampms` bigint(20) NOT NULL,
	`numrequests` int(11) NOT NULL,
	`requestduration` text NOT NULL,
	UNIQUE KEY `jobid` (`jobid`,`personid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
$$$

