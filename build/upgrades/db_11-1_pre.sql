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

-- rename persondevice table to device
drop table if exists `persondevice`
$$$
CREATE TABLE `device` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `personId` int(11) NOT NULL,
 `deviceUuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 `sequence` tinyint(4) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `personId` (`personId`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;$
