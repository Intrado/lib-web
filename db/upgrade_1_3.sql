ALTER TABLE `job` ADD `cancelleduserid` INT;

ALTER TABLE `customer` ADD `logocontentid` BIGINT;

ALTER TABLE `blockednumber` CHANGE `description` `description` VARCHAR( 100 ) NOT NULL;

