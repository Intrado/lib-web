-- Upgrade from release 6.2 to 6.3


-- Add aditional import field types
ALTER TABLE `importfield` CHANGE `action` `action` ENUM( 'copy', 'staticvalue', 'number', 'currency', 'date', 'lookup', 'curdate', 
	'numeric', 'currencyleadingzero' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'copy'
$$$


ALTER TABLE `custdm` ADD `poststatus` TEXT NOT NULL default ''
$$$


CREATE TABLE `subscriber` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`username` VARCHAR( 255 ) NOT NULL ,
`password` VARCHAR( 50 ) NOT NULL ,
`personid` INT NULL ,
`lastlogin` DATETIME NULL ,
`enabled` TINYINT NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin
$$$

ALTER TABLE `persondatavalues` ADD `editlock` TINYINT NOT NULL DEFAULT '0'
$$$

ALTER TABLE `subscriber` ADD `preferences` TEXT NOT NULL DEFAULT ''
$$$

update fieldmap set options = 'searchable,text,firstname,subscribe,dynamic' where options like '%firstname%'
$$$

update fieldmap set options = 'searchable,text,lastname,subscribe,dynamic' where options like '%lastname%'
$$$

