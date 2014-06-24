-- $rev 1

-- note this is a short lived table and will be replaced in near future after more infocenter schema is worked out
CREATE TABLE `ic_portalperson` (
 `portaluserid` int(11) NOT NULL,
 `personid` int(11) NOT NULL,
 PRIMARY KEY (`portaluserid`,`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 2

-- deprecate fields that move to new useraccess table. no change to user imports they still use these deprecated fields
ALTER TABLE `user`
CHANGE `accessid` `accessid` INT( 11 ) NULL DEFAULT NULL COMMENT 'deprecated, see useraccess.accessid', 
CHANGE `personid` `personid` INT( 11 ) NULL DEFAULT NULL COMMENT 'deprecated, see useraccess.personid'
$$$

-- fix foobar accessid value
UPDATE `user` set accessid = null where accessid = 0
$$$

-- imports create with accessid=NULL and GUI to manage setting access
ALTER TABLE `guardiancategory` ADD `accessid` INT NULL
$$$
 
-- support for various types of access profile
ALTER TABLE `access` ADD `type` ENUM( 'cs', 'guardian', 'identity' ) NOT NULL DEFAULT 'cs'
$$$
 
-- create special access profile for user identities
insert into `access` (`name`, `description`, `type`) values ('User Identity', 'Links a user to their contact information', 'identity')
$$$
insert into `permission` (`accessid`, `name`, `value`) values (LAST_INSERT_ID(), 'fullaccess', '1')
$$$
 
-- user access to object type
CREATE TABLE `useraccess` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `accessid` int(11) NOT NULL,
  `type` enum('organization','section','person') NOT NULL DEFAULT 'person',
  `personid` int(11) DEFAULT NULL,
  `organizationid` int(11) DEFAULT NULL COMMENT 'future use',
  `sectionid` int(11) DEFAULT NULL COMMENT 'future use',
  `importid` int(11) DEFAULT NULL COMMENT 'future use',
  `importstatus` enum('none','checking','new') NOT NULL DEFAULT 'none' COMMENT 'future use',
  PRIMARY KEY (`id`),
  INDEX  `userid` (`userid` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$
 
-- $rev 3

DROP TABLE `ic_portalperson`
$$$

-- $rev 4

ALTER  TABLE  `ttsvoice`  ADD  `name` VARCHAR( 50  )  NOT  NULL ,
  ADD  `enabled` TINYINT NOT  NULL DEFAULT  '0'
$$$

-- Loquendo voices enabled for old customers
UPDATE `ttsvoice` SET `name` = 'Susan', `enabled` = '1' 
  WHERE `language` = 'english' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Dave', `enabled` = '1' 
  WHERE `language` = 'english' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Esperanza', `enabled` = '1' 
  WHERE `language` = 'spanish' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Carlos', `enabled` = '1' 
  WHERE `language` = 'spanish' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Montserrat', `enabled` = '1' 
  WHERE `language` = 'catalan' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Jordi', `enabled` = '1' 
  WHERE `language` = 'catalan' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Lisheng', `enabled` = '1' 
  WHERE `language` = 'chinese' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Saskia', `enabled` = '1' 
  WHERE `language` = 'dutch' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Willem', `enabled` = '1' 
  WHERE `language` = 'dutch' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Milla', `enabled` = '1' 
  WHERE `language` = 'finnish' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Florence', `enabled` = '1' 
  WHERE `language` = 'french' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Bernard', `enabled` = '1' 
  WHERE `language` = 'french' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Katrin', `enabled` = '1' 
  WHERE `language` = 'german' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Stefan', `enabled` = '1' 
  WHERE `language` = 'german' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Afroditi', `enabled` = '1' 
  WHERE `language` = 'greek' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Paola', `enabled` = '1' 
  WHERE `language` = 'italian' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Matteo', `enabled` = '1' 
  WHERE `language` = 'italian' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Zosia', `enabled` = '1' 
  WHERE `language` = 'polish' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Krzysztof', `enabled` = '1' 
  WHERE `language` = 'polish' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Amalia', `enabled` = '1' 
  WHERE `language` = 'portuguese' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Eusebio', `enabled` = '1' 
  WHERE `language` = 'portuguese' and `gender` = 'male'
$$$

UPDATE `ttsvoice` SET `name` = 'Olga', `enabled` = '1' 
  WHERE `language` = 'russian' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Annika', `enabled` = '1' 
  WHERE `language` = 'swedish' and `gender` = 'female'
$$$

UPDATE `ttsvoice` SET `name` = 'Sven', `enabled` = '1' 
  WHERE `language` = 'swedish' and `gender` = 'male'
$$$

-- NeoSpeech voices enabled for new customers only

INSERT INTO `ttsvoice` (`language`, `languagecode`, `gender`, `name`, `enabled`) VALUES 
  ('english', 'en', 'female', 'Julie', 0),
  ('english', 'en', 'male', 'James', 0),
  ('turkish', 'tr', 'male', 'Hasari', 0)
$$$

-- $rev 5

-- upgrade customer provider is loquendo, manual switch to neospeech using asp manager
insert into setting (name, value) values ('_defaultttsprovider', 'loquendo')
$$$

-- this voice is temporary until we get the list of language-gender-name for neospeech
update ttsvoice set enabled = '1' where name = 'Hasari'
$$$

-- $rev 6

ALTER TABLE `ttsvoice` ADD `provider` ENUM( 'loquendo', 'neospeech' ) NOT NULL DEFAULT 'loquendo'
$$$

update ttsvoice set provider = 'neospeech' where name in ('James','Julie','Hasari')
$$$

-- $rev 7

INSERT INTO `ttsvoice` (`language`, `languagecode`, `gender`, `name`, `enabled`, `provider`) VALUES
  ('spanish', 'es', 'female', 'Violeta', 0, 'neospeech'),
  ('korean', 'ko', 'male', 'Junwoo', 1, 'neospeech'),
  ('korean', 'ko', 'female', 'Yumi', 1, 'neospeech'),
  ('japanese', 'ja', 'male', 'Show', 1, 'neospeech'),
  ('japanese', 'ja', 'female', 'Misaki', 1, 'neospeech'),
  ('chinese', 'zh', 'male', 'Liang', 1, 'neospeech'),
  ('chinese', 'zh', 'female', 'Hui', 0, 'neospeech')
$$$

delete from ttsvoice where name = 'Hasari'
$$$

-- $rev 8
-- No sql changes

-- $rev 9
-- Update neospeech english female voice
update ttsvoice set name = 'Ashley' where name = 'Julie'
$$$

-- Add neospeech spanish male voice
insert into ttsvoice (language, languagecode, gender, name, enabled, provider) select
'spanish' as language, 'es' as languagecode, 'male' as gender, 'Francisco' as name, enabled, 'neospeech' as provider
from ttsvoice where name = 'Violeta'
$$$

-- $rev 10
-- set Carlos to the same enable state as Esperanza
update ttsvoice t1 inner join (select enabled from ttsvoice where name = 'Esperanza' limit 1) t2
set t1.enabled = t2.enabled where name = 'Carlos'
$$$