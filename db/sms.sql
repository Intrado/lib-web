

CREATE TABLE `smsjob` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `listid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL,
  `txt` varchar(160) NOT NULL,
  `sendoptout` tinyint(4) NOT NULL,
  `sentdate` datetime NOT NULL,
  `status` enum('new','queued','sent','error') NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;



CREATE TABLE `smsmsg` (
  `id` int(11) NOT NULL auto_increment,
  `smsjobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `phone` varchar(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


ALTER TABLE `phone` ADD `smsenabled` TINYINT NOT NULL DEFAULT '0';

ALTER TABLE `blockednumber` ADD `type` ENUM( 'call', 'sms', 'both' ) NOT NULL DEFAULT 'both';


ALTER TABLE `smsjob` ADD `deleted` TINYINT NOT NULL DEFAULT '0';