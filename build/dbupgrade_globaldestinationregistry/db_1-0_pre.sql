-- $rev 1

CREATE TABLE endpoint (
    id            INT(11) PRIMARY KEY AUTO_INCREMENT,
    destination   VARCHAR(128) NOT NULL UNIQUE,
    type          ENUM('PHONE', 'EMAIL', 'PUSH') NOT NULL,
    sub_type      ENUM('UNKNOWN', 'LANDLINE', 'MOBILE') NOT NULL DEFAULT 'UNKNOWN',
    created_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_date DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted       BOOLEAN NOT NULL DEFAULT FALSE,
    INDEX destination_UK (destination)
) ENGINE = INNODB DEFAULT CHARSET=utf8
$$$

CREATE TABLE consent (
    endpoint_id INT(11) PRIMARY KEY,
    sms         ENUM('PENDING', 'YES', 'NO') NOT NULL DEFAULT 'PENDING',
    `call`      ENUM('PENDING', 'YES', 'NO') NOT NULL DEFAULT 'PENDING',
    FOREIGN KEY endpoint_FK (endpoint_id)
    REFERENCES endpoint(id)
) ENGINE = INNODB DEFAULT CHARSET=utf8
$$$

CREATE TABLE block (
   endpoint_id INT(11) PRIMARY KEY,
   sms         BOOLEAN NOT NULL DEFAULT FALSE,
   `call`      BOOLEAN NOT NULL DEFAULT FALSE,
   FOREIGN KEY endpoint_FK (endpoint_id)
   REFERENCES endpoint(id)
) ENGINE = INNODB DEFAULT CHARSET=utf8
$$$
