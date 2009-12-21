CREATE TABLE IF NOT EXISTS `messagegroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL,
  `modified` datetime NOT NULL,
  `lastused` datetime DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `permanent` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
$$$

ALTER TABLE `message` ADD `messagegroupid` INT( 11 ) NOT NULL AFTER `id` 
$$$

ALTER TABLE `audiofile` ADD `messagegroupid` INT( 11 ) DEFAULT NULL AFTER `id` 
$$$

ALTER TABLE `job` ADD `messagegroupid` INT( 11 ) NOT NULL AFTER `id` 
$$$

