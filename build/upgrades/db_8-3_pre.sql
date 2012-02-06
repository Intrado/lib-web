-- $rev1

CREATE TABLE IF NOT EXISTS `personguardian` (
  `personid` int(11) NOT NULL,
  `guardianpersonid` int(11) NOT NULL,
  `type` enum('primary','other') NOT NULL,
  `importid` int(11) NOT NULL,
  `importstatus` enum('none','checking','new') DEFAULT NULL,
  PRIMARY KEY (`personid`,`guardianpersonid`),
  INDEX guardianpersonid ( `guardianpersonid` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `person` CHANGE `type` `type` ENUM( 'system', 'addressbook', 'manualadd', 'upload', 'subscriber',  'guardian_auto',  'guardian_cm') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'system'
$$$

update person set type = 'subscriber' where type = 'system' and importid is null
$$$

