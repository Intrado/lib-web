-- $rev 1

CREATE TABLE endpoint (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    destination   VARCHAR(255) NOT NULL,
    type          ENUM('PHONE', 'EMAIL', 'DEVICE') NOT NULL,
    subType       ENUM('LANDLINE', 'MOBILE'),
    consentSms    ENUM('PENDING', 'YES', 'NO'),
    consentCall   ENUM('PENDING', 'YES', 'NO'),
    blockSms      TINYINT(1),
    blockCall     TINYINT(1),
    modifiedDate  BIGINT NOT NULL,
    createdDate   BIGINT NOT NULL,
    deleted       TINYINT(1) NOT NULL DEFAULT FALSE,
    revision      INT NOT NULL DEFAULT 1,
    UNIQUE INDEX (destination),
    INDEX (createdDate)
)
$$$

-- the partition key column must be part of the primary key
CREATE TABLE endpointevent (
    id            INT AUTO_INCREMENT,
    createdDate   BIGINT NOT NULL,
    destination   VARCHAR(255) NOT NULL,
    action        ENUM('REGISTER', 'DEREGISTER', 'CONSENT', 'BLOCK', 'IDENTIFY') NOT NULL,
    details       TEXT,
    PRIMARY KEY (id, createdDate),
    INDEX (destination),
    INDEX (createdDate)
) PARTITION BY RANGE (createdDate)
(
    PARTITION pNULL VALUES LESS THAN (0),
    PARTITION p20160101 VALUES LESS THAN (UNIX_TIMESTAMP('2016-02-01')*1000),
    PARTITION p20160201 VALUES LESS THAN (UNIX_TIMESTAMP('2016-03-01')*1000),
    PARTITION p20160301 VALUES LESS THAN (UNIX_TIMESTAMP('2016-04-01')*1000),
    PARTITION p20160401 VALUES LESS THAN (UNIX_TIMESTAMP('2016-05-01')*1000),
    PARTITION p20160501 VALUES LESS THAN (UNIX_TIMESTAMP('2016-06-01')*1000),
    PARTITION p20160601 VALUES LESS THAN (UNIX_TIMESTAMP('2016-07-01')*1000),
    PARTITION p20160701 VALUES LESS THAN (UNIX_TIMESTAMP('2016-08-01')*1000),
    PARTITION p20160801 VALUES LESS THAN (UNIX_TIMESTAMP('2016-09-01')*1000),
    PARTITION p20160901 VALUES LESS THAN (UNIX_TIMESTAMP('2016-10-01')*1000),
    PARTITION p20161001 VALUES LESS THAN (UNIX_TIMESTAMP('2016-11-01')*1000),
    PARTITION p20161101 VALUES LESS THAN (UNIX_TIMESTAMP('2016-12-01')*1000),
    PARTITION p20161201 VALUES LESS THAN (UNIX_TIMESTAMP('2017-01-01')*1000),
    PARTITION p20170101 VALUES LESS THAN (UNIX_TIMESTAMP('2017-02-01')*1000),
    PARTITION p20170201 VALUES LESS THAN (UNIX_TIMESTAMP('2017-03-01')*1000),
    PARTITION p20170301 VALUES LESS THAN (UNIX_TIMESTAMP('2017-04-01')*1000),
    PARTITION p20170401 VALUES LESS THAN (UNIX_TIMESTAMP('2017-05-01')*1000),
    PARTITION p20170501 VALUES LESS THAN (UNIX_TIMESTAMP('2017-06-01')*1000),
    PARTITION p20170601 VALUES LESS THAN (UNIX_TIMESTAMP('2017-07-01')*1000),
    PARTITION p20170701 VALUES LESS THAN (UNIX_TIMESTAMP('2017-08-01')*1000),
    PARTITION p20170801 VALUES LESS THAN (UNIX_TIMESTAMP('2017-09-01')*1000),
    PARTITION p20170901 VALUES LESS THAN (UNIX_TIMESTAMP('2017-10-01')*1000),
    PARTITION p20171001 VALUES LESS THAN (UNIX_TIMESTAMP('2017-11-01')*1000),
    PARTITION p20171101 VALUES LESS THAN (UNIX_TIMESTAMP('2017-12-01')*1000),
    PARTITION p20171201 VALUES LESS THAN (UNIX_TIMESTAMP('2018-01-01')*1000),
    PARTITION pMAX VALUES LESS THAN (MAXVALUE)
)
$$$
