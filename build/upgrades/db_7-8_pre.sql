-- $rev 1

ALTER TABLE `user` ADD `salt` VARCHAR( 29 ) NOT NULL AFTER `password` ,
ADD `passwordversion` TINYINT NOT NULL AFTER `salt` 
$$$

-- TODO set passwordversion based on length of pass

ALTER TABLE `subscriber` ADD `salt` VARCHAR( 29 ) NOT NULL AFTER `password` ,
ADD `passwordversion` TINYINT NOT NULL AFTER `salt` 
$$$
