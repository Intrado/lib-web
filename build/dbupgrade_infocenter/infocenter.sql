CREATE TABLE `user` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `portalUserId` INT NOT NULL
) ENGINE = InnoDB
$$$
 
CREATE TABLE `usercustomer` (
  `userId` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  PRIMARY KEY (`userId`,`customerId`)
) ENGINE=InnoDB
$$$

ALTER TABLE `user` ADD INDEX ( `portalUserId` )
$$$

-- ------------------------------------------------------
-- add table for database versioning used by upgrade_databases.php

CREATE TABLE `dbupgrade` (
 `id` varchar(20) NOT NULL,
 `version` varchar(20) NOT NULL,
 `lastUpdateMs` bigint(20) NOT NULL,
 `status` varchar(20) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `dbupgrade` 
  VALUES ('infocenter', '11.0/1', (UNIX_TIMESTAMP() * 1000), 'none');

-- ------------------------------------------------------
-- NO MORE BELOW HERE!!! use upgrade_databases
