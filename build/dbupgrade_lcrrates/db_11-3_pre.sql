-- $rev 1

CREATE TABLE `carrierratemodel` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `classname` VARCHAR(100) NOT NULL,
  `params` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

INSERT INTO `carrierratemodel` (`id`, `name`, `classname`, `params`) VALUES
(1, 'Qwest CA','Qwest', '{"state":"ca"}'),
(2, 'Qwest VA','Qwest', '{"state":"va"}'),
(3, 'Bandwidth CA', 'Bandwidth', '{"state":"ca"}'),
(4, 'Bandwidth VA', 'Bandwidth', '{"state":"Va"}'),
(5, 'Xo CA','Xo', '{"state":"ca"}'),
(6, 'Xo VA','Xo', '{"state":"va"}'),
(7, 'Level3 IL','Level3', '{"state":"il"}'),
(8, 'Xo IL', 'Xo', '{"state":"il"}'),
(9, 'Qwest TDM CA','Qwest', '{"state":"ca","isTdm":true}'),
(10, 'Qwest TDM VA','Qwest', '{"state":"va","isTdm":true}'),
(11, 'Qwest TDM IL','Qwest', '{"state":"il","isTdm":true}'),
(13, 'Simple CA','simple', '{"state":"ca"}'),
(14, 'Level3 CA','level3', '{"state":"ca"}'),
(15, 'XoHvod IL','XoHvod', '{"state":"il"}'),
(16, 'XoHvod CA','XoHvod', '{"state":"ca"}'),
(17, 'XoHvod VA','XoHvod', '{"state":"va"}'),
(18, 'CenturyLink TDM IL','CenturyLink', '{"state":"il","type":"tdm"}'),
(19, 'CenturyLink TDM CA','CenturyLink', '{"state":"il","type":"tdm"}'),
(20, 'CenturyLink TDM VA','CenturyLink', '{"state":"il","type":"tdm"}'),
(21, 'CenturyLink Voip CA','CenturyLink', '{"state":"il","type":"voip"}'),
(22, 'CenturyLink Voip VA','CenturyLink', '{"state":"il","type":"voip"}'),
(23, 'HyperCube IL','HyperCube', '{"state":"il"}'),
(24, 'Bandwidth Rated CA','BandwidthRated', '{"state":"ca"}'),
(25, 'Bandwidth Rated VA','BandwidthRated', '{"state":"va"}')
$$$

CREATE TABLE IF NOT EXISTS `carrierratemodelblock` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `carrierRateModelClassname` VARCHAR(100) CHARACTER SET utf8 NOT NULL,
  `pattern` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `createdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`carrierRateModelClassname`, `pattern`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*
* join authserver.dmgroupblock.dmgroupid to dmgroup_map.oldId, to get the newId
* join dmgroup_map.newId to dmgroup.id, to get the carrierRateModelClassname
* then DISTINCT this, or else use INSERT IGNORE to populate carrierratemodelblock
*/

INSERT INTO `carrierratemodelblock` (`carrierRateModelClassname`, `pattern`)
SELECT DISTINCT g.`carrierRateModelClassname`, b.`pattern`
FROM `authserver`.`dmgroupblock` b
INNER JOIN `authserver`.`dmgroup_map` m ON (m.`oldId` = b.`id`)
INNER JOIN `authserver`.`dmgroup` g ON (m.`newId` = g.`id`)
$$$
