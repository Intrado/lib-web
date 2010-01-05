
-- $rev 1

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

ALTER TABLE `blockeddestination` ADD `failattempts` TINYINT( 4 ) NULL
$$$

ALTER TABLE `blockeddestination` ADD UNIQUE `typedestination` ( `type` , `destination` )
$$$

ALTER TABLE `blockeddestination` ADD `blockmethod` ENUM( 'manual', 'pending', 'autoblock' ) NOT NULL
$$$

ALTER TABLE `blockeddestination` ADD INDEX `methoddate` ( `blockmethod` , `createdate` )
$$$


-- $rev 2


ALTER TABLE `message` ADD `messagegroupid` INT( 11 ) NOT NULL AFTER `id` 
$$$

ALTER TABLE `audiofile` ADD `messagegroupid` INT( 11 ) DEFAULT NULL AFTER `id` 
$$$

ALTER TABLE `job` ADD `messagegroupid` INT( 11 ) NOT NULL AFTER `id` 
$$$

CREATE TABLE `event` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`userid` INT NOT NULL ,
	`organizationid` INT NOT NULL ,
	`sectionid` INT NULL ,
	`targetedmessageid` INT NULL ,
	`name` VARCHAR( 50 ) NOT NULL ,
	`notes` TEXT NOT NULL ,
	`occurence` DATETIME NOT NULL
) ENGINE = InnoDB
$$$

 CREATE TABLE `alert` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`eventid` INT NOT NULL ,
	`personid` INT NOT NULL ,
	`date` DATE NOT NULL ,
	`time` TIME NOT NULL
) ENGINE = InnoDB
$$$

CREATE TABLE `targetedmessage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messagekey` varchar(255) NOT NULL,
  `targetedmessagecategoryid` int(11) NOT NULL,
  `overridemessagegroupid` int(11) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB
$$$

CREATE TABLE `personassociation` (
  `personid` int(11) NOT NULL,
  `type` enum('organization','section','event') NOT NULL,
  `organizationid` int(11) DEFAULT NULL,
  `sectionid` int(11) DEFAULT NULL,
  `eventid` int(11) DEFAULT NULL,
  KEY `personid` (`personid`)
) ENGINE=InnoDB
$$$

