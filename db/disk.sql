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
update agent set options = '"{"usernameAttributeName":"userPrincipalName","accountAttributeName":"userAccountControl","useFQDN":true,"enabledOperation":"bitcompare","ou":null}';


