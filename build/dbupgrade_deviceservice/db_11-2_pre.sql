-- $rev 1

-- no-op
do 1
$$$

-- $rev 2

CREATE TABLE app (
  id int(11) NOT NULL AUTO_INCREMENT,
  productName varchar(50) NOT NULL,
  PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE appinstance_new (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(50) NOT NULL,
  appId int(11) NULL,
  appCredentialId int(11) NULL,
  osType enum('android','ios') NULL,
  PRIMARY KEY (id),
  UNIQUE KEY `appIdOsType` (`appId`, `osType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE appversion (
  appInstanceId int(11) NOT NULL,
  appVersion varchar(20) NOT NULL,
  createdTimestampMs bigint NOT NULL,
  status enum('supported', 'deprecated','notsupported') NOT NULL default 'supported',
  statusJson text NULL,
  PRIMARY KEY (appInstanceId, appVersion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE appcredential (
  id int(11) NOT NULL AUTO_INCREMENT,
  protocol enum('APNS','GCM') NOT NULL,
  isProduction tinyint(1) NOT NULL DEFAULT '0',
  appleCert BLOB NULL,
  applePassPhrase varchar(100) CHARACTER SET ascii NULL,
  googleApiKey varchar(100) CHARACTER SET ascii NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- add new column to device to associate with the right version
ALTER TABLE `device` ADD COLUMN appVersion VARCHAR(20) AFTER appInstanceId
$$$

-- $rev 3

-- clean up after the data has been migrated.
-- do not drop the appinstance_old table yet.
RENAME TABLE
  appinstance TO appinstance_old,
  appinstance_new TO appinstance
$$$

-- $rev 4

-- add new column to registrationlog to associate with the right version
ALTER TABLE `registrationlog` ADD COLUMN appVersion VARCHAR(20) AFTER appInstanceId
$$$

UPDATE registrationlog AS r
JOIN appinstance_old AS aold ON (aold.id = r.appInstanceId)
JOIN appinstance AS anew ON (anew.name = aold.name AND anew.osType = r.osType)
JOIN appversion AS av ON (av.appInstanceId = anew.id AND av.appVersion = aold.version)
JOIN appcredential AS ac ON (ac.id = anew.appCredentialId AND ac.isProduction = aold.isProduction)
SET r.appInstanceId = anew.id,
    r.appVersion = av.appVersion
$$$
