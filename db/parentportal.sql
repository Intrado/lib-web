
-- moved from parentuser.sql

CREATE TABLE `parentuser` (
  `id` int(11) NOT NULL auto_increment,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `customerid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;



-- moved from personparent.sql

CREATE TABLE `personparent` (
  `personid` int(11) NOT NULL,
  `parentuserid` int(11) NOT NULL,
  UNIQUE KEY `personid` (`personid`,`parentuserid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
