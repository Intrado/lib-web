-- $rev 1

UPDATE `messagegroup` mg inner join message m on (m.messagegroupid = mg.id) inner join messagepart mp on (mp.messageid = m.id) SET mp.txt=REPLACE(mp.txt, '\\n', '\n') WHERE mg.type='systemtemplate' and mg.name='messagelink Template' and m.type='sms' and m.languagecode='en' and mp.txt like '%\\n%'
$$$
