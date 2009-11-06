-- Upgrade from release 7.1.x to 7.5

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

delete from setting where name = 'smsoptintext'
$$$

insert into setting (name, value) select 'smscustomername', value from setting where name = 'displayname' on duplicate key update name='smscustomername'
$$$

