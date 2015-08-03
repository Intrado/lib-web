-- $rev 1

CREATE TABLE `dmgroupjmssetting_production` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `jmsConnectionFactoryName` VARCHAR(50) NOT NULL,
  `resultQueueName` VARCHAR(50) NOT NULL,
  `statusTopicName` VARCHAR(50) NOT NULL,
  `taskQueueName` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

INSERT INTO `dmgroupjmssetting_production` (`id`, `jmsConnectionFactoryName`, `resultQueueName`, `statusTopicName`, `taskQueueName`) VALUES
  (1, 'JMSSV3', 'BandwidthCAFirstcall.result', 'BandwidthCAFirstcall.status', 'BandwidthCAFirstcall.task'),
  (2, 'JMSSV3', 'XoCAFirstcall.result', 'XoCAFirstcall.status', 'XoCAFirstcall.task'),
  (3, 'JMSDC2', 'XoVAFirstcall.result', 'XoVAFirstcall.status', 'XoVAFirstcall.task'),
  (4, 'JMSCH1', 'Level3ILFirstcall.result', 'Level3ILFirstcall.status', 'Level3ILFirstcall.task'),
  (5, 'JMSCH1', 'XoILFirstcall.result', 'XoILFirstcall.status', 'XoILFirstcall.task'),
  (6, 'JMSSV3', 'QwestTDMCALastcall.result', 'QwestTDMCALastcall.status', 'QwestTDMCALastcall.task'),
  (7, 'JMSSV3', 'SimpleCAFirstcall.result', 'SimpleCAFirstcall.status', 'SimpleCAFirstcall.task'),
  (8, 'JMSSV3', 'Level3CAFirstcall.result', 'Level3CAFirstcall.status', 'Level3CAFirstcall.task'),
  (9, 'JMSCH1', 'XoHvodILFirstcall.result', 'XoHvodILFirstcall.status', 'XoHvodILFirstcall.task'),
  (10, 'JMSSV3', 'XoHvodCAFirstcall.result', 'XoHvodCAFirstcall.status', 'XoHvodCAFirstcall.task'),
  (11, 'JMSDC2', 'XoHvodVAFirstcall.result', 'XoHvodVAFirstcall.status', 'XoHvodVAFirstcall.task'),
  (12, 'JMSCH1', 'CenturyLinkTDMILFirstcall.result', 'CenturyLinkTDMILFirstcall.status', 'CenturyLinkTDMILFirstcall.task'),
  (13, 'JMSCH1', 'CenturyLinkTDMILLastcall.result', 'CenturyLinkTDMILLastcall.status', 'CenturyLinkTDMILLastcall.task'),
  (14, 'JMSSV3', 'CenturyLinkTDMCAFirstcall.result', 'CenturyLinkTDMCAFirstcall.status', 'CenturyLinkTDMCAFirstcall.task'),
  (15, 'JMSSV3', 'CenturyLinkTDMCALastcall.result', 'CenturyLinkTDMCALastcall.status', 'CenturyLinkTDMCALastcall.task'),
  (16, 'JMSDC2', 'CenturyLinkTDMVAFirstcall.result', 'CenturyLinkTDMVAFirstcall.status', 'CenturyLinkTDMVAFirstcall.task'),
  (17, 'JMSDC2', 'CenturyLinkTDMVALastcall.result', 'CenturyLinkTDMVALastcall.status', 'CenturyLinkTDMVALastcall.task'),
  (18, 'JMSSV3', 'CenturyLinkVoipCAFirstcall.result', 'CenturyLinkVoipCAFirstcall.status', 'CenturyLinkVoipCAFirstcall.task'),
  (19, 'JMSDC2', 'CenturyLinkVoipVAFirstcall.result', 'CenturyLinkVoipVAFirstcall.status', 'CenturyLinkVoipVAFirstcall.task'),
  (20, 'JMSCH1', 'HyperCubeILFirstcall.result', 'HyperCubeILFirstcall.status', 'HyperCubeILFirstcall.task'),
  (21, 'JMSSV3', 'BandwidthRatedCAFirstcall.result', 'BandwidthRatedCAFirstcall.status', 'BandwidthRatedCAFirstcall.task'),
  (22, 'JMSDC2', 'BandwidthRatedVAFirstcall.result', 'BandwidthRatedVAFirstcall.status', 'BandwidthRatedVAFirstcall.task')
$$$

CREATE TABLE `dmgroupjmssetting` LIKE `dmgroupjmssetting_production`
$$$
INSERT INTO `dmgroupjmssetting` (`id`, `jmsConnectionFactoryName`, `resultQueueName`, `statusTopicName`, `taskQueueName`)
SELECT `id`, 'JMSDEFAULT', `resultQueueName`, `statusTopicName`, `taskQueueName`
FROM `dmgroupjmssetting_production`
$$$

-- TODO only in production, where server_id is nonzero
-- RENAME TABLE dmgroupjmssetting TO dmgroupjmssetting_test, dmgroupjmssetting_production TO dmgroupjmssetting

