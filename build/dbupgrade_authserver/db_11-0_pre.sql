-- $rev 1

select 1
$$$

-- $rev 2

-- A general table to hold settings for portalauth, one of which will be 'trustedDomains'
CREATE TABLE `authserver`.`portalsetting` (
	`name` VARCHAR(50) NOT NULL PRIMARY KEY,
	`value` VARCHAR(1024) NOT NULL
)
$$$
 
-- A place to optionally link a redirectUrl for this token (nullable!)
ALTER TABLE `authserver`.`portalactivation` ADD COLUMN `redirecturl` varchar(255);
$$$

