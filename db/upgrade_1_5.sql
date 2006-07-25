



-- this adds the systempriority field
ALTER TABLE `jobworkitem` ADD `systempriority` TINYINT NOT NULL DEFAULT '3' AFTER `priority` ;


-- drop a now useless index, and add a nice one
ALTER TABLE `jobworkitem` DROP INDEX `priority`; 
ALTER TABLE `jobworkitem` DROP INDEX `assign` ,
ADD INDEX `assign` ( `status` , `type` , `systempriority` , `priority` ) ;


ALTER TABLE `customer`
  DROP `addr1`,
  DROP `addr2`,
  DROP `city`,
  DROP `state`,
  DROP `zip`,
  DROP `contactname`,
  DROP `contactphone`,
  DROP `contactemail`;
  
  
ALTER TABLE `customer` ADD `hostname` VARCHAR( 255 ) NOT NULL AFTER `logocontentid` ,
ADD `remotedm` VARCHAR( 255 ) NOT NULL AFTER `hostname` ;
