-- $rev 1
-- no-op

-- $rev 2
CREATE TABLE `cloudContentSynchronization` (
    `cloudStorageProviderName` varchar(50) NOT NULL,
    `customerId` int NOT NULL,
    `lastCheckedContentId` bigint NOT NULL,
    `scanInitiatedTimeMs` bigint NOT NULL,
    PRIMARY KEY (`cloudStorageProviderName`,`customerId`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
$$$

