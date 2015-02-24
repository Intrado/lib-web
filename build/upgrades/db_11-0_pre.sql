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

-- $rev 2
ALTER  TABLE  `importfield`  CHANGE  `mapto`  `mapto` VARCHAR( 50 )  CHARACTER  SET utf8 COLLATE utf8_general_ci NOT  NULL DEFAULT  ''
$$$

-- $rev 3

-- Remove settings which allow access to the deprecated message sender
delete from setting where name = "_allowoldmessagesender"
$$$

delete from usersetting where name = "_allowoldmessagesender"
$$$

-- $rev 4
-- copy enrollment data from personassociation.sectionid
insert into enrollment (personid, sectionid, importid, importstatus) select personid, sectionid, importid, 'new' from personassociation where sectionid is not null
$$$

-- $rev 5
-- dummy rev to insert new customer setting '_hasicra'

-- $rev 6
-- dummy rev to insert organization settings '_hasquicktip'

-- $rev 7
CREATE OR REPLACE SQL SECURITY DEFINER VIEW `aspsmsblock` AS select `s`.`sms` AS `sms`,`sb`.`status` AS `status`,`s`.`personid` AS `personid`,`sb`.`lastupdate` AS `lastupdate`,`sb`.`notes` AS `notes`, `s`.`editlock` AS `editlock` from (`c_1`.`sms` `s` join `aspshard`.`smsblock` `sb` on((`sb`.`sms` = convert(`s`.`sms` using latin1))))
$$$