CREATE TABLE `messagegroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL,
  `modified` datetime NOT NULL,
  `lastused` datetime DEFAULT NULL,
  `permanent` tinyint NOT NULL DEFAULT 1,
  `deleted` tinyint NOT NULL DEFAULT 0, 
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 3

ALTER TABLE `message` ADD `autotranslate` ENUM( 'none', 'translated', 'source', 'overridden' ) NOT NULL DEFAULT 'none'
$$$

ALTER TABLE `message` ADD `subtype` VARCHAR( 20 ) NOT NULL AFTER `type`
$$$

CREATE TABLE `organization` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`orgkey` VARCHAR( 255 ) NOT NULL ,
	`deleted` TINYINT NOT NULL DEFAULT 0,
	UNIQUE orgkey (orgkey)
) ENGINE = InnoDB
$$$

RENAME TABLE `userrule`  TO `userassociation`
$$$

ALTER TABLE `userassociation` ADD `type` ENUM( 'rule', 'organization', 'section' ) NOT NULL DEFAULT 'rule' AFTER `userid` ,
	ADD `organizationid` INT NULL AFTER `type` ,
	ADD `sectionid` INT NULL AFTER `organizationid`
$$$

ALTER TABLE `userassociation` CHANGE `ruleid` `ruleid` INT( 11 ) NULL
$$$

ALTER TABLE `listentry` CHANGE `type` `typeold` ENUM( 'R', 'A', 'N' ) NOT NULL DEFAULT 'A'
$$$
 
ALTER TABLE `listentry` add  `type` ENUM( 'rule', 'add', 'negate', 'organization', 'section' ) NOT NULL DEFAULT 'add' after `typeold`
$$$

update listentry set type = case typeold when 'A' then 'add' when 'R' then 'rule' when 'N' then 'negate' end
$$$

-- messages can belong to surveys too
ALTER TABLE `message` CHANGE `messagegroupid` `messagegroupid` INT( 11 ) NULL  
$$$

-- fix zeros
update message set messagegroupid = null where messagegroupid=0
$$$

ALTER TABLE `job` CHANGE messagegroupid messagegroupid int(11) NULL
$$$

update job set messagegroupid=null where messagegroupid=0
$$$

ALTER TABLE `job` ADD INDEX ( `messagegroupid` )
$$$

ALTER TABLE `message` ADD INDEX ( `messagegroupid` )
$$$

-- language stuff

ALTER TABLE `ttsvoice` ADD `languagecode` VARCHAR( 3 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL AFTER `language`
$$$

update ttsvoice set languagecode = case language 
	when 'english' then 'en'
	when 'spanish' then 'es' 
	when 'catalan' then 'ca' 
	when 'chinese' then 'zh' 
	when 'dutch' then 'nl' 
	when 'finnish' then 'fi' 
	when 'french' then 'fr' 
	when 'german' then 'de' 
	when 'greek' then 'el' 
	when 'italian' then 'it' 
	when 'polish' then 'pl' 
	when 'portuguese' then 'pt' 
	when 'russian' then 'ru' 
	when 'swedish' then 'sv' 
end
$$$

ALTER TABLE `language` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
$$$

ALTER TABLE `language` ADD `code` VARCHAR( 3 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default ''
$$$

ALTER TABLE `language` ADD INDEX ( `code` ) ;
$$$

update language set code = case name
	when 'Afrikaans' then 'af'
	when 'Albanian' then 'sq'
	when 'Amharic' then 'am'
	when 'Arabic' then 'ar'
	when 'Bangali' then 'bn'
	when 'Bengali' then 'bn'
	when 'Bosnian' then 'bs'
	when 'Bulgarian' then 'bg'
	when 'Burmese' then 'my'
	when 'Cambodian' then 'km'
	when 'Cantonese' then 'caz'
	when 'Catalan' then 'ca'
	when 'Chinese' then 'zh'
	when 'Chinese (Cantonese)' then 'yue'
	when 'Creole' then '???'
	when 'Creole English' then 'en'
	when 'Croatian' then 'hr'
	when 'Czech' then 'cs'
	when 'Danish' then 'da'
	when 'Darsi-Farsi' then 'prs'
	when 'Dutch' then 'nl'
	when 'English' then 'en'
	when 'Ethiopian' then '???'
	when 'Farsi' then 'pes'
	when 'Filipino' then 'fil'
	when 'Finnish' then 'fi'
	when 'Fomali' then '???'
	when 'French' then 'fr'
	when 'Fulani' then 'fuc'
	when 'Gari-Farsi' then 'pes'
	when 'German' then 'de'
	when 'Greek' then 'el'
	when 'Gujarati' then 'gu'
	when 'Haitian' then 'ht'
	when 'Haitian Creole' then 'ht'
	when 'Haitian/Creole' then 'ht'
	when 'Hatian-Creole' then 'ht'
	when 'Hausa' then 'ha'
	when 'Hebrew' then 'iw'
	when 'Hindi' then 'hi'
	when 'Hmong' then 'hmn'
	when 'Indonesian' then 'in'
	when 'Italian' then 'it'
	when 'Japanese' then 'ja'
	when 'Khmer' then 'km'
	when 'Korean' then 'ko'
	when 'Kurdish' then 'ku'
	when 'Laotian' then 'lo'
	when 'Latvian' then 'lv'
	when 'Lithuanian' then 'lt'
	when 'Malinke' then '???'
	when 'Mandar' then 'mdr'
	when 'Mandarin' then 'cmn'
	when 'Mandarin Puton' then 'cmn'
	when 'Mandinka' then 'mnk'
	when 'Marshallese' then 'mh'
	when 'Navajo' then 'nv'
	when 'Nigerian' then '???'
	when 'Norwegian' then 'no'
	when 'Oromo' then 'om'
	when 'Other Chinese' then 'zh'
	when 'Other Liberian' then '???'
	when 'Pakistani/Indian' then '???'
	when 'Pashto' then 'ps'
	when 'Philipino' then 'fil'
	when 'Philipino (Tagalog)' then 'tl'
	when 'Pilipino' then 'fil'
	when 'Polish' then 'pl'
	when 'Portugese' then 'pt'
	when 'Portuguese' then 'pt'
	when 'Punjabi' then 'pa'
	when 'Punjabi ' then 'pa'
	when 'Romanian' then 'ro'
	when 'Russian' then 'ru'
	when 'Samoan' then 'sm'
	when 'Serbian' then 'sr'
	when 'Sign Language' then 'sgn'
	when 'Slovak' then 'sk'
	when 'Slovenian' then 'sl'
	when 'Somali' then 'so'
	when 'Somalian' then 'so'
	when 'Spanish' then 'es'
	when 'Swedish' then 'sv'
	when 'Tagalog' then 'tl'
	when 'Tagalog/Filipino' then 'tl'
	when 'Thai' then 'th'
	when 'Tibetan' then 'bo'
	when 'Tigrigna' then 'ti'
	when 'Turkish' then 'tr'
	when 'Twi' then 'tw'
	when 'Ukrainian' then 'uk'
	when 'Ukranian' then 'uk'
	when 'Urdu' then 'ur'
	when 'Vietnamese' then 'vi'
	when 'Wolof' then 'wo'
end
$$$


ALTER TABLE message ADD `languagecode` VARCHAR( 3 ) CHARACTER SET ascii COLLATE ascii_general_ci null
$$$

-- temp, used to find original language associations for clones messages
alter table message add originalid int(11) null
$$$

