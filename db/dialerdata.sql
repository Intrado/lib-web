
-- DIALER DATA 1.2
-- 
-- Dumping data for table `access`
-- 

INSERT INTO `access` (`id`, `customerid`, `moduserid`, `name`, `description`, `created`, `modified`, `deleted`) VALUES (1, 1, 1, 'System Administrators', '', '2005-07-20 00:00:00', '2005-12-12 17:49:55', 0);
INSERT INTO `access` (`id`, `customerid`, `moduserid`, `name`, `description`, `created`, `modified`, `deleted`) VALUES (2, 1, 1, 'Attendance', '', '2005-12-12 17:50:46', '2005-12-12 17:50:46', 0);

-- 
-- Dumping data for table `customer`
-- 

INSERT INTO `customer` (`id`, `name`, `addr1`, `addr2`, `city`, `state`, `zip`, `contactname`, `contactphone`, `contactemail`, `enabled`) VALUES (1, 'School District', '', '', '', '', '', '', '', '', 1);

-- 
-- Dumping data for table `fieldmap`
-- 

INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (19, 1, 'f01', 'First Name', 'searchable,text');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (20, 1, 'f02', 'Last Name', 'searchable,text');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (23, 1, 'f03', 'Language', 'searchable,multisearch');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (24, 1, 'f04', 'Gender', 'searchable,multisearch');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (25, 1, 'f05', 'Afterschool', 'searchable,multisearch');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (26, 1, 'f06', 'Lunch Balance', '');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (27, 1, 'f07', 'GPA', '');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (28, 1, 'f08', 'Grade', 'searchable,multisearch');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (29, 1, 'f09', 'School', 'searchable,multisearch');
INSERT INTO `fieldmap` (`id`, `customerid`, `fieldnum`, `name`, `options`) VALUES (30, 1, 'f10', 'Absent Date', 'searchable,reldate');

-- 
-- Dumping data for table `jobtype`
-- 

INSERT INTO `jobtype` (`id`, `customerid`, `name`, `priority`) VALUES (1, 1, 'Emergency', 10000);
INSERT INTO `jobtype` (`id`, `customerid`, `name`, `priority`) VALUES (2, 1, 'Attendance', 20000);

-- 
-- Dumping data for table `language`
-- 

INSERT INTO `language` (`id`, `customerid`, `name`, `code`) VALUES (1, 1, 'English', '');
INSERT INTO `language` (`id`, `customerid`, `name`, `code`) VALUES (2, 1, 'Spanish', '');

-- 
-- Dumping data for table `permission`
-- 

INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (1, 1, 'loginweb', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (2, 1, 'manageprofile', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (3, 1, 'manageaccount', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (4, 1, 'manageaccount', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (5, 1, 'managesystem', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (6, 1, 'loginphone', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (7, 1, 'startstats', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (8, 1, 'startshort', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (9, 1, 'starteasy', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (27, 1, 'sendprint', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (11, 1, 'callmax', '10');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (12, 1, 'sendemail', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (26, 1, 'sendphone', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (28, 1, 'sendmulti', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (30, 1, 'createlist', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (29, 1, 'createrepeat', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (17, 1, 'createreport', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (21, 1, 'datafields', 'f01|f02|f03|f04|f05|f06|f07|f08|f09|f10');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (31, 1, 'maxjobdays', '7');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (32, 1, 'viewsystemreports', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (33, 1, 'managesystemjobs', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (34, 1, 'managemyaccount', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (35, 1, 'viewcontacts', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (36, 1, 'viewsystemactive', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (37, 1, 'viewsystemrepeating', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (38, 1, 'viewsystemcompleted', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (39, 2, 'loginweb', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (40, 2, 'startstats', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (41, 2, 'startshort', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (42, 2, 'sendphone', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (43, 2, 'callmax', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (44, 2, 'createlist', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (45, 2, 'datafields', 'f01|f02|f03|f04|f05|f06|f07|f08|f09|f10');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (46, 2, 'createrepeat', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (47, 2, 'maxjobdays', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (48, 2, 'createreport', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (49, 2, 'managemyaccount', '1');
INSERT INTO `permission` (`id`, `accessid`, `name`, `value`) VALUES (50, 2, 'viewcontacts', '1');

-- 
-- Dumping data for table `setting`
-- 

INSERT INTO `setting` (`id`, `customerid`, `name`, `value`, `moduserid`, `modified`) VALUES (1, 1, 'retry', '15', NULL, NULL);
INSERT INTO `setting` (`id`, `customerid`, `name`, `value`, `moduserid`, `modified`) VALUES (2, 1, 'disablerepeat', '0', NULL, NULL);


-- 
-- Dumping data for table `ttsvoice`
-- 

INSERT INTO `ttsvoice` (`id`, `ttsname`, `name`, `language`, `gender`) VALUES (1, '', '', 'english', 'male');
INSERT INTO `ttsvoice` (`id`, `ttsname`, `name`, `language`, `gender`) VALUES (2, '', '', 'english', 'female');
INSERT INTO `ttsvoice` (`id`, `ttsname`, `name`, `language`, `gender`) VALUES (3, '', '', 'spanish', 'male');
INSERT INTO `ttsvoice` (`id`, `ttsname`, `name`, `language`, `gender`) VALUES (4, '', '', 'spanish', 'female');

-- 
-- Dumping data for table `user`
-- 

INSERT INTO `user` (`id`, `accessid`, `login`, `password`, `accesscode`, `pincode`, `customerid`, `personid`, `firstname`, `lastname`, `phone`, `email`, `enabled`, `lastlogin`, `deleted`) VALUES (1, 1, 'admin', '43e9a4ab75570f5b', '123456', '565491d704013245', 1, NULL, 'System', 'Administrator', '', '', 1, NULL, 0);




-- create the user account

GRANT ALL PRIVILEGES ON *.* TO 'sharpteeth'@'%'IDENTIFIED BY 'sharpteeth202' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 ;
GRANT ALL PRIVILEGES ON *.* TO 'sharpteeth'@'localhost'IDENTIFIED BY 'sharpteeth202' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 ;

