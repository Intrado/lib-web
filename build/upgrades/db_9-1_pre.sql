-- $rev 1

-- need more chars
ALTER TABLE  `importfield` CHANGE  `mapto`  `mapto` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''
$$$

-- all user fieldmaps begin with 'u'
update importfield set mapto = concat('u', mapto) where locate('u', mapto) = 0 and importid in (select id from import where datatype = 'user')
$$$

