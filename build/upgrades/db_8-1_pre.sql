-- $rev 1

-- add dm notes
ALTER TABLE `custdm` ADD `notes` TEXT;
$$$

-- $rev 2

-- Add categories for alerts to seperate customer and manager alerts and any future category needs
CREATE TABLE `importalertcategory` (
    `id` int(11) NOT NULL auto_increment,
    `name` varchar(50) NOT NULL,
    `emails` TEXT,
 	 PRIMARY KEY  (`id`)
) ENGINE = InnoDB;
$$$

-- New table to keep track of import alert rules instead of a json encoding string
CREATE TABLE `importalertrule` (
	`id` int(11) NOT NULL auto_increment,
	`importid` INT NOT NULL,
	`categoryid` INT NOT NULL,
	`name` varchar(50) NOT NULL,
    `operation` enum('eq','ne','gt','lt') NOT NULL,
    `testvalue` INT NOT NULL,
    `daysofweek` varchar(20) NOT NULL,
 	 PRIMARY KEY  (`id`)
) ENGINE = InnoDB;
$$$

-- Add field for netsuite integration   
ALTER TABLE `import` ADD `nsticketid` VARCHAR( 50 ) NOT NULL default '';
$$$

-- Add notes field that is not visible to the customer 
ALTER TABLE `import` ADD `managernotes` TEXT;
$$$
