
-- --------------------------------------------------------
--
-- Table structure for table `defaultrates`
--

CREATE TABLE IF NOT EXISTS `defaultrates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `rate` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `lerg6`
--

CREATE TABLE IF NOT EXISTS `lerg6` (
  `lata_switch` varchar(5) NOT NULL,
  `lataname` varchar(20) NOT NULL,
  `status` varchar(1) NOT NULL,
  `effdate` varchar(10) NOT NULL,
  `npa` varchar(3) NOT NULL,
  `nxx` varchar(3) NOT NULL,
  `blockid` varchar(1) NOT NULL,
  `coctype` varchar(3) NOT NULL,
  `ssc` varchar(4) NOT NULL,
  `dind` varchar(1) NOT NULL,
  `eo` varchar(2) NOT NULL,
  `at` varchar(2) NOT NULL,
  `portable` varchar(1) NOT NULL,
  `aocn` varchar(4) NOT NULL,
  `ocn` varchar(4) NOT NULL,
  `localityname` varchar(10) NOT NULL,
  `localitycounty` varchar(2) NOT NULL,
  `localitystate` varchar(2) NOT NULL,
  `rcname` varchar(10) NOT NULL,
  `rctype` varchar(1) NOT NULL,
  `linesfrom` varchar(4) NOT NULL,
  `linesto` varchar(4) NOT NULL,
  `switch` varchar(11) NOT NULL,
  `switchhaindicator` varchar(2) NOT NULL,
  `testlinenumber` varchar(4) NOT NULL,
  `responseam` varchar(1) NOT NULL,
  `expirationdate` varchar(10) NOT NULL,
  `thousandsblockindicator` varchar(1) NOT NULL,
  `lata_ratecenter` varchar(5) NOT NULL,
  `creationdatebirrds` varchar(10) NOT NULL,
  `estatusdate` varchar(10) NOT NULL,
  `lastmodificationdate` varchar(10) NOT NULL,
  KEY `npanxxblockid` (`npa`,`nxx`,`blockid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `bandwidthrates`
--

CREATE TABLE `bandwidthrates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lata` int(11) NOT NULL,
  `city` varchar(25) NOT NULL,
  `state` varchar(25) NOT NULL,
  `interstaterate` double DEFAULT NULL,
  `intrastaterate` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `qwestclasslookup`
--

CREATE TABLE IF NOT EXISTS `qwestclasslookup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ocn` char(4) NOT NULL,
  `class` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ocn` (`ocn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `qwestrates`
--

CREATE TABLE IF NOT EXISTS `qwestrates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lata` smallint(6) NOT NULL,
  `state` varchar(15) NOT NULL,
  `class` tinyint(4) NOT NULL,
  `interstaterate` double NOT NULL,
  `intrastaterate` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `xorates`
--

CREATE TABLE IF NOT EXISTS `xorates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `npanxx` char(6) NOT NULL,
  `category` varchar(10) NOT NULL,
  `lata` smallint(6) NOT NULL,
  `ocn` varchar(4) NOT NULL,
  `state` char(2) NOT NULL,
  `interstaterate` double NOT NULL,
  `intrastaterate` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `npanxx` (`npanxx`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- old Qwest rate data tables
DROP TABLE `qwestrates`;
DROP TABLE `qwestclasslookup`;

-- raw Qwest data (from pdf export)
CREATE TABLE `qwestrawinterstaterate` (
  `state` varchar(50) NOT NULL,
  `lata` smallint(6) NOT NULL,
  `class1` float NOT NULL,
  `class2` float NOT NULL,
  `class3` float NOT NULL,
  `class4` float NOT NULL,
  `class5` float NOT NULL,
  `class6` float NOT NULL,
  PRIMARY KEY (`state`,`lata`),
  KEY `lata` (`lata`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `qwestrawintrastaterate` (
  `state` varchar(50) NOT NULL,
  `class1` float NOT NULL,
  `class2` float NOT NULL,
  `class3` float NOT NULL,
  `class4` float NOT NULL,
  `class5` float NOT NULL,
  `class6` float NOT NULL,
  PRIMARY KEY (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `qwestclasslookup` (
  `ocn` varchar(4) NOT NULL,
  `class` tinyint(4) NOT NULL,
  PRIMARY KEY (`ocn`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Qwest rate view for homoginzing all the lata, class, inter/intra state data
CREATE VIEW qwestrateview AS 
select inter.state, inter.lata, 1 as class, inter.class1 as interstaterate, intra.class1 as intrastaterate from qwestrawinterstaterate inter left join qwestrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 2 as class, inter.class2 as interstaterate, intra.class2 as intrastaterate from qwestrawinterstaterate inter left join qwestrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 3 as class, inter.class3 as interstaterate, intra.class3 as intrastaterate from qwestrawinterstaterate inter left join qwestrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 4 as class, inter.class4 as interstaterate, intra.class4 as intrastaterate from qwestrawinterstaterate inter left join qwestrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 5 as class, inter.class5 as interstaterate, intra.class5 as intrastaterate from qwestrawinterstaterate inter left join qwestrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 6 as class, inter.class6 as interstaterate, intra.class6 as intrastaterate from qwestrawinterstaterate inter left join qwestrawintrastaterate intra on inter.state = intra.state
order by lata;

-- make rate tables use int instead of small int for lata field
ALTER TABLE `qwestrawinterstaterate` CHANGE `lata` `lata` INT NOT NULL ;
ALTER TABLE `xorates` CHANGE `lata` `lata` INT NOT NULL ;

--
-- Table structure for table `level3rates`
--

CREATE TABLE `level3rates` (
  `lata` int(11) NOT NULL,
  `ocn` varchar(4) NOT NULL,
  `jurisdiction` enum('interstate','intrastate') NOT NULL,
  `rate` float NOT NULL,
  PRIMARY KEY (`lata`,`ocn`,`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- add an index on localitystate, need this to look up lata/ocn values for intrastate rate with level3 and bandwidth