CREATE TABLE `dmgroupjmsprofile` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `dispatcherJmsSettingId` INT NOT NULL,
  `dmJmsSettingId` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

INSERT INTO `dmgroupjmsprofile` (`id`, `name`, `dispatcherJmsSettingId`, `dmJmsSettingId`) VALUES
  (1, 'Bandwidth CA Firstcall', 1, 1),
  (2, 'Xo CA Firstcall', 2, 2),
  (3, 'Xo VA Firstcall', 3, 3),
  (4, 'Level3 IL Firstcall', 4, 4),
  (5, 'Xo IL Firstcall', 5, 5),
  (6, 'Qwest TDM CA Lastcall', 6, 6),
  (7, 'Simple CA Firstcall', 7, 7),
  (8, 'Level3 CA Firstcall', 8, 8),
  (9, 'XoHvod IL Firstcall', 9, 9),
  (10, 'XoHvod CA Firstcall', 10, 10),
  (11, 'XoHvod VA Firstcall', 11, 11),
  (12, 'CenturyLink TDM IL Firstcall', 12, 12),
  (13, 'CenturyLink TDM IL Lastcall', 13, 13),
  (14, 'CenturyLink TDM CA Firstcall', 14, 14),
  (15, 'CenturyLink TDM CA Lastcall', 15, 15),
  (16, 'CenturyLink TDM VA Firstcall', 16, 16),
  (17, 'CenturyLink TDM VA Lastcall', 17, 17),
  (18, 'CenturyLink Voip CA Firstcall', 18, 18),
  (19, 'CenturyLink Voip VA Firstcall', 19, 19),
  (20, 'HyperCube IL Firstcall', 20, 20),
  (21, 'Bandwidth Rated CA Firstcall', 21, 21),
  (22, 'Bandwidth Rated VA Firstcall', 22, 22)
$$$

CREATE TABLE `dmgroup_new` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `carrierRateModelId` INT NOT NULL,
  `carrierRateModelClassname` VARCHAR(100) NOT NULL,
  `carrierRateModelParams` TEXT NOT NULL,
  `dmGroupJmsProfileId` INT NULL,
  `dispatchType` ENUM('system','customer') NOT NULL DEFAULT 'system',
  `routeType` ENUM('firstcall','lastcall','othercall') NOT NULL DEFAULT 'firstcall',
  `notes` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

INSERT INTO `dmgroup_new` (`id`, `name`, `carrierRateModelId`, `carrierRateModelClassname`, `carrierRateModelParams`, `routeType`) VALUES
  (1,'DmAPI Bandwidth CA Firstcall',3,'Bandwidth','{"state":"ca"}','firstcall'),
  (2,'DmAPI Xo CA Firstcall',5,'Xo','{"state":"ca"}','firstcall'),
  (3,'DmAPI Xo VA Firstcall',6,'Xo','{"state":"va"}','firstcall'),
  (4,'DmAPI Level3 IL Firstcall',7,'Level3','{"state":"il"}','firstcall'),
  (5,'DmAPI Xo IL Firstcall',8,'Xo','{"state":"il"}','firstcall'),
  (6,'DmAPI Qwest TDM CA Lastcall',9,'Qwest','{"state":"ca","isTdm":true}','lastcall'),
  (7,'DmAPI Simple CA Firstcall',13,'Simple','{"state":"ca"}','firstcall'),
  (8,'DmAPI Level3 CA Firstcall',14,'Level3','{"state":"ca"}','firstcall'),
  (9,'DmAPI XoHvod IL Firstcall',15,'XoHvod','{"state":"il"}','firstcall'),
  (10,'DmAPI XoHvod CA Firstcall',16,'XoHvod','{"state":"ca"}','firstcall'),
  (11,'DmAPI XoHvod VA Firstcall',17,'XoHvod','{"state":"va"}','firstcall'),
  (12,'DmAPI CenturyLink TDM IL Firstcall',18,'CenturyLink','{"state":"il","type":"tdm"}','firstcall'),
  (13,'DmAPI CenturyLink TDM IL Lastcall',18,'CenturyLink','{"state":"il","type":"tdm"}','lastcall'),
  (14,'DmAPI CenturyLink TDM CA Firstcall',19,'CenturyLink','{"state":"ca","type":"tdm"}','firstcall'),
  (15,'DmAPI CenturyLink TDM CA Lastcall',19,'CenturyLink','{"state":"ca","type":"tdm"}','lastcall'),
  (16,'DmAPI CenturyLink TDM VA Firstcall',20,'CenturyLink','{"state":"va","type":"tdm"}','firstcall'),
  (17,'DmAPI CenturyLink TDM VA Lastcall',20,'CenturyLink','{"state":"va","type":"tdm"}','lastcall'),
  (18,'DmAPI CenturyLink Voip CA Firstcall',21,'CenturyLink','{"state":"ca","type":"voip"}','firstcall'),
  (19,'DmAPI CenturyLink Voip VA Firstcall',22,'CenturyLink','{"state":"va","type":"voip"}','firstcall'),
  (20,'DmAPI HyperCube IL Firstcall',23,'HyperCube','{"state":"il"}','firstcall'),
  (21,'DmAPI Bandwidth Rated CA Firstcall',24,'BandwidthRated','{"state":"ca"}','firstcall'),
  (22,'DmAPI Bandwidth Rated VA Firstcall',25,'BandwidthRated','{"state":"va"}','firstcall')
