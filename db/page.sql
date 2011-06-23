-- 8.0 page code

CREATE TABLE `pagelink` (
	`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`customerid` INT NOT NULL ,
	`jobid` INT NOT NULL ,
	`code` CHAR( 6 ) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
	UNIQUE (`code`)
) ENGINE = InnoDB;