ALTER TABLE `lerg6` ADD INDEX ( `localitystate` );

-- no need for id field, makes sourcing new rate data harder
ALTER TABLE `xorates` DROP `id`;

--
-- Table structure for table `bandwidthrates`
--

DROP TABLE `bandwidthrates`;
CREATE TABLE `bandwidthrates` (
  `state` varchar(2) NOT NULL,
  `interstaterate` double DEFAULT NULL,
  `intrastaterate` double DEFAULT NULL,
  KEY `state` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `qwesttdmrawinterstaterate`
--

CREATE TABLE `qwesttdmrawinterstaterate` (
  `state` varchar(50) NOT NULL,
  `lata` int(11) NOT NULL,
  `class1` float NOT NULL,
  `class2` float NOT NULL,
  `class3` float NOT NULL,
  `class4` float NOT NULL,
  `class5` float NOT NULL,
  `class6` float NOT NULL,
  PRIMARY KEY (`state`,`lata`),
  KEY `lata` (`lata`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `qwesttdmrawintrastaterate`
--

CREATE TABLE `qwesttdmrawintrastaterate` (
  `state` varchar(50) NOT NULL,
  `class1` float NOT NULL,
  `class2` float NOT NULL,
  `class3` float NOT NULL,
  `class4` float NOT NULL,
  `class5` float NOT NULL,
  `class6` float NOT NULL,
  PRIMARY KEY (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Qwest rate view for homoginzing all the lata, class, inter/intra state data
CREATE VIEW qwesttdmrateview AS 
select inter.state, inter.lata, 1 as class, inter.class1 as interstaterate, intra.class1 as intrastaterate from qwesttdmrawinterstaterate inter left join qwesttdmrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 2 as class, inter.class2 as interstaterate, intra.class2 as intrastaterate from qwesttdmrawinterstaterate inter left join qwesttdmrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 3 as class, inter.class3 as interstaterate, intra.class3 as intrastaterate from qwesttdmrawinterstaterate inter left join qwesttdmrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 4 as class, inter.class4 as interstaterate, intra.class4 as intrastaterate from qwesttdmrawinterstaterate inter left join qwesttdmrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 5 as class, inter.class5 as interstaterate, intra.class5 as intrastaterate from qwesttdmrawinterstaterate inter left join qwesttdmrawintrastaterate intra on inter.state = intra.state
union
select inter.state, inter.lata, 6 as class, inter.class6 as interstaterate, intra.class6 as intrastaterate from qwesttdmrawinterstaterate inter left join qwesttdmrawintrastaterate intra on inter.state = intra.state
order by lata;

--
-- Table structure for table `xohvod`
--

CREATE TABLE IF NOT EXISTS `xohvod` (
  `npanxx` char(6) NOT NULL,
  `rate` double NOT NULL,
  PRIMARY KEY (`npanxx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `centurylinktall`
-- Currently there is no difference between TDM and VoIP, nor inter and intra-state rates
--

CREATE TABLE IF NOT EXISTS `centurylinkall` (
  `lata` int(11) NOT NULL,
  `ocn` varchar(4) NOT NULL,
  `rate` double NOT NULL,
  PRIMARY KEY (`lata`,`ocn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- create views for the other variations, table can be replaced if the rates diverge
create view centurylinktdminterstaterate as select * from centurylinkall;
create view centurylinktdmintrastaterate as select * from centurylinkall;
create view centurylinkvoipinterstaterate as select * from centurylinkall;
create view centurylinkvoipintrastaterate as select * from centurylinkall;

-- ------------------------------------------------------
-- add table for database versioning used by upgrade_databases.php

CREATE TABLE `dbupgrade` (
 `id` varchar(20) NOT NULL,
 `version` varchar(20) NOT NULL,
 `lastUpdateMs` bigint(20) NOT NULL,
 `status` varchar(20) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `dbupgrade` 
  VALUES ('lcrrates', '11.0/1', (UNIX_TIMESTAMP() * 1000), 'none');

-- ------------------------------------------------------
-- NO MORE BELOW HERE!!! use upgrade_databases
