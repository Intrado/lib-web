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
