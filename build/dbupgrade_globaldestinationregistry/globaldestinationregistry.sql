CREATE TABLE `dbupgrade` (
 `id` varchar(64) NOT NULL,
 `version` varchar(20) NOT NULL,
 `lastUpdateMs` bigint(20) NOT NULL,
 `status` varchar(20) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `dbupgrade`
  VALUES ('globaldestinationregistry', '0.1/1', (UNIX_TIMESTAMP() * 1000), 'none');

