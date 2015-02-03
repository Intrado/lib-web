-- $rev 1

CREATE TABLE `persondevice` (
  `personid` int(11) NOT NULL,
  `deviceUuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  PRIMARY KEY (`personid`,`deviceUuid`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
 
ALTER TABLE `contactpref` CHANGE `type` `type` ENUM('phone','email','print','sms','device') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

-- $rev 2

-- no schema just update _hasinfocenter settings

-- $rev 3

-- no schema, disable all _hasinfocenter (keeping _hasicplus)
-- manual process by support to enable infocenter and guardian data for our customers

-- $rev 4

-- rename guardian profile permission
update permission set name = 'icplus' where name = 'infocenter'
$$$

-- $rev 5

-- add support for guardian rule item
-- indicate if list should include the people themselves
ALTER TABLE `list` ADD `recipientmode` enum ('self','guardian','selfAndGuardian') NOT NULL DEFAULT 'selfAndGuardian'
$$$

-- restrict targeted recipients based on guardiancategory relation to list people. if no entries, include all categories.
CREATE TABLE `listguardiancategory` (
  `listId` int(11) NOT NULL,
  `guardianCategoryId` int(11) NOT NULL,
  PRIMARY KEY (`listId`,`guardianCategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- store the recipient for lookup to display name on reports (instead of sequence12)
-- lookup the recipientperson name from the person table (no need to keep history of recipient name)
ALTER TABLE `reportcontact` ADD `recipientpersonid` INT NOT NULL  AFTER `sequence`
$$$