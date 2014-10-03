-- $rev 1

select 1
$$$

-- $rev 2
--
-- Table structure for table `bandwidthratedrates`
--

CREATE TABLE IF NOT EXISTS `bandwidthratedrates` (
  `npanxx` char(6) NOT NULL,
  `lata` int(11) NOT NULL,
  `interstaterate` double DEFAULT NULL,
  `intrastaterate` double DEFAULT NULL,
  PRIMARY KEY (`npanxx`,`lata`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

INSERT INTO `defaultrates` (`name`, `rate`) VALUES ('bandwidthrated', 0.07)
$$$

