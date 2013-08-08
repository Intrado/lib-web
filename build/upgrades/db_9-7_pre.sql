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

-- $rev 2

ALTER TABLE `reportemailtracking` CHANGE `requestduration` `requestduration` BIGINT NOT NULL
$$$

-- $rev 3
-- Force customers to the revolution 9 theme (code in this update also removes theme selection from the UI)

delete from usersetting where name in ("_brandtheme", "_brandprimary", "_brandratio", "_brandtheme1", "_brandtheme2")
$$$

delete from setting where name in ("_brandtheme", "_brandprimary", "_brandratio", "_brandtheme1", "_brandtheme2")
$$$

-- $rev 4
-- Add indexes for reporting email read duration
ALTER TABLE  `reportemaildelivery` ADD INDEX  `jobperson` (  `jobid` ,  `personid`, `sequence` )
$$$

-- $rev 5
-- Updates existing email templates to include an email tracking pixel
update messagepart mp set mp.txt = concat(mp.txt, '${trackingpixelimg}') where mp.messageid in
	(select id from message where type = 'email' and subtype = 'html'
		and messagegroupid in (select messagegroupid from template where type in ('emergency', 'notification')))
$$$

-- Updates existing customers to have the deprecated message sender enabled
insert into setting (organizationid, name, value) values (null, '_allowoldmessagesender', 1)
$$$

-- Updates existing users who are not deleted to use the deprecated message sender
insert into usersetting (userid, name, value) (select id, '_allowoldmessagesender' as name, 1 as value from user where not deleted)
$$$