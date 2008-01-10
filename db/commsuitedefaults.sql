--
-- Default values for new database
--
INSERT INTO `access` VALUES (1,'admin','')
$$$

INSERT INTO `fieldmap` VALUES (1,'f01','First Name','searchable,text,firstname'),(2,'f02','Last Name','searchable,text,lastname'),(3,'f03','Language','searchable,multisearch,language')
$$$

INSERT INTO `jobtype` VALUES (1,'Emergency',1,'Emergencies Only',0,0),(2,'Attendance',2,'Attendance Notifications',0,0),(3,'General',3,'General Announcements',0,0),(4,'Survey',3,'Survey Notifications',1,0)
$$$

INSERT INTO `jobtypepref` VALUES (1,'phone',0,1),(1,'phone',1,1),(1,'phone',2,1),(1,'email',0,1),(1,'email',1,1),(1,'sms',0,1),(1,'sms',1,1)
$$$
INSERT INTO `jobtypepref` VALUES (2,'phone',0,1),(2,'phone',1,0),(2,'phone',2,0),(2,'email',0,1),(2,'email',1,0),(2,'sms',0,1),(2,'sms',1,0)
$$$
INSERT INTO `jobtypepref` VALUES (3,'phone',0,1),(3,'phone',1,0),(3,'phone',2,0),(3,'email',0,1),(3,'email',1,0),(3,'sms',0,1),(3,'sms',1,0)
$$$
INSERT INTO `jobtypepref` VALUES (4,'phone',0,1),(4,'phone',1,0),(4,'phone',2,0),(4,'email',0,1),(4,'email',1,0),(4,'sms',0,0),(4,'sms',1,0)
$$$

INSERT INTO `language` VALUES (1,'English'),(2,'Spanish')
$$$

INSERT INTO `permission` VALUES (1,1,'loginweb','1'),(2,1,'manageprofile','1'),(3,1,'manageaccount','1'),(4,1,'managesystem','1'),(5,1,'loginphone','1'),(6,1,'startstats','1'),(7,1,'startshort','1'),(8,1,'starteasy','1'),(9,1,'sendprint','0'),(10,1,'callmax','10'),(11,1,'sendemail','1'),(12,1,'sendphone','1'),(13,1,'sendsms','0'),(14,1,'sendmulti','1'),(15,1,'leavemessage','1'),(16,1,'survey','1'),(17,1,'createlist','1'),(18,1,'createrepeat','1'),(19,1,'createreport','1'),(20,1,'maxjobdays','7'),(21,1,'viewsystemreports','1'),(22,1,'viewusagestats','1'),(23,1,'viewcalldistribution','1'),(24,1,'managesystemjobs','1'),(25,1,'managemyaccount','1'),(26,1,'viewcontacts','1'),(27,1,'viewsystemactive','1'),(28,1,'viewsystemrepeating','1'),(29,1,'viewsystemcompleted','1'),(30,1,'listuploadids','1'),(31,1,'listuploadcontacts','1'),(32,1,'setcallerid','1'),(33,1,'blocknumbers','1'),(34,1,'callblockingperms','editall'),(35,1,'metadata','1'),(36,1,'managetasks','1')
$$$

INSERT INTO `setting` VALUES (1,'maxphones','3'),(2,'maxemails','2'),(3,'retry','15'),(4,'disablerepeat','0'),(5,'surveyurl','http://myschool.com/survey/'),(6,'displayname','CommSuite Customer'),(7,'timezone','US/Pacific'),(8,'inboundnumber',''),(9,'_maxusers','5'),(10,'_renewaldate',''),(12,'_callspurchased',''),(13,'checkpassword','0'),(14,'_hassms','0'),(15,'maxsms','2')
$$$

INSERT INTO `ttsvoice` VALUES (1,'english','male'),(2,'english','female'),(3,'spanish','male'),(4,'spanish','female')
$$$

INSERT INTO `user` VALUES (1,1,'schoolmessenger','43e9a4ab75570f5b','','','SchoolMessenger','Admin','','','',1,NULL,0,0)
$$$