$$$

INSERT INTO `dmgroup_new` (`id`, `name`, `dmGroupJmsProfileId`, `carrierRateModelId`, `carrierRateModelClassname`, `carrierRateModelParams`, `routeType`) VALUES
  (23,'JMS Bandwidth CA Firstcall',1,3,'Bandwidth','{"state":"ca"}','firstcall'),
  (24,'JMS Xo CA Firstcall',2,5,'Xo','{"state":"ca"}','firstcall'),
  (25,'JMS Xo VA Firstcall',3,6,'Xo','{"state":"va"}','firstcall'),
  (26,'JMS Level3 IL Firstcall',4,7,'Level3','{"state":"il"}','firstcall'),
  (27,'JMS Xo IL Firstcall',5,8,'Xo','{"state":"il"}','firstcall'),
  (28,'JMS Qwest TDM CA Lastcall',6,9,'Qwest','{"state":"ca","isTdm":true}','lastcall'),
  (29,'JMS Simple CA Firstcall',7,13,'Simple','{"state":"ca"}','firstcall'),
  (30,'JMS Level3 CA Firstcall',8,14,'Level3','{"state":"ca"}','firstcall'),
  (31,'JMS XoHvod IL Firstcall',9,15,'XoHvod','{"state":"il"}','firstcall'),
  (32,'JMS XoHvod CA Firstcall',10,16,'XoHvod','{"state":"ca"}','firstcall'),
  (33,'JMS XoHvod VA Firstcall',11,17,'XoHvod','{"state":"va"}','firstcall'),
  (34,'JMS CenturyLink TDM IL Firstcall',12,18,'CenturyLink','{"state":"il","type":"tdm"}','firstcall'),
  (35,'JMS CenturyLink TDM IL Lastcall',13,18,'CenturyLink','{"state":"il","type":"tdm"}','lastcall'),
  (36,'JMS CenturyLink TDM CA Firstcall',14,19,'CenturyLink','{"state":"ca","type":"tdm"}','firstcall'),
  (37,'JMS CenturyLink TDM CA Lastcall',15,19,'CenturyLink','{"state":"ca","type":"tdm"}','lastcall'),
  (38,'JMS CenturyLink TDM VA Firstcall',16,20,'CenturyLink','{"state":"va","type":"tdm"}','firstcall'),
  (39,'JMS CenturyLink TDM VA Lastcall',17,20,'CenturyLink','{"state":"va","type":"tdm"}','lastcall'),
  (40,'JMS CenturyLink Voip CA Firstcall',18,21,'CenturyLink','{"state":"ca","type":"voip"}','firstcall'),
  (41,'JMS CenturyLink Voip VA Firstcall',19,22,'CenturyLink','{"state":"va","type":"voip"}','firstcall'),
  (42,'JMS HyperCube IL Firstcall',20,23,'HyperCube','{"state":"il"}','firstcall'),
  (43,'JMS Bandwidth Rated CA Firstcall',21,24,'BandwidthRated','{"state":"ca"}','firstcall'),
  (44,'JMS Bandwidth Rated VA Firstcall',22,25,'BandwidthRated','{"state":"va"}','firstcall')
$$$

CREATE TABLE `dmgroup_map` (
  `newId` INT NOT NULL PRIMARY KEY,
  `routeType` ENUM('firstcall','lastcall','othercall') NOT NULL DEFAULT 'firstcall',
  `oldId` INT NOT NULl
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

INSERT INTO `dmgroup_map` (`oldId`, `routeType`, `newId`) VALUES
  (3,'firstcall',1),
  (5,'firstcall',2),
  (6,'firstcall',3),
  (7,'firstcall',4),
  (8,'firstcall',5),
  (9,'lastcall',6),
  (13,'firstcall',7),
  (14,'firstcall',8),
  (15,'firstcall',9),
  (16,'firstcall',10),
  (17,'firstcall',11),
  (18,'firstcall',12),
  (18,'lastcall',13),
  (19,'firstcall',14),
  (19,'lastcall',15),
  (20,'firstcall',16),
  (20,'lastcall',17),
  (21,'firstcall',18),
  (22,'firstcall',19),
  (23,'firstcall',20),
  (24,'firstcall',21),
  (25,'firstcall',22)
$$$

RENAME TABLE
  `dmgroup` TO `dmgroup_old`,
  `dmgroup_new` TO `dmgroup`
$$$

-- $rev 2

UPDATE `dm` JOIN `dmgroup_map` ON `dm`.`dmgroupid` = `dmgroup_map`.`oldId`
  SET `dm`.`dmgroupid` = `dmgroup_map`.`newId`
$$$
