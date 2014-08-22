CREATE TABLE `agent` (
 `id` int(11) NOT NULL auto_increment,
 `uuid` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
 `name` varchar(50) NOT NULL,
 `numpollthread` smallint(6) NOT NULL,
 PRIMARY KEY  (`id`),
 UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `customeragent` (
 `customerid` int(11) NOT NULL,
 `agentid` int(11) NOT NULL,
 `options` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
 PRIMARY KEY  (`customerid`,`agentid`)
) ENGINE=InnoDB;

-- ASP_9-0-4 Aug 22, 2012 options to hold json with ldap attribute variables
ALTER TABLE  `agent` ADD  `options` TEXT NULL;

-- update all agents the defaults for active directory
update agent set options = '{"usernameAttributeName":"userPrincipalName","accountAttributeName":"userAccountControl","useFQDN":true,"enabledOperation":"bitcompare","ou":null}';


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
  VALUES ('disk', '11.0/1', (UNIX_TIMESTAMP() * 1000), 'none');

-- ------------------------------------------------------
-- NO MORE BELOW HERE!!! use upgrade_databases
