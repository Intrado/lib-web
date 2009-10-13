-- Upgrade from release 7.1 to 7.5

INSERT into `joblist` (`jobid`, `listid`) SELECT `id`, `listid` from `job`
$$$

ALTER TABLE `job` DROP `listid`, DROP `thesql`
$$$

ALTER TABLE `joblist` DROP `thesql`
$$$

ALTER TABLE `custdm` CHANGE `poststatus` `poststatus` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci
$$$

ALTER TABLE `subscriber` CHANGE `preferences` `preferences` TEXT
$$$
