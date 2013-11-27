-- $rev 1
ALTER TABLE `alert` ADD INDEX ( `date` )
$$$
-- $rev 2
ALTER TABLE `event` ADD INDEX ( `userid` )
$$$
-- $rev 3
CREATE TABLE IF NOT EXISTS `burst` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `contentid` bigint(20),
  `name` varchar(50) NOT NULL default '',
  `status` enum('new','mapped','sent') NOT NULL default 'new',
  `filename` varchar(255) NOT NULL default '',
  `bytes` bigint(20),
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
-- $rev 4
CREATE TABLE IF NOT EXISTS `burst_template` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `x` double(12,8),
  `y` double(12,8),
  `created` datetime,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 5
RENAME TABLE burst_template TO bursttemplate
$$$

ALTER TABLE burst
ADD COLUMN `bursttemplateid`    INT(11),
ADD COLUMN `uploaddatems`       BIGINT(20) NOT NULL,
ADD COLUMN `pagesskipstart`     INT(3)     NOT NULL DEFAULT 0,
ADD COLUMN `pagesskipend`       INT(3)     NOT NULL DEFAULT 0,
ADD COLUMN `pagesperreport`     INT(3)     NOT NULL DEFAULT 1,
ADD COLUMN `totalpagesfound`    INT(6),
ADD COLUMN `actualreportscount` INT(6)
$$$

drop INDEX `id` ON burst
$$$

drop INDEX `id` ON bursttemplate
$$$
