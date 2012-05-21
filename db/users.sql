
-- using PHP style variables to note where you need to change a value
-- $some_random_password should be a randomly generated password, different for each user


-- pagelink user for finding codes. used by appserver
CREATE USER 'pagelink_ro'@'%' IDENTIFIED BY '$some_random_password';
GRANT SELECT ON `pagelink`.`pagelink` TO 'pagelink_ro'@'%';

-- pagelink user for creating links. used by redialer
CREATE USER 'pagelink_rw'@'%' IDENTIFIED BY '$some_random_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON `pagelink`.`pagelink` TO 'pagelink_rw'@'%';

-- props manager needs a manager class account (authserver)

CREATE USER 'propsmanager'@'10.80.0.136' IDENTIFIED BY '***';
GRANT USAGE ON * . * TO 'propsmanager'@'10.80.0.136' IDENTIFIED BY '' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'propsmanager'@'10.80.0.136';

CREATE USER 'propsmanager'@'10.80.0.137' IDENTIFIED BY '***';
GRANT USAGE ON * . * TO 'propsmanager'@'10.80.0.137' IDENTIFIED BY '' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'propsmanager'@'10.80.0.137';


-- authserver users need more access

-- FIXME shard1?

GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard2auth'@'10.80.0.58';

GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard3auth'@'10.80.0.61';
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard3auth'@'10.80.0.62';
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard3auth'@'10.80.0.63';


GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard4auth'@'10.80.0.66';
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard4auth'@'10.80.0.67';
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard4auth'@'10.80.0.68';

GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard5auth'@'10.80.0.71';
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard5auth'@'10.80.0.72';
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard5auth'@'10.80.0.73';

GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard6auth'@'10.80.0.76';
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard6auth'@'10.80.0.77';
GRANT SELECT , INSERT , UPDATE , DELETE ON `authserver` . * TO 'shard6auth'@'10.80.0.78';


-- appserver needs to read authserver

GRANT SELECT ON `authserver` . * TO 'appsErv3r'@'%';

