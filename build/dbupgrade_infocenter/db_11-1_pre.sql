-- $rev 1

CREATE TABLE `userdevice` (
  `userId` int(11) NOT NULL,
  `deviceUuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`userId`,`deviceUuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

