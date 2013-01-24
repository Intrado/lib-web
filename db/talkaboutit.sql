-- global 'talkaboutit' database

CREATE TABLE `smscustomer` (
 `customerid` int(11) NOT NULL,
 `smsnumber` varchar(10) NOT NULL,
 PRIMARY KEY (`customerid`,`smsnumber`)
) ENGINE=InnoDB;

-- TAI 1.5

CREATE TABLE `smsthreadstate` (
 `smsnumber` varchar(10) NOT NULL,
 `state` enum('init','message','dir','recipient') NOT NULL DEFAULT 'init',
 `lasttimestamp` bigint(11) NOT NULL,
 `message` text NOT NULL,
 `staffnamepartial` varchar(160) DEFAULT NULL,
 `directorypageindex` int(11) DEFAULT NULL,
 PRIMARY KEY (`smsnumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- TAI 1.5.1

ALTER TABLE `smsthreadstate` CHANGE `lasttimestamp` `lasttimestampms` BIGINT NOT NULL;

