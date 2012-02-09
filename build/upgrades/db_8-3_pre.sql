-- $rev 1

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

-- $rev 2

$$$

ALTER TABLE `personguardian` CHANGE `importid` `importid` INT( 11 ) NULL 
$$$

-- $rev 3

-- no symbols in naming
ALTER TABLE `person` CHANGE `type` `type` ENUM( 'system', 'addressbook', 'manualadd', 'upload', 'subscriber',  'guardianauto',  'guardiancm') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'system'
$$$

-- need more chars for descriptive names
ALTER TABLE `template` CHANGE `type` `type` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

-- rename
update template set type = 'subscriber-accountexpire' where type = 'subscriber'
$$$

