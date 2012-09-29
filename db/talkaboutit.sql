-- global 'talkaboutit' database

CREATE TABLE `smscustomer` (
 `customerid` int(11) NOT NULL,
 `smsnumber` varchar(10) NOT NULL,
 PRIMARY KEY (`customerid`,`smsnumber`)
) ENGINE=InnoDB;


