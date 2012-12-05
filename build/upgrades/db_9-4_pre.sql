-- $rev 1

UPDATE `messagegroup` mg inner join message m on (m.messagegroupid = mg.id) inner join messagepart mp on (mp.messageid = m.id) SET mp.txt=REPLACE(mp.txt, '\\n', '\n') WHERE mg.type='systemtemplate' and mg.name='messagelink Template' and m.type='sms' and m.languagecode='en' and mp.txt like '%\\n%'
$$$

-- $rev 2

-- change default from 0 to null
ALTER TABLE  `setting` CHANGE  `organizationid`  `organizationid` INT( 11 ) NULL DEFAULT NULL
$$$

-- fix
UPDATE `setting` set organizationid = null where organizationid = 0
$$$

