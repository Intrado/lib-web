
-- Database: `pagelink`

-- 8.0 page code

CREATE TABLE `pagelink` (
	`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`customerid` INT NOT NULL ,
	`jobid` INT NOT NULL ,
	`code` CHAR( 6 ) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
	UNIQUE (`code`)
) ENGINE = InnoDB;


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
  VALUES ('pagelink', '11.0/1', (UNIX_TIMESTAMP() * 1000), 'none');

-- ------------------------------------------------------
-- NO MORE BELOW HERE!!! use upgrade_databases
